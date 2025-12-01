<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$testResults = new TestResults();
$db = getDB();

$resultId = (int)($_GET['id'] ?? 0);
$permitNumber = $_GET['permit_number'] ?? '';

if (!$resultId && !empty($permitNumber)) {
    $stmt = $db->prepare("SELECT id FROM test_results WHERE permit_number = ?");
    $stmt->execute([$permitNumber]);
    $row = $stmt->fetch();
    if ($row && isset($row['id'])) {
        $resultId = (int)$row['id'];
    }
}

if (!$resultId) {
    redirect('/admin/test_results_management.php');
}

// Get comprehensive result details
$stmt = $db->prepare("
    SELECT 
        tr.*,
        s.id as student_id,
        s.first_name, 
        s.last_name, 
        s.middle_name, 
        s.email, 
        s.type,
        af.course_first,
        af.course_second,
        af.course_third,
        af.last_school,
        af.school_address,
        f2.general_average as f2_gwa,
        tp.exam_date as permit_exam_date, 
        tp.exam_time, 
        tp.exam_room,
        a.full_name as processed_by_name
    FROM test_results tr
    JOIN students s ON tr.student_id = s.id
    LEFT JOIN admission_forms af ON s.id = af.student_id
    LEFT JOIN f2_personal_data_forms f2 ON s.id = f2.student_id
    LEFT JOIN test_permits tp ON tr.permit_number = tp.permit_number
    LEFT JOIN admins a ON tr.processed_by = a.id
    WHERE tr.id = ?
");
$stmt->execute([$resultId]);
$result = $stmt->fetch();

if (!$result) {
    redirect('/admin/test_results_management.php');
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAT Result - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .cat-result-container {
            background: white;
            border: 2px solid #000;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Calibri', 'Arial', sans-serif;
        }
        .single-content-box {
            border: 2px solid #203764;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            background: white;
            color: #203764;
            font-family: 'Calibri', Arial, sans-serif;
        }
        
        .single-content-box table {
            color: #203764;
        }
        
        .single-content-box table th,
        .single-content-box table td {
            border-color: #203764 !important;
        }
        
        .single-content-box hr {
            border-color: #203764;
        }
        
        .single-content-box .border {
            border-color: #203764 !important;
        }
        
        .subjects-table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .subjects-table th:first-child,
        .subjects-table td:first-child {
            width: 50%;
            text-align: left;
        }
        
        .subjects-table th:nth-child(2),
        .subjects-table td:nth-child(2) {
            width: 25%;
        }
        
        .subjects-table th:nth-child(3),
        .subjects-table td:nth-child(3) {
            width: 25%;
        }
        .header-section {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .info-section {
            border: 1px solid #000;
            padding: 15px;
            margin: 15px 0;
        }
        .scores-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .scores-table th, .scores-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .scores-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .final-result {
            border: 2px solid #000;
            padding: 15px;
            margin: 20px 0;
            background-color: #f9f9f9;
        }
        .requirements-section {
            border: 1px solid #000;
            padding: 15px;
            margin: 15px 0;
        }
        .checkbox-item {
            margin: 5px 0;
        }
        .result-failed {
            color: #dc3545;
            font-weight: bold;
        }
        .result-passed {
            color: #198754;
            font-weight: bold;
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                College Admission Test Result
                            </h2>
                            <p class="text-muted mb-0">Detailed CAT result report for <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></p>
                        </div>
                        <div class="text-end">
                            <a href="print_test_result.php?id=<?php echo $resultId; ?>" target="_blank" class="btn btn-warning me-2">
                                <i class="fas fa-print me-1"></i>Print PDF
                            </a>
                            <a href="print_test_result.php?id=<?php echo $resultId; ?>&download=1" class="btn btn-success me-2">
                                <i class="fas fa-download me-1"></i>Download PDF
                            </a>
                            <a href="test_results_management.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($alert['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

                <!-- Everything in ONE single box -->
        <div class="row">
            <div class="col-12">
                <div class="single-content-box">
                    <!-- Header Section INSIDE the box -->
                    <div class="d-flex align-items-center mb-3">
                        <img src="../assets/images/qsulogo.png" alt="QSU Logo" style="width: 50px; height: 50px; margin-right: 15px;">
                        <div>
                            <h1 style="font-size: 16px; margin: 0; font-weight: bold; color: #000;">QUIRINO STATE UNIVERSITY</h1>
                            <h2 style="font-size: 12px; margin: 5px 0; font-weight: normal; color: #000;">SAS - Office of Guidance, Counseling and Admission</h2>
                        </div>
                    </div>
                    <hr style="border: 1px solid #203764; margin: 15px 0;">
                    
                    <!-- Top right info boxes -->
                    <div class="row mb-3">
                        <div class="col-8">
                            <h3 style="font-size: 14px; font-weight: bold;">COLLEGE ADMISSION TEST (CAT)</h3>
                        </div>
                        <div class="col-4">
                            <div class="border p-1 mb-1" style="font-size: 10px;">
                                <strong>Examinee No.:</strong> <?php echo htmlspecialchars($result['student_id']); ?>
                            </div>
                            <div class="border p-1" style="font-size: 10px;">
                                <strong>Date of Exam:</strong> <?php echo date('m/d/Y', strtotime($result['exam_date'] ?? 'now')); ?>
                            </div>
                        </div>
                    </div>
                    <!-- Single rounded box with both name and course separated by line - SMALLER SIZE -->
                    <div class="mb-4">
                        <div class="border" style="border-radius: 8px; border-color: #203764; border-width: 2px; background: #f9f9f9; padding: 12px; max-width: 600px;">
                            <!-- Name (top part) -->
                            <div style="padding-bottom: 8px;">
                                <?php echo strtoupper(htmlspecialchars(($result['last_name'] ?? '') . ', ' . ($result['first_name'] ?? '') . ' ' . ($result['middle_name'] ?? ''))); ?>
                            </div>
                            
                            <!-- Dividing line -->
                            <hr style="border: 0; border-top: 1px solid #203764; margin: 0; margin-bottom: 8px;">
                            
                            <!-- Course (bottom part) -->
                            <div>
                                <?php echo htmlspecialchars($result['course_first'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subjects Table matching the form -->
                    <table class="table table-bordered subjects-table">
                        <thead class="table-dark">
                            <tr>
                                <th>SUBJECTS</th>
                                <th>RELATIVE WEIGHT</th>
                                <th>RATINGS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1. GENERAL INFORMATION</td>
                                <td class="text-center">10%</td>
                                <td class="text-center"><?php echo number_format($result['gen_info_transmuted'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <td>2. FILIPINO</td>
                                <td class="text-center">15%</td>
                                <td class="text-center"><?php echo number_format($result['filipino_transmuted'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <td>3. ENGLISH</td>
                                <td class="text-center">25%</td>
                                <td class="text-center"><?php echo number_format($result['english_transmuted'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <td>4. SCIENCE</td>
                                <td class="text-center">25%</td>
                                <td class="text-center"><?php echo number_format($result['science_transmuted'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <td>5. MATHEMATICS</td>
                                <td class="text-center">25%</td>
                                <td class="text-center"><?php echo number_format($result['math_transmuted'] ?? 0, 0); ?></td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>OVERALL AVERAGE RATING</strong></td>
                                <td class="text-center"><strong>50%</strong></td>
                                <td class="text-center"><strong><?php echo number_format($result['exam_rating'] ?? 0, 0); ?></strong></td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>GENERAL WEIGHTED AVERAGE (SHS)</strong></td>
                                <td class="text-center"><strong></strong></td>
                                <td class="text-center"><strong><?php echo number_format($result['f2_gwa'] ?? 0, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Overall Rating -->
                    <div class="final-result">
                        <h3 style="font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 15px;">OVERALL RATING (EXAM + ORAL INTERVIEW)</h3>
                        
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <th style="border: 1px solid #000; padding: 8px; text-align: left;">Component</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Rating</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Weight</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Weighted Score</th>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px;">Exam</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo number_format($result['exam_rating'] ?? 0, 2); ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;">50%</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo number_format(($result['exam_rating'] ?? 0) * 0.50, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px;">Oral Interview</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo number_format($result['interview_score'] ?? 0, 1); ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;">10%</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo number_format(($result['interview_score'] ?? 0) * 0.10, 2); ?></td>
                            </tr>
                            <tr style="font-weight: bold; background-color: #f0f0f0;">
                                <td style="border: 1px solid #000; padding: 8px;">TOTAL RATING</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;">–</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;">60%</td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo number_format($result['exam_rating'] ?? 0, 2); ?></td>
                            </tr>
                        </table>

                        <div style="margin-top: 15px;">
                            <p style="font-size: 14px; font-weight: bold;">
                                Result: 
                                <?php 
                                $examRating = $result['exam_rating'] ?? 0;
                                $overallRating = $examRating >= 75 ? 'PASSED' : 'FAILED';
                                if ($overallRating === 'FAILED'): 
                                ?>
                                    <span class="result-failed">❌ <?php echo strtoupper($overallRating); ?></span>
                                <?php else: ?>
                                    <span class="result-passed">✓ <?php echo strtoupper($overallRating); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Requirements Checklist -->
                    <div class="requirements-section">
                        <h3 style="font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 15px;">REQUIREMENTS CHECKLIST</h3>
                        
                        <?php
                        $requirements = [
                            'High School Form 138',
                            'Certification of Good Moral Character',
                            'PSA Authenticated Birth Certificate',
                            'Transfer Credential',
                            'Certification of Grades'
                        ];
                        
                        foreach ($requirements as $requirement):
                        ?>
                        <div class="checkbox-item">
                            <span style="margin-right: 10px;">☐</span><?php echo $requirement; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Final Summary -->
                    <div class="final-result">
                        <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px;">FINAL SUMMARY</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Raw Score:</strong> <?php echo $result['raw_score'] ?? 0; ?>/250</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Exam Rating:</strong> <?php echo number_format($result['exam_rating'] ?? 0, 2); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Total Rating:</strong> <?php echo number_format($result['exam_rating'] ?? 0, 2); ?>/100</p>
                            </div>
                        </div>
                        <p style="font-weight: bold; font-size: 14px;">
                            Result: 
                            <?php if ($overallRating === 'Failed'): ?>
                                <span class="result-failed">❌ <?php echo strtoupper($overallRating); ?></span>
                            <?php else: ?>
                                <span class="result-passed">✓ <?php echo strtoupper($overallRating); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($result['recommendation'])): ?>
                        <p><strong>Cause:</strong> <?php echo htmlspecialchars($result['recommendation']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- IMPORTANT Section -->
                    <div class="mt-4">
                        <h4 style="font-size: 12px; font-weight: bold;">IMPORTANT:</h4>
                        <div style="font-size: 10px; line-height: 1.4;">
                            <p>1. The result is valid only if there is any alteration.</p>
                            <p>2. To qualify, based programs, the examinee must have an average rating of at least 75%. In the formation</p>
                            <p>of the final score a 50% of the total is assigned to the entrance test score and Mathematics</p>
                            <p>Test. Student Handbook 2017 Edition)</p>
                        </div>
                    </div>

                    <!-- Certification Section -->
                    <div class="mt-4">
                        <p style="font-size: 11px;"><strong>Certified True and Correct by:</strong></p>
                        <div class="d-flex justify-content-center mt-3">
                            <div class="row" style="width: 80%;">
                                <div class="col-6 text-center">
                                    <p style="margin: 2px 0; font-size: 9px;"><strong><?php echo htmlspecialchars($result['processed_by_name'] ?? 'System Administrator'); ?></strong></p>
                                    <div style="border-bottom: 1px solid #203764; margin: 2px 0; height: 1px;"></div>
                                    <p style="margin: 2px 0; font-size: 9px;">Psychometrician</p>
                                </div>
                                <div class="col-6 text-center">
                                    <?php 
                                    $studentFullName = strtoupper(trim(($result['last_name'] ?? '') . ', ' . ($result['first_name'] ?? '') . ' ' . ($result['middle_name'] ?? '')));
                                    $processingDate = date('m/d/Y', strtotime($result['processed_at'] ?? 'now'));
                                    ?>
                                    <p style="margin: 2px 0; font-size: 9px;"><strong><?php echo htmlspecialchars($studentFullName); ?></strong></p>
                                    <div style="border-bottom: 1px solid #203764; margin: 2px 0; height: 1px;"></div>
                                    <p style="margin: 2px 0; font-size: 9px;"><?php echo $processingDate; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Processing Info -->
                    <div style="margin-top: 20px; font-size: 8px; color: #666;">
                        <p>Processed by: <?php echo htmlspecialchars($result['processed_by_name'] ?? 'System Administrator'); ?></p>
                        <p>Generated on: <?php echo date('M d, Y H:i'); ?></p>
                    </div>
                    </div> <!-- End single-content-box -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
