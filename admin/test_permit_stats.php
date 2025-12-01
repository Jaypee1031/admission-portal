<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_permit.php';
require_once '../includes/test_results.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$testPermit = new TestPermit();
$testResults = new TestResults();

// Get statistics
$db = getDB();

// Overall statistics
$stats = $testPermit->getTestPermitStats();

// Monthly statistics
$monthlyStats = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected
        FROM test_permits 
        WHERE DATE_FORMAT(issued_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $monthlyStats[$month] = $stmt->fetch();
}

// Course preference statistics
$courseStats = $db->prepare("
    SELECT 
        af.course_first,
        COUNT(*) as count
    FROM test_permits tp
    JOIN students s ON tp.student_id = s.id
    JOIN admission_forms af ON s.id = af.student_id
    GROUP BY af.course_first
    ORDER BY count DESC
    LIMIT 10
");
$courseStats->execute();
$coursePreferences = $courseStats->fetchAll();

// Test Results Statistics
$testResultsStats = $testResults->getTestResultStats();

// Exam Performance Statistics
$examPerformanceStats = $db->prepare("
    SELECT 
        COUNT(*) as total_exams,
        COUNT(CASE WHEN exam_rating >= 75 THEN 1 END) as passed,
        COUNT(CASE WHEN exam_rating < 75 THEN 1 END) as failed,
        AVG(exam_rating) as average_rating,
        MAX(exam_rating) as highest_rating,
        MIN(exam_rating) as lowest_rating
    FROM test_results 
    WHERE exam_rating IS NOT NULL
");
$examPerformanceStats->execute();
$examPerformance = $examPerformanceStats->fetch();

// Subject Performance Statistics
$subjectPerformanceStats = $db->prepare("
    SELECT 
        AVG(gen_info_transmuted) as avg_gen_info,
        AVG(filipino_transmuted) as avg_filipino,
        AVG(english_transmuted) as avg_english,
        AVG(science_transmuted) as avg_science,
        AVG(math_transmuted) as avg_math
    FROM test_results 
    WHERE gen_info_transmuted IS NOT NULL
");
$subjectPerformanceStats->execute();
$subjectPerformance = $subjectPerformanceStats->fetch();

// Monthly Test Results
$monthlyTestResults = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN exam_rating >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN exam_rating < 75 THEN 1 END) as failed,
            AVG(exam_rating) as avg_rating
        FROM test_results 
        WHERE DATE_FORMAT(processed_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $monthlyTestResults[$month] = $stmt->fetch();
}

// Recent activity
$recentActivity = $db->prepare("
    SELECT tp.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, a.full_name as admin_name
    FROM test_permits tp
    JOIN students s ON tp.student_id = s.id
    LEFT JOIN admins a ON tp.approved_by = a.id
    ORDER BY tp.issued_at DESC
    LIMIT 10
");
$recentActivity->execute();
$recentPermits = $recentActivity->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Analytics Dashboard - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #28a745);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 24px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .course-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
        
        .course-rank {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .activity-timeline {
            position: relative;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .activity-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }
        
        .activity-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .activity-content {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar.bg-gradient {
            background: linear-gradient(90deg, #007bff, #28a745);
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .btn-group .btn.active {
            background-color: #007bff;
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .chart-container canvas {
            max-height: 300px !important;
            max-width: 100% !important;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            .chart-container canvas {
                max-height: 250px !important;
            }
        }
        
        @media (max-width: 576px) {
            .chart-container {
                height: 200px;
            }
            
            .chart-container canvas {
                max-height: 200px !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="applicantsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i>Applicants
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="applicantsDropdown">
                        <li><a class="dropdown-item" href="applicants.php">
                            <i class="fas fa-list me-2"></i>All Applicants
                        </a></li>
                        <li><a class="dropdown-item" href="test_permits.php">
                            <i class="fas fa-id-card me-2"></i>Test Permits
                        </a></li>
                        <li><a class="dropdown-item" href="admission_forms.php">
                            <i class="fas fa-file-alt me-2"></i>Admission Forms
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="f2_forms_management.php">
                            <i class="fas fa-file-alt me-2"></i>Personal Data Forms
                        </a></li>
                        <li><a class="dropdown-item" href="test_results_management.php">
                            <i class="fas fa-chart-line me-2"></i>Test Results
                        </a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                        <li><a class="dropdown-item" href="add_student.php">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </a></li>
                        <li><a class="dropdown-item" href="add_admin.php">
                            <i class="fas fa-user-shield me-2"></i>Add Admin
                        </a></li>
                    </ul>
                </div>
                <a class="nav-link active" href="test_permit_stats.php">
                    <i class="fas fa-chart-bar me-1"></i>Statistics
                </a>
                <a class="nav-link" href="test_permit_settings.php">
                    <i class="fas fa-cog me-1"></i>Settings
                </a>
                <div class="navbar-text me-3 d-flex align-items-center">
                    <i class="fas fa-user-shield me-2 text-warning"></i>
                    <span class="fw-bold">System Administrator</span>
                </div>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-chart-bar me-2 text-primary"></i>
                                Admission Analytics Dashboard
                            </h2>
                            <p class="text-muted mb-0">Comprehensive insights into test permits, exam performance, and admission statistics</p>
                        </div>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <a href="export_test_permit_stats.php" class="btn btn-success">
                                <i class="fas fa-file-excel me-1"></i>Export to Excel
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-calendar me-1"></i>
                            Last updated: <?php echo date('M d, Y H:i'); ?>
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-sync-alt me-1"></i>
                            Auto-refresh enabled
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics with Better Design -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-primary"><?php echo $stats['total_permits']; ?></h3>
                        <p class="stats-label">Total Test Permits</p>
                        <small class="text-muted">
                            <i class="fas fa-arrow-up text-success me-1"></i>
                            All time permits issued
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-success"><?php echo $stats['upcoming_exams']; ?></h3>
                        <p class="stats-label">Upcoming Exams</p>
                        <small class="text-muted">
                            <i class="fas fa-clock text-warning me-1"></i>
                            Scheduled for future dates
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-warning"><?php echo $stats['past_exams']; ?></h3>
                        <p class="stats-label">Completed Exams</p>
                        <small class="text-muted">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Successfully finished
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-info"><?php echo round(($stats['upcoming_exams'] / max($stats['total_permits'], 1)) * 100, 1); ?>%</h3>
                        <p class="stats-label">Active Rate</p>
                        <small class="text-muted">
                            <i class="fas fa-chart-line text-primary me-1"></i>
                            Current activity level
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-primary"></i>
                    </div>
                    <h4 class="text-primary"><?php echo count($recentPermits); ?></h4>
                    <p class="text-muted mb-0">Recent Applications</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>
                        Last 10 activities
                    </small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-graduation-cap fa-3x text-success"></i>
                    </div>
                    <h4 class="text-success"><?php echo count($coursePreferences); ?></h4>
                    <p class="text-muted mb-0">Course Options</p>
                    <small class="text-info">
                        <i class="fas fa-book me-1"></i>
                        Available programs
                    </small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-calendar-alt fa-3x text-warning"></i>
                    </div>
                    <h4 class="text-warning"><?php echo date('M Y'); ?></h4>
                    <p class="text-muted mb-0">Current Month</p>
                    <small class="text-primary">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('F Y'); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Test Results Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">
                    <i class="fas fa-chart-bar me-2 text-success"></i>
                    Exam Performance Analytics
                </h4>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-success"><?php echo $examPerformance['passed'] ?? 0; ?></h3>
                        <p class="stats-label">Students PASSED</p>
                        <small class="text-muted">
                            <i class="fas fa-graduation-cap text-success me-1"></i>
                            Exam Rating ≥ 75%
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-danger"><?php echo $examPerformance['failed'] ?? 0; ?></h3>
                        <p class="stats-label">Students FAILED</p>
                        <small class="text-muted">
                            <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                            Exam Rating < 75%
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-info"><?php echo number_format($examPerformance['average_rating'] ?? 0, 1); ?>%</h3>
                        <p class="stats-label">Average Rating</p>
                        <small class="text-muted">
                            <i class="fas fa-chart-line text-info me-1"></i>
                            Overall performance
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number text-warning"><?php echo $examPerformance['total_exams'] > 0 ? number_format(($examPerformance['passed'] / $examPerformance['total_exams']) * 100, 1) : 0; ?>%</h3>
                        <p class="stats-label">Pass Rate</p>
                        <small class="text-muted">
                            <i class="fas fa-trophy text-warning me-1"></i>
                            Success percentage
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Performance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-book me-2 text-primary"></i>
                        Subject Performance Breakdown
                    </h5>
                    <div class="row">
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-primary"><?php echo number_format($subjectPerformance['avg_gen_info'] ?? 0, 1); ?></h4>
                                <small class="text-muted">General Info</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo ($subjectPerformance['avg_gen_info'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-success"><?php echo number_format($subjectPerformance['avg_filipino'] ?? 0, 1); ?></h4>
                                <small class="text-muted">Filipino</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($subjectPerformance['avg_filipino'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-warning"><?php echo number_format($subjectPerformance['avg_english'] ?? 0, 1); ?></h4>
                                <small class="text-muted">English</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-warning" style="width: <?php echo ($subjectPerformance['avg_english'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-info"><?php echo number_format($subjectPerformance['avg_science'] ?? 0, 1); ?></h4>
                                <small class="text-muted">Science</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-info" style="width: <?php echo ($subjectPerformance['avg_science'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-danger"><?php echo number_format($subjectPerformance['avg_math'] ?? 0, 1); ?></h4>
                                <small class="text-muted">Mathematics</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo ($subjectPerformance['avg_math'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center mb-3">
                            <div class="subject-score">
                                <h4 class="text-secondary"><?php echo number_format($examPerformance['average_rating'] ?? 0, 1); ?></h4>
                                <small class="text-muted">Overall</small>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-secondary" style="width: <?php echo ($examPerformance['average_rating'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-12 mb-3">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Monthly Trends
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="changeChartPeriod('monthly')">Monthly</button>
                            <button type="button" class="btn btn-outline-primary" onclick="changeChartPeriod('weekly')">Weekly</button>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-3">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Status Distribution
                    </h5>
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-success">Approved</span>
                            <span class="fw-bold"><?php echo $stats['approved_permits'] ?? 0; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-warning">Pending</span>
                            <span class="fw-bold"><?php echo $stats['pending_permits'] ?? 0; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-danger">Rejected</span>
                            <span class="fw-bold"><?php echo $stats['rejected_permits'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Preferences and Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>
                            Popular Course Choices
                        </h5>
                        <span class="badge bg-primary"><?php echo count($coursePreferences); ?> courses</span>
                    </div>
                    <div class="course-list">
                        <?php 
                        $totalCourseCount = array_sum(array_column($coursePreferences, 'count'));
                        $counter = 0;
                        foreach ($coursePreferences as $course): 
                            $counter++;
                            $percentage = round(($course['count'] / max($totalCourseCount, 1)) * 100, 1);
                        ?>
                        <div class="course-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="course-rank me-3">#<?php echo $counter; ?></span>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($course['course_first'] ?? 'Unknown Course'); ?></h6>
                                        <small class="text-muted"><?php echo $course['count']; ?> applications</small>
                                    </div>
                                </div>
                                <span class="badge bg-primary"><?php echo $percentage; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-gradient" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2 text-success"></i>
                            Recent Activity
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshActivity()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="activity-timeline">
                        <?php foreach ($recentPermits as $permit): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-<?php echo $permit['status'] === 'Approved' ? 'success' : ($permit['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                <i class="fas fa-<?php echo $permit['status'] === 'Approved' ? 'check' : ($permit['status'] === 'Pending' ? 'clock' : 'times'); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($permit['student_name'] ?? 'Unknown Student'); ?></h6>
                                        <p class="mb-1 text-muted">
                                            <span class="badge bg-<?php echo $permit['status'] === 'Approved' ? 'success' : ($permit['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo $permit['status']; ?>
                                            </span>
                                            - <?php echo htmlspecialchars($permit['permit_number'] ?? 'N/A'); ?>
                                        </p>
                                        <?php if ($permit['admin_name']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Processed by: <?php echo htmlspecialchars($permit['admin_name'] ?? 'Unknown Admin'); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo date('M d, H:i', strtotime($permit['issued_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Interactive functions
        function changeChartPeriod(period) {
            // Remove active class from all buttons
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Here you would typically reload the chart with new data
            console.log('Switching to', period, 'view');
        }
        
        function refreshActivity() {
            // Add loading animation
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
            btn.disabled = true;
            
            // Simulate refresh (in real app, this would make an AJAX call)
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                // Show success message
                showNotification('Activity refreshed successfully!', 'success');
            }, 1500);
        }
        
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats cards
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animate activity items
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.4s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 500 + (index * 100));
            });
        });

        // Monthly trends chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyStats); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    },
                    line: {
                        tension: 0.4
                    }
                }
            },
            data: {
                labels: Object.keys(monthlyData).map(month => {
                    const date = new Date(month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Total',
                        data: Object.values(monthlyData).map(data => data.total),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    },
                    {
                        label: 'Approved',
                        data: Object.values(monthlyData).map(data => data.approved),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1
                    },
                    {
                        label: 'Pending',
                        data: Object.values(monthlyData).map(data => data.pending),
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.2)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status distribution chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo count(array_filter($recentPermits, fn($p) => $p['status'] === 'Approved')); ?>,
                        <?php echo count(array_filter($recentPermits, fn($p) => $p['status'] === 'Pending')); ?>,
                        <?php echo count(array_filter($recentPermits, fn($p) => $p['status'] === 'Rejected')); ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                cutout: '60%',
                elements: {
                    arc: {
                        borderWidth: 2,
                        borderColor: '#fff'
                    }
                }
            }
        });
    </script>
</body>
</html>
