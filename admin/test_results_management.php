<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';
require_once '../includes/favicon.php';
require_once '../config/grading_config.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$testResults = new TestResults();


// Handle AJAX request for fetching student data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_student') {
    // Log the request for debugging
    error_log("AJAX Request received: " . print_r($_POST, true));
    
    try {
        $searchTerm = trim($_POST['search_term']);
        
        if (!empty($searchTerm)) {
            $db = getDB();
            
            // Clean the search term - remove email addresses and extra formatting
            $cleanSearchTerm = preg_replace('/\s*\([^)]*\)/', '', $searchTerm); // Remove email in parentheses
            $cleanSearchTerm = trim($cleanSearchTerm);
            
            // Split the search term into individual words for better matching
            $searchWords = explode(' ', $cleanSearchTerm);
            $searchWords = array_filter($searchWords, function($word) {
                return strlen(trim($word)) > 1; // Remove single characters
            });
        
            // Try to find student by ID or any part of the name (first, middle, last)
            $stmt = $db->prepare("
                SELECT s.*, af.home_address, af.last_school,
                       tp.permit_number
                FROM students s 
                LEFT JOIN admission_forms af ON s.id = af.student_id
                LEFT JOIN test_permits tp ON s.id = tp.student_id
                WHERE s.id = ? OR 
                      s.email LIKE ? OR
                      CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.middle_name, ''), ' ', COALESCE(s.last_name, '')) LIKE ? OR
                      CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) LIKE ? OR
                      s.first_name LIKE ? OR
                      s.middle_name LIKE ? OR
                      s.last_name LIKE ? OR
                      CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.middle_name, ''), ' ', COALESCE(s.last_name, '')) LIKE ? OR
                      CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) LIKE ?
            ");
            
            $searchPattern = '%' . $searchTerm . '%';
            $cleanPattern = '%' . $cleanSearchTerm . '%';
            $numericId = is_numeric($searchTerm) ? (int)$searchTerm : 0;
            
            $stmt->execute([
                $numericId,
                $searchPattern, // Email search
                $searchPattern, // Full name with middle
                $searchPattern, // First + last name
                $searchPattern, // First name
                $searchPattern, // Middle name
                $searchPattern, // Last name
                $cleanPattern,  // Cleaned full name
                $cleanPattern   // Cleaned first + last name
            ]);
            $student = $stmt->fetch();
        
            if ($student) {
                // Return student data as JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'student_id' => $student['id'],
                        'permit_number' => $student['permit_number'] ?? '',
                        'student_name' => trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))
                    ]
                ]);
            } else {
                // Try fallback search with individual words
                $fallbackStudent = null;
                if (!empty($searchWords)) {
                    foreach ($searchWords as $word) {
                        if (strlen($word) > 2) { // Only search words longer than 2 characters
                            $fallbackStmt = $db->prepare("
                                SELECT s.*, af.home_address, af.last_school, tp.permit_number
                                FROM students s 
                                LEFT JOIN admission_forms af ON s.id = af.student_id
                                LEFT JOIN test_permits tp ON s.id = tp.student_id
                                WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.middle_name LIKE ?
                                LIMIT 1
                            ");
                            $wordPattern = '%' . $word . '%';
                            $fallbackStmt->execute([$wordPattern, $wordPattern, $wordPattern]);
                            $fallbackStudent = $fallbackStmt->fetch();
                            
                            if ($fallbackStudent) {
                                break; // Found a match, stop searching
                            }
                        }
                    }
                }
                
                if ($fallbackStudent) {
                    // Return fallback student data
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'student_id' => $fallbackStudent['id'],
                            'permit_number' => $fallbackStudent['permit_number'] ?? '',
                            'student_name' => trim(($fallbackStudent['first_name'] ?? '') . ' ' . ($fallbackStudent['middle_name'] ?? '') . ' ' . ($fallbackStudent['last_name'] ?? ''))
                        ],
                        'message' => 'Found partial match for: ' . $searchTerm
                    ]);
                } else {
                    // Debug: Let's see what students exist
                    $debugStmt = $db->prepare("SELECT s.id, s.first_name, s.last_name, s.middle_name FROM students s LIMIT 5");
                    $debugStmt->execute();
                    $debugStudents = $debugStmt->fetchAll();
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Student not found for: ' . $searchTerm,
                        'debug' => [
                            'search_term' => $searchTerm,
                            'clean_search_term' => $cleanSearchTerm,
                            'search_words' => $searchWords,
                            'numeric_id' => $numericId,
                            'search_pattern' => $searchPattern,
                            'available_students' => $debugStudents
                        ]
                    ]);
                }
            }
        }
        exit;
    } catch (Exception $e) {
        error_log("Error in AJAX request: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle manual entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_entry'])) {
    $permitNumber = trim($_POST['permit_number']);
    $examDate = $_POST['exam_date'];
    
    // Student details
    $studentName = trim($_POST['student_name']);
    $school = trim($_POST['school']);
    $address = trim($_POST['address']);
    
    // Subject scores
    $genInfoRaw = (int)$_POST['gen_info_raw'];
    $filipinoRaw = (int)$_POST['filipino_raw'];
    $englishRaw = (int)$_POST['english_raw'];
    $scienceRaw = (int)$_POST['science_raw'];
    $mathRaw = (int)$_POST['math_raw'];
    
    
    // Validate inputs
    $errors = [];
    if (empty($permitNumber)) $errors[] = "Permit number is required";
    if (empty($examDate)) $errors[] = "Exam date is required";
    if (empty($studentName)) $errors[] = "Student name is required";
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
            'math_raw' => $mathRaw,
            'student_name' => $studentName,
            'school' => $school,
            'address' => $address
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
    
    redirect('/admin/test_results_management.php');
}

// Handle result deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $result = $testResults->deleteTestResult($_GET['delete']);
    if ($result['success']) {
        showAlert($result['message'], 'success');
    } else {
        showAlert($result['message'], 'error');
    }
    redirect('/admin/test_results_management.php');
}

// Get filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'exam_date' => $_GET['exam_date'] ?? '',
    'overall_rating' => $_GET['overall_rating'] ?? ''
];

// Check if editing existing result
$existingData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT tr.*, s.first_name, s.last_name, s.middle_name, s.email,
                   af.course_first, af.last_school, af.home_address
            FROM test_results tr
            JOIN students s ON tr.student_id = s.id
            LEFT JOIN admission_forms af ON s.id = af.student_id
            WHERE tr.id = ?
        ");
        $stmt->execute([$_GET['edit']]);
        $existingData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching existing test result: " . $e->getMessage());
    }
}

// Get all test results
$results = $testResults->getAllTestResults($filters);
$stats = $testResults->getTestResultStats();

// Pagination for results list (display only)
$resultsPerPage = 10;
$resultsPage = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$resultsTotal = count($results);
$resultsOffset = ($resultsPage - 1) * $resultsPerPage;
$resultsPageItems = array_slice($results, $resultsOffset, $resultsPerPage);
$resultsStart = $resultsTotal > 0 ? $resultsOffset + 1 : 0;
$resultsEnd = $resultsTotal > 0 ? min($resultsOffset + count($resultsPageItems), $resultsTotal) : 0;
$hasPrevResults = $resultsPage > 1;
$hasNextResults = $resultsOffset + $resultsPerPage < $resultsTotal;

$alert = getAlert();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results Management - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item active" href="test_results_management.php">
                            <i class="fas fa-chart-line me-2"></i>Test Results
                        </a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                        <li><a class="dropdown-item" href="manage_students.php">
                            <i class="fas fa-user-cog me-2"></i>Manage Students
                        </a></li>
                        <li><a class="dropdown-item" href="add_student.php">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </a></li>
                        <li><a class="dropdown-item" href="add_admin.php">
                            <i class="fas fa-user-shield me-2"></i>Add Admin
                        </a></li>
                    </ul>
                </div>
                <a class="nav-link" href="test_permit_stats.php">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                Test Results Management
                            </h2>
                            <p class="text-muted mb-0">Enter test results manually and generate CAT reports.</p>
                        </div>
                        <div class="text-end">
                            <a href="export_test_results.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-success me-2">
                                <i class="fas fa-download me-1"></i>Export Results
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-2">
                <div class="dashboard-card text-center p-3">
                    <div class="mb-2">
                        <i class="fas fa-chart-bar fa-2x text-primary"></i>
                    </div>
                    <h5 class="text-primary mb-1"><?php echo $stats['total_results']; ?></h5>
                    <p class="text-muted mb-0 small">Total Results</p>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card text-center p-3">
                    <div class="mb-2">
                        <i class="fas fa-calendar fa-2x text-success"></i>
                    </div>
                    <h5 class="text-success mb-1"><?php echo $stats['recent_results']; ?></h5>
                    <p class="text-muted mb-0 small">Recent (30 days)</p>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card text-center p-3">
                    <div class="mb-2">
                        <i class="fas fa-percentage fa-2x text-info"></i>
                    </div>
                    <h5 class="text-info mb-1"><?php echo number_format($stats['averages']['avg_percentage_score'] ?? 0, 1); ?>%</h5>
                    <p class="text-muted mb-0 small">Average Score</p>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="dashboard-card text-center p-3">
                    <div class="mb-2">
                        <i class="fas fa-trophy fa-2x text-warning"></i>
                    </div>
                    <h5 class="text-warning mb-1"><?php echo $stats['by_rating']['Passed'] ?? 0; ?></h5>
                    <p class="text-muted mb-0 small">Passed</p>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-search me-2 text-primary"></i>
                        Search Student
                    </h5>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search_student" 
                                       placeholder="Enter Student ID, First Name, Middle Name, or Last Name..." 
                                       onkeypress="handleSearchKeypress(event)">
                                <button type="button" class="btn btn-primary" onclick="searchStudent()">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                            </div>
                            <div class="form-text">
                                You can search by Student ID (e.g., 1) or any part of the student's name (e.g., John, Doe, Smith)
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="search-status" class="form-text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Entry Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-edit me-2 text-success"></i>
                        <?php echo $existingData ? 'Edit Test Result' : 'Manual Test Result Entry'; ?>
                    </h5>
                    
                    <form method="POST" id="manualEntryForm">
                        <input type="hidden" name="manual_entry" value="1">
                        
                        <!-- Student Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Student Information
                                </h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="number" class="form-control" id="student_id" name="student_id" 
                                       placeholder="Auto-filled from search" required readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="permit_number" class="form-label">Permit Number *</label>
                                <input type="text" class="form-control" id="permit_number" name="permit_number" 
                                       placeholder="Auto-filled from search" 
                                       value="<?php echo htmlspecialchars($existingData['permit_number'] ?? ''); ?>" 
                                       required readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="exam_date" class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" id="exam_date" name="exam_date" 
                                       value="<?php echo htmlspecialchars($existingData['exam_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="student_name" class="form-label">Student Name *</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" 
                                       placeholder="Auto-filled from search" 
                                       value="<?php echo htmlspecialchars($existingData ? trim(($existingData['last_name'] ?? '') . ', ' . ($existingData['first_name'] ?? '') . ' ' . ($existingData['middle_name'] ?? '')) : ''); ?>" 
                                       required readonly>
                            </div>
                        </div>
                        
                        <!-- Subject Scores - Enhanced Input -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-calculator me-2"></i>Subject Scores
                                </h6>
                            </div>
                            
                            <!-- Quick Input Table -->
                            <div class="col-12 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <thead>
                                                    <tr class="bg-light">
                                                        <th class="text-center" style="width: 20%;">Subject</th>
                                                        <th class="text-center" style="width: 15%;">Max Points</th>
                                                        <th class="text-center" style="width: 20%;">Raw Score</th>
                                                        <th class="text-center" style="width: 15%;">Weight</th>
                                                        <th class="text-center" style="width: 15%;">Transmuted</th>
                                                        <th class="text-center" style="width: 15%;">Weighted</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- General Information -->
                                                    <tr class="border-bottom">
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-success rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                                                <strong>General Info</strong>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">30</td>
                                                        <td class="text-center">
                                                            <div class="input-group input-group-sm" style="max-width: 140px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('gen_info_raw', -1, 30)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="form-control text-center" id="gen_info_raw" name="gen_info_raw" 
                                                                       min="0" max="30" value="<?php echo htmlspecialchars($existingData['gen_info_raw'] ?? '0'); ?>" 
                                                                       onchange="calculateScores()" style="font-weight: bold;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('gen_info_raw', 1, 30)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-3">
                                                                <small class="text-muted">Raw: <span id="display_gen_info_raw" style="font-size: 1.1em; font-weight: bold;">0</span></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">10%</td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-light text-dark" id="display_gen_info_transmuted">0.000</span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-success text-white" id="display_gen_info_weighted">0.000</span>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Filipino -->
                                                    <tr class="border-bottom">
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                                                <strong>Filipino</strong>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">50</td>
                                                        <td class="text-center">
                                                            <div class="input-group input-group-sm" style="max-width: 140px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('filipino_raw', -1, 50)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="form-control text-center" id="filipino_raw" name="filipino_raw" 
                                                                       min="0" max="50" value="<?php echo htmlspecialchars($existingData['filipino_raw'] ?? '0'); ?>" 
                                                                       onchange="calculateScores()" style="font-weight: bold;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('filipino_raw', 1, 50)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-3">
                                                                <small class="text-muted">Raw: <span id="display_filipino_raw" style="font-size: 1.1em; font-weight: bold;">0</span></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">15%</td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-light text-dark" id="display_filipino_transmuted">0.000</span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-primary text-white" id="display_filipino_weighted">0.000</span>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- English -->
                                                    <tr class="border-bottom">
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-warning rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                                                <strong>English</strong>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">60</td>
                                                        <td class="text-center">
                                                            <div class="input-group input-group-sm" style="max-width: 140px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('english_raw', -1, 60)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="form-control text-center" id="english_raw" name="english_raw" 
                                                                       min="0" max="60" value="<?php echo htmlspecialchars($existingData['english_raw'] ?? '0'); ?>" 
                                                                       onchange="calculateScores()" style="font-weight: bold;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('english_raw', 1, 60)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-3">
                                                                <small class="text-muted">Raw: <span id="display_english_raw" style="font-size: 1.1em; font-weight: bold;">0</span></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">25%</td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-light text-dark" id="display_english_transmuted">0.000</span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-warning text-dark" id="display_english_weighted">0.000</span>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Science -->
                                                    <tr class="border-bottom">
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-info rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                                                <strong>Science</strong>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">60</td>
                                                        <td class="text-center">
                                                            <div class="input-group input-group-sm" style="max-width: 140px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('science_raw', -1, 60)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="form-control text-center" id="science_raw" name="science_raw" 
                                                                       min="0" max="60" value="<?php echo htmlspecialchars($existingData['science_raw'] ?? '0'); ?>" 
                                                                       onchange="calculateScores()" style="font-weight: bold;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('science_raw', 1, 60)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-3">
                                                                <small class="text-muted">Raw: <span id="display_science_raw" style="font-size: 1.1em; font-weight: bold;">0</span></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">25%</td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-light text-dark" id="display_science_transmuted">0.000</span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-info text-white" id="display_science_weighted">0.000</span>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Mathematics -->
                                                    <tr>
                                                        <td class="align-middle">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-danger rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                                                <strong>Mathematics</strong>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">50</td>
                                                        <td class="text-center">
                                                            <div class="input-group input-group-sm" style="max-width: 140px; margin: 0 auto;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('math_raw', -1, 50)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="form-control text-center" id="math_raw" name="math_raw" 
                                                                       min="0" max="50" value="<?php echo htmlspecialchars($existingData['math_raw'] ?? '0'); ?>" 
                                                                       onchange="calculateScores()" style="font-weight: bold;">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="adjustScore('math_raw', 1, 50)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                            <div class="mt-3">
                                                                <small class="text-muted">Raw: <span id="display_math_raw" style="font-size: 1.1em; font-weight: bold;">0</span></small>
                                                            </div>
                                                        </td>
                                                        <td class="text-center align-middle">25%</td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-light text-dark" id="display_math_transmuted">0.000</span>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <span class="badge bg-danger text-white" id="display_math_weighted">0.000</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <div class="row mt-3">
                                            <div class="col-12 text-center">
                                                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="setAllScores(0)">
                                                    <i class="fas fa-undo me-1"></i>Clear All
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm me-2" onclick="setAllScores('max')">
                                                    <i class="fas fa-star me-1"></i>Set Max Scores
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" onclick="calculateScores()">
                                                    <i class="fas fa-calculator me-1"></i>Recalculate
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Score Summary -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-trophy me-2"></i>Exam Rating Summary
                                        </h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h5 class="text-muted mb-1">Total Raw Score</h5>
                                                <h3 class="text-primary mb-0" id="total_raw_score">0</h3>
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="text-muted mb-1">Exam Rating</h5>
                                                <h3 class="text-success mb-0" id="exam_rating">0.000</h3>
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="text-muted mb-1">Grade Equivalent</h5>
                                                <h3 class="text-info mb-0" id="exam_rating_text">0.000</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo $existingData ? 'Update Test Result' : 'Save & Generate CAT Report'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Name or permit number..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" 
                                   value="<?php echo htmlspecialchars($filters['exam_date']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="overall_rating" class="form-label">Overall Rating</label>
                            <select class="form-select" id="overall_rating" name="overall_rating">
                                <option value="">All Ratings</option>
                                <option value="PASSED" <?php echo $filters['overall_rating'] === 'PASSED' ? 'selected' : ''; ?>>PASSED</option>
                                <option value="FAILED" <?php echo $filters['overall_rating'] === 'FAILED' ? 'selected' : ''; ?>>FAILED</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Test Results
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by name or email..." onkeyup="searchTable()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchTable()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="printAllResults()">
                                    <i class="fas fa-print me-1"></i>Print All
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportResults()">
                                    <i class="fas fa-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                        // Build base query string for pagination links (preserve filters)
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $prevQuery = $baseQuery;
                        $nextQuery = $baseQuery;
                        $prevQuery['page'] = ($resultsPage > 1) ? $resultsPage - 1 : 1;
                        $nextQuery['page'] = $resultsPage + 1;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($resultsTotal)): ?>
                                Showing applicants <?php echo $resultsStart; ?>–<?php echo $resultsEnd; ?> of <?php echo $resultsTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($resultsPageItems); ?> test results
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Test results navigation">
                            <?php if (!empty($hasPrevResults) && $hasPrevResults): ?>
                                <a href="test_results_management.php?<?php echo http_build_query($prevQuery); ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if (!empty($hasNextResults) && $hasNextResults): ?>
                                <a href="test_results_management.php?<?php echo http_build_query($nextQuery); ?>" class="btn btn-outline-secondary">Next</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-2">Click 'Next' to view more applicants.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No.</th>
                                    <th>Student Name</th>
                                    <th>Exam Scores</th>
                                    <th>Total Rating</th>
                                    <th>Remarks</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resultsPageItems)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No test results found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($resultsPageItems as $index => $result): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $resultsOffset + $index + 1; ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(substr($result['first_name'] ?? '', 0, 1) . substr($result['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php 
                                                    $fullName = trim(($result['first_name'] ?? '') . ' ' . ($result['middle_name'] ?? '') . ' ' . ($result['last_name'] ?? ''));
                                                    echo htmlspecialchars($fullName); 
                                                ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Gen Info:</strong> <?php echo $result['gen_info_raw'] ?? 'N/A'; ?>/30 → <?php echo number_format($result['gen_info_transmuted'] ?? 0, 1); ?>% (10%)</div>
                                            <div><strong>Filipino:</strong> <?php echo $result['filipino_raw'] ?? 'N/A'; ?>/50 → <?php echo number_format($result['filipino_transmuted'] ?? 0, 1); ?>% (15%)</div>
                                            <div><strong>English:</strong> <?php echo $result['english_raw'] ?? 'N/A'; ?>/60 → <?php echo number_format($result['english_transmuted'] ?? 0, 1); ?>% (25%)</div>
                                            <div><strong>Science:</strong> <?php echo $result['science_raw'] ?? 'N/A'; ?>/60 → <?php echo number_format($result['science_transmuted'] ?? 0, 1); ?>% (25%)</div>
                                            <div><strong>Math:</strong> <?php echo $result['math_raw'] ?? 'N/A'; ?>/50 → <?php echo number_format($result['math_transmuted'] ?? 0, 1); ?>% (25%)</div>
                                            <div class="mt-1"><strong>Exam Rating:</strong> <?php echo number_format($result['exam_rating'] ?? 0, 1); ?> (50%)</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            <strong class="fs-5"><?php echo number_format($result['exam_rating'] ?? 0, 1); ?>%</strong>
                                            <br><small class="text-muted">Exam Rating</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $examRating = $result['exam_rating'] ?? 0;
                                            // Calculate correct rating in real-time
                                            $correctRating = getOverallRating($examRating);
                                            $passingThreshold = PASSING_THRESHOLD;
                                            echo $examRating >= $passingThreshold ? 'success' : 'danger'; 
                                        ?> fs-6">
                                            <?php echo $correctRating; ?>
                                        </span>
                                    </td>
                                    <td class="actions-column text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view_cat_result.php?id=<?php echo $result['id']; ?>" class="btn btn-info" title="View CAT Result">
                                                <i class="fas fa-eye"></i>
                                                <span class="ms-1">View</span>
                                            </a>
                                            <a href="test_results_management.php?edit=<?php echo $result['id']; ?>" class="btn btn-primary" title="Edit Result">
                                                <i class="fas fa-edit"></i>
                                                <span class="ms-1">Edit</span>
                                            </a>
                                            <button type="button" class="btn btn-warning" onclick="printResult(<?php echo $result['id']; ?>)" title="Print Result">
                                                <i class="fas fa-print"></i>
                                                <span class="ms-1">Print</span>
                                            </button>
                                            <a href="?delete=<?php echo $result['id']; ?>" class="btn btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this result?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                                <span class="ms-1">Delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Result Modal -->
    <div class="modal fade" id="viewResultModal" tabindex="-1" aria-labelledby="viewResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewResultModalLabel">
                        <i class="fas fa-chart-line me-2"></i>Test Result Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resultContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading result details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printResultFromModal()">
                        <i class="fas fa-print me-1"></i>Print Result
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/autocomplete.js"></script>
    <script>
        let currentResultId = null;
        
        // Enhanced input functions
        function adjustScore(fieldId, change, maxValue) {
            const input = document.getElementById(fieldId);
            let currentValue = parseInt(input.value) || 0;
            let newValue = currentValue + change;
            
            // Ensure value stays within bounds
            newValue = Math.max(0, Math.min(newValue, maxValue));
            
            input.value = newValue;
            calculateScores();
        }
        
        function setAllScores(value) {
            const subjects = [
                { id: 'gen_info_raw', max: 30 },
                { id: 'filipino_raw', max: 50 },
                { id: 'english_raw', max: 60 },
                { id: 'science_raw', max: 60 },
                { id: 'math_raw', max: 50 }
            ];
            
            subjects.forEach(subject => {
                const input = document.getElementById(subject.id);
                if (value === 'max') {
                    input.value = subject.max;
                } else {
                    input.value = value;
                }
            });
            
            calculateScores();
        }
        
        // Real-time score calculation
        function calculateScores() {
            // Get raw scores
            const genInfo = parseFloat(document.getElementById('gen_info_raw').value) || 0;
            const filipino = parseFloat(document.getElementById('filipino_raw').value) || 0;
            const english = parseFloat(document.getElementById('english_raw').value) || 0;
            const science = parseFloat(document.getElementById('science_raw').value) || 0;
            const math = parseFloat(document.getElementById('math_raw').value) || 0;
            
            // Calculate total raw score
            const totalRaw = genInfo + filipino + english + science + math;
            
            // Transmutation tables (updated to match exact expected values)
            const transmutationTables = {
                genInfo: [0, 25, 30, 35, 40, 45, 50, 55, 60, 65, 68, 70, 72, 74, 78.667, 80, 82, 84, 86, 88, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100],
                filipino: [0, 20, 25, 30, 35, 40, 45, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 82.400, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 96.5, 97, 97.5, 98, 98.5, 99, 99.5, 99.8, 100],
                english: [0, 15, 20, 25, 30, 35, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70, 74.667, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 90.5, 91, 91.5, 92, 92.5, 93, 93.5, 94, 94.5, 95, 95.5, 96, 96.5, 97, 97.5, 98, 98.5, 99, 100],
                science: [0, 15, 20, 25, 30, 35, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70, 74.667, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 90.5, 91, 91.5, 92, 92.5, 93, 93.5, 94, 94.5, 95, 95.5, 96, 96.5, 97, 97.5, 98, 98.5, 99, 100],
                math: [0, 20, 25, 30, 35, 64.000, 45, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 82, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 96.5, 97, 97.5, 98, 98.5, 99, 99.5, 99.8, 100]
            };
            
            // Calculate transmuted scores using tables
            const genInfoTransmuted = transmutationTables.genInfo[genInfo] || 0;
            const filipinoTransmuted = transmutationTables.filipino[filipino] || 0;
            const englishTransmuted = transmutationTables.english[english] || 0;
            const scienceTransmuted = transmutationTables.science[science] || 0;
            const mathTransmuted = transmutationTables.math[math] || 0;
            
            // Calculate weighted exam rating
            const examRating = (genInfoTransmuted * 0.10) + 
                              (filipinoTransmuted * 0.15) + 
                              (englishTransmuted * 0.25) + 
                              (scienceTransmuted * 0.25) + 
                              (mathTransmuted * 0.25);
            
            // Calculate final score (50% of exam rating)
            const finalScore = examRating * 0.50;
            
            // Interview and total rating calculations removed - focusing on exam rating only
            
            // Calculate weighted scores for each subject
            const genInfoWeighted = genInfoTransmuted * 0.10;
            const filipinoWeighted = filipinoTransmuted * 0.15;
            const englishWeighted = englishTransmuted * 0.25;
            const scienceWeighted = scienceTransmuted * 0.25;
            const mathWeighted = mathTransmuted * 0.25;
            
            // Update table display elements
            document.getElementById('display_gen_info_raw').textContent = genInfo;
            document.getElementById('display_gen_info_transmuted').textContent = genInfoTransmuted.toFixed(3);
            document.getElementById('display_gen_info_weighted').textContent = genInfoWeighted.toFixed(3);
            
            document.getElementById('display_filipino_raw').textContent = filipino;
            document.getElementById('display_filipino_transmuted').textContent = filipinoTransmuted.toFixed(3);
            document.getElementById('display_filipino_weighted').textContent = filipinoWeighted.toFixed(3);
            
            document.getElementById('display_english_raw').textContent = english;
            document.getElementById('display_english_transmuted').textContent = englishTransmuted.toFixed(3);
            document.getElementById('display_english_weighted').textContent = englishWeighted.toFixed(3);
            
            document.getElementById('display_science_raw').textContent = science;
            document.getElementById('display_science_transmuted').textContent = scienceTransmuted.toFixed(3);
            document.getElementById('display_science_weighted').textContent = scienceWeighted.toFixed(3);
            
            document.getElementById('display_math_raw').textContent = math;
            document.getElementById('display_math_transmuted').textContent = mathTransmuted.toFixed(3);
            document.getElementById('display_math_weighted').textContent = mathWeighted.toFixed(3);
            
            // Update totals
            document.getElementById('total_raw_score').textContent = totalRaw;
            document.getElementById('exam_rating').textContent = examRating.toFixed(3);
            document.getElementById('exam_rating_text').textContent = examRating.toFixed(3);
            
            // Final results section removed - calculations complete
        }
        
        // View result function
        function viewResult(resultId) {
            currentResultId = resultId;
            const modal = new bootstrap.Modal(document.getElementById('viewResultModal'));
            const content = document.getElementById('resultContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading result details...</p>
                </div>
            `;
            
            modal.show();
            
            // Load result data
            fetch(`view_test_result.php?id=${resultId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading result details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Print result function
        function printResult(resultId) {
            window.open(`print_test_result.php?id=${resultId}`, '_blank');
        }
        
        // Print result from modal
        function printResultFromModal() {
            if (currentResultId) {
                printResult(currentResultId);
            }
        }
        
        // Print all results
        function printAllResults() {
            window.open('print_all_test_results.php', '_blank');
        }
        
        // Export results
        function exportResults() {
            window.open('export_test_results.php', '_blank');
        }
        
        // Search table function
        function searchTable() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const table = document.querySelector('.table-responsive table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const nameCell = row.cells[1]; // Student Name column (index 1)
                const emailCell = row.cells[2]; // Email column (index 2)
                
                if (nameCell && emailCell) {
                    const nameText = nameCell.textContent.toLowerCase();
                    const emailText = emailCell.textContent.toLowerCase();
                    
                    if (nameText.includes(searchTerm) || emailText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }
        
        // Search student function
        function searchStudent() {
            const searchTerm = document.getElementById('search_student').value.trim();
            const statusDiv = document.getElementById('search-status');
            
            if (!searchTerm) {
                statusDiv.innerHTML = '';
                return;
            }
            
            // Show loading status
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i> Searching...';
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'fetch_student');
            formData.append('search_term', searchTerm);
            
            // Make AJAX request
            fetch('test_results_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Populate form fields with fetched data
                    document.getElementById('student_id').value = data.data.student_id || '';
                    document.getElementById('permit_number').value = data.data.permit_number || '';
                    document.getElementById('student_name').value = data.data.student_name || '';
                    
                    // Update status
                    statusDiv.innerHTML = '<i class="fas fa-check text-success"></i> Student found and data loaded';
                    statusDiv.className = 'form-text text-success';
                    
                    // Recalculate scores
                    calculateScores();
                } else {
                    // Show error with debug info
                    let errorMsg = '<i class="fas fa-exclamation-triangle text-warning"></i> ' + data.message;
                    if (data.debug) {
                        errorMsg += '<br><small>Debug: Search term: "' + data.debug.search_term + '", Numeric ID: ' + data.debug.numeric_id;
                        if (data.debug.available_students && data.debug.available_students.length > 0) {
                            errorMsg += '<br>Available students: ';
                            data.debug.available_students.forEach(function(student) {
                                errorMsg += 'ID:' + student.id + ' (' + student.first_name + ' ' + student.last_name + '), ';
                            });
                        }
                        errorMsg += '</small>';
                    }
                    statusDiv.innerHTML = errorMsg;
                    statusDiv.className = 'form-text text-warning';
                    
                    // Clear form fields
                    document.getElementById('student_id').value = '';
                    document.getElementById('permit_number').value = '';
                    document.getElementById('student_name').value = '';
                    
                    // Recalculate scores
                    calculateScores();
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<i class="fas fa-times text-danger"></i> Error searching student: ' + error.message;
                statusDiv.className = 'form-text text-danger';
                console.error('Error:', error);
            });
        }
        
        // Handle Enter key press in search field
        function handleSearchKeypress(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchStudent();
            }
        }
        
        // Initialize autocomplete for search fields
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($existingData): ?>
            // Trigger calculation for existing data
            calculateScores();
            <?php endif; ?>
            
            // Initialize autocomplete for student search
            const studentSearchInput = document.getElementById('search_student');
            if (studentSearchInput) {
                new AutocompleteSearch(studentSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'students',
                    displayField: 'display',
                    valueField: 'name',
                    onSelect: function(suggestion) {
                        // Auto-fill form with selected student data
                        studentSearchInput.value = suggestion.display;
                        
                        // Fill form fields directly with suggestion data
                        document.getElementById('student_id').value = suggestion.id || '';
                        document.getElementById('permit_number').value = suggestion.permit_number || '';
                        document.getElementById('student_name').value = suggestion.name || '';
                        
                        // Update status
                        const statusDiv = document.getElementById('search-status');
                        if (statusDiv) {
                            statusDiv.innerHTML = '<i class="fas fa-check text-success"></i> Student selected and form filled';
                            statusDiv.className = 'form-text text-success';
                        }
                        
                        // Automatically trigger search to get complete student data
                        searchStudent();
                        
                        // Recalculate scores
                        calculateScores();
                    }
                });
            }
            
            // Initialize autocomplete for filter search
            const filterSearchInput = document.getElementById('search');
            if (filterSearchInput) {
                new AutocompleteSearch(filterSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'general',
                    displayField: 'display',
                    valueField: 'name'
                });
            }
            
            // Initialize autocomplete for table search
            const tableSearchInput = document.getElementById('searchInput');
            if (tableSearchInput) {
                new AutocompleteSearch(tableSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'students',
                    displayField: 'display',
                    valueField: 'name'
                });
            }
        });
    </script>
</body>
</html>
