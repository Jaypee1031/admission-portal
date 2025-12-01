<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';
require_once '../includes/favicon.php';
require_once '../config/grading_config.php';

// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$testResults = new TestResults();

// Get test result for this student
$result = $testResults->getTestResult($user['id']);

// Recalculate overall rating in real-time based on exam rating
if ($result && isset($result['exam_rating'])) {
    $correctRating = getOverallRating($result['exam_rating']);
    // Update the result with correct rating for display
    $result['overall_rating'] = $correctRating;
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                <span class="navbar-text me-3">
                    <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                </span>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                Test Results
                            </h2>
                            <p class="text-muted mb-0">View your college admission test results.</p>
                        </div>
                        <div class="text-end">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Informational notice about official release of test results
        $studentTypeLabel = $user['student_type'] ?? ($user['type'] ?? '');
        $studentTypeLabel = trim((string)$studentTypeLabel);

        if (strcasecmp($studentTypeLabel, 'Transferee') === 0) {
            $requirementLabel = 'transferee admission requirements';
        } else {
            // Default to Freshman wording
            $requirementLabel = 'freshman admission requirements';
        }
        ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Official test results will be given to you personally by QSU Guidance/Admissions staff when you visit the university.
                    Please bring all required documents for your
                    <strong><?php echo htmlspecialchars($studentTypeLabel ?: 'admission'); ?></strong>
                    application, including your <?php echo htmlspecialchars($requirementLabel); ?>, when you come to campus.
                </div>
            </div>
        </div>

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($alert['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$result): ?>
        <!-- No Results Available -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-chart-line fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">No Test Results Available</h4>
                    <p class="text-muted mb-4">
                        Your test results are not yet available. Please check back later or contact the administration office.
                    </p>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Test Results Available -->
        <div class="row">
            <!-- Student Information -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-user me-2 text-primary"></i>
                        Student Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Name:</strong> <?php echo htmlspecialchars(($result['first_name'] ?? '') . ' ' . ($result['last_name'] ?? '')); ?>
                            <?php if ($result['middle_name']): ?>
                                <?php echo htmlspecialchars(' ' . $result['middle_name']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Permit Number:</strong> 
                            <span class="badge bg-info"><?php echo htmlspecialchars($result['permit_number']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Exam Date:</strong> <?php echo date('M d, Y', strtotime($result['exam_date'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Exam Time:</strong> <?php echo date('g:i A', strtotime($result['exam_time'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Scores -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2 text-success"></i>
                        Subject Scores
                    </h5>
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2"><?php echo $result['gen_info_raw'] ?? 'N/A'; ?>/30</h4>
                                <p class="text-muted mb-1">General Info</p>
                                <small class="text-success"><?php echo number_format($result['gen_info_transmuted'] ?? 0, 1); ?>%</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2"><?php echo $result['filipino_raw'] ?? 'N/A'; ?>/50</h4>
                                <p class="text-muted mb-1">Filipino</p>
                                <small class="text-success"><?php echo number_format($result['filipino_transmuted'] ?? 0, 1); ?>%</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2"><?php echo $result['english_raw'] ?? 'N/A'; ?>/60</h4>
                                <p class="text-muted mb-1">English</p>
                                <small class="text-success"><?php echo number_format($result['english_transmuted'] ?? 0, 1); ?>%</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2"><?php echo $result['science_raw'] ?? 'N/A'; ?>/60</h4>
                                <p class="text-muted mb-1">Science</p>
                                <small class="text-success"><?php echo number_format($result['science_transmuted'] ?? 0, 1); ?>%</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2"><?php echo $result['math_raw'] ?? 'N/A'; ?>/50</h4>
                                <p class="text-muted mb-1">Mathematics</p>
                                <small class="text-success"><?php echo number_format($result['math_transmuted'] ?? 0, 1); ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Results -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2 text-success"></i>
                        Overall Results
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-primary mb-2"><?php echo number_format($result['exam_rating'] ?? 0, 1); ?></h3>
                                <p class="text-muted mb-0">Exam Rating</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-success mb-2"><?php echo number_format($result['exam_percentage'] ?? 0, 1); ?>%</h3>
                                <p class="text-muted mb-0">Exam Percentage</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3>
                                    <span class="badge bg-<?php 
                                        echo $result['overall_rating'] === 'Excellent' ? 'success' : 
                                            ($result['overall_rating'] === 'Very Good' ? 'info' :
                                            ($result['overall_rating'] === 'Passed' ? 'success' : 
                                            ($result['overall_rating'] === 'Conditional' ? 'warning' : 'danger'))); 
                                    ?> fs-6">
                                        <?php echo htmlspecialchars($result['overall_rating'] ?? 'N/A'); ?>
                                    </span>
                                </h3>
                                <p class="text-muted mb-0">Overall Rating</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ranking and GWA -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-trophy me-2 text-warning"></i>
                        Ranking & Academic Performance
                    </h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-warning mb-2">#<?php echo $result['final_rank'] ?? 'N/A'; ?></h3>
                                <p class="text-muted mb-0">Final Rank</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-info mb-2"><?php echo number_format($result['gwa_score'] ?? 0, 2); ?></h3>
                                <p class="text-muted mb-0">GWA Score</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-success mb-2"><?php echo number_format($result['interview_score'] ?? 0, 1); ?></h3>
                                <p class="text-muted mb-0">Interview Score</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Analysis -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2 text-info"></i>
                        Performance Analysis
                    </h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Exam Rating</span>
                                    <span><?php echo number_format($result['exam_rating'] ?? 0, 1); ?></span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <?php $passingThreshold = PASSING_THRESHOLD; ?>
                                    <div class="progress-bar bg-<?php echo ($result['exam_rating'] ?? 0) >= $passingThreshold ? 'success' : 'danger'; ?>" 
                                         style="width: <?php echo min(100, $result['exam_rating'] ?? 0); ?>%">
                                        <?php echo number_format($result['exam_rating'] ?? 0, 1); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendation -->
            <?php if ($result['recommendation']): ?>
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-comment me-2 text-warning"></i>
                        Recommendation
                    </h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo nl2br(htmlspecialchars($result['recommendation'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
