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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permitNumber = trim($_POST['permit_number']);
    $examDate = $_POST['exam_date'];
    
    // Subject scores
    $genInfoRaw = (int)$_POST['gen_info_raw'];
    $filipinoRaw = (int)$_POST['filipino_raw'];
    $englishRaw = (int)$_POST['english_raw'];
    $scienceRaw = (int)$_POST['science_raw'];
    $mathRaw = (int)$_POST['math_raw'];
    
    // Validate scores
    $errors = [];
    if (empty($permitNumber)) $errors[] = "Permit number is required";
    if (empty($examDate)) $errors[] = "Exam date is required";
    if ($genInfoRaw < 0 || $genInfoRaw > 30) $errors[] = "General Info score must be between 0-30";
    if ($filipinoRaw < 0 || $filipinoRaw > 50) $errors[] = "Filipino score must be between 0-50";
    if ($englishRaw < 0 || $englishRaw > 60) $errors[] = "English score must be between 0-60";
    if ($scienceRaw < 0 || $scienceRaw > 60) $errors[] = "Science score must be between 0-60";
    if ($mathRaw < 0 || $mathRaw > 50) $errors[] = "Math score must be between 0-50";
    
    if (empty($errors)) {
        // Prepare data for processing
        $excelData = [[
            'permit_number' => $permitNumber,
            'exam_date' => $examDate,
            'gen_info_raw' => $genInfoRaw,
            'filipino_raw' => $filipinoRaw,
            'english_raw' => $englishRaw,
            'science_raw' => $scienceRaw,
            'math_raw' => $mathRaw
        ]];
        
        // Process the result
        $result = $testResults->uploadTestResults($excelData, $user['id']);
        
        if ($result['success']) {
            showAlert($result['message'], 'success');
            $resultRow = $testResults->getTestResultByPermitNumber($permitNumber);
            if ($resultRow && isset($resultRow['id'])) {
                redirect('/admin/view_cat_result.php?id=' . urlencode($resultRow['id']));
            } else {
                redirect('/admin/test_results_management.php');
            }
        } else {
            showAlert($result['message'], 'error');
        }
    } else {
        showAlert(implode('<br>', $errors), 'error');
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Test Result Entry - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
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
                            <li><a class="dropdown-item active" href="manual_test_result_entry.php">
                                <i class="fas fa-edit me-2"></i>Manual Test Entry
                            </a></li>
                        </ul>
                    </div>
                    <a class="nav-link" href="test_permit_stats.php">
                        <i class="fas fa-chart-bar me-1"></i>Statistics
                    </a>
                    <a class="nav-link" href="test_permit_settings.php">
                        <i class="fas fa-cog me-1"></i>Settings
                    </a>
                </div>
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
                                <i class="fas fa-edit me-2 text-primary"></i>
                                Manual Test Result Entry
                            </h2>
                            <p class="text-muted mb-0">Enter individual student test scores and generate CAT report.</p>
                        </div>
                        <div class="text-end">
                            <a href="test_results_management.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Test Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show">
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Score Entry Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-calculator me-2 text-success"></i>
                        Enter Test Scores
                    </h5>
                    
                    <form method="POST" id="scoreEntryForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="permit_number" class="form-label">Permit Number *</label>
                                <input type="text" class="form-control" id="permit_number" name="permit_number" 
                                       placeholder="e.g., TP2025001" required>
                            </div>
                            <div class="col-md-6">
                                <label for="exam_date" class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" id="exam_date" name="exam_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            General Information (30 pts max)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <label for="gen_info_raw" class="form-label">Raw Score</label>
                                        <input type="number" class="form-control" id="gen_info_raw" name="gen_info_raw" 
                                               min="0" max="30" value="0" onchange="calculateScores()">
                                        <div class="form-text">Weight: 10%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-language me-2"></i>
                                            Filipino (50 pts max)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <label for="filipino_raw" class="form-label">Raw Score</label>
                                        <input type="number" class="form-control" id="filipino_raw" name="filipino_raw" 
                                               min="0" max="50" value="0" onchange="calculateScores()">
                                        <div class="form-text">Weight: 15%</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-book me-2"></i>
                                            English (60 pts max)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <label for="english_raw" class="form-label">Raw Score</label>
                                        <input type="number" class="form-control" id="english_raw" name="english_raw" 
                                               min="0" max="60" value="0" onchange="calculateScores()">
                                        <div class="form-text">Weight: 25%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-flask me-2"></i>
                                            Science (60 pts max)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <label for="science_raw" class="form-label">Raw Score</label>
                                        <input type="number" class="form-control" id="science_raw" name="science_raw" 
                                               min="0" max="60" value="0" onchange="calculateScores()">
                                        <div class="form-text">Weight: 25%</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calculator me-2"></i>
                                            Mathematics (50 pts max)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <label for="math_raw" class="form-label">Raw Score</label>
                                        <input type="number" class="form-control" id="math_raw" name="math_raw" 
                                               min="0" max="50" value="0" onchange="calculateScores()">
                                        <div class="form-text">Weight: 25%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-line me-2"></i>
                                            Calculations
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong>Total Raw Score:</strong> 
                                            <span id="total_raw_score" class="text-primary">0</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Exam Rating:</strong> 
                                            <span id="exam_rating" class="text-success">0.00</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Percentage:</strong> 
                                            <span id="percentage" class="text-info">0.00%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Save & Generate CAT Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        Scoring Information
                    </h5>
                    
                    <div class="mb-3">
                        <h6>Subject Weights:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-circle text-primary me-2"></i>General Info: 10%</li>
                            <li><i class="fas fa-circle text-success me-2"></i>Filipino: 15%</li>
                            <li><i class="fas fa-circle text-warning me-2"></i>English: 25%</li>
                            <li><i class="fas fa-circle text-info me-2"></i>Science: 25%</li>
                            <li><i class="fas fa-circle text-danger me-2"></i>Mathematics: 25%</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Formula:</h6>
                        <p class="small text-muted">
                            Exam Rating = (Gen Info × 10%) + (Filipino × 15%) + 
                            (English × 25%) + (Science × 25%) + (Math × 25%)
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Qualification:</h6>
                        <p class="small text-muted">
                            • Average rating of at least 75% in Admission Test<br>
                            • GWA of at least 80% or higher<br>
                            • 80% or better in Science and Mathematics
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateScores() {
            // Get raw scores
            const genInfo = parseFloat(document.getElementById('gen_info_raw').value) || 0;
            const filipino = parseFloat(document.getElementById('filipino_raw').value) || 0;
            const english = parseFloat(document.getElementById('english_raw').value) || 0;
            const science = parseFloat(document.getElementById('science_raw').value) || 0;
            const math = parseFloat(document.getElementById('math_raw').value) || 0;
            
            // Calculate total raw score
            const totalRaw = genInfo + filipino + english + science + math;
            
            // Calculate transmuted scores (percentage)
            const genInfoTransmuted = (genInfo / 30) * 100;
            const filipinoTransmuted = (filipino / 50) * 100;
            const englishTransmuted = (english / 60) * 100;
            const scienceTransmuted = (science / 60) * 100;
            const mathTransmuted = (math / 50) * 100;
            
            // Calculate weighted exam rating
            const examRating = (genInfoTransmuted * 0.10) + 
                              (filipinoTransmuted * 0.15) + 
                              (englishTransmuted * 0.25) + 
                              (scienceTransmuted * 0.25) + 
                              (mathTransmuted * 0.25);
            
            // Calculate percentage
            const percentage = (totalRaw / 250) * 100; // Total possible: 250
            
            // Update display
            document.getElementById('total_raw_score').textContent = totalRaw;
            document.getElementById('exam_rating').textContent = examRating.toFixed(2);
            document.getElementById('percentage').textContent = percentage.toFixed(2) + '%';
        }
        
        // Calculate on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateScores();
        });
    </script>
</body>
</html>
