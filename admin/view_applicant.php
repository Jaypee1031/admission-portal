<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';
require_once '../includes/requirements.php';
require_once '../includes/admission_form.php';
require_once '../includes/test_permit.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$db = getDB();
$alert = getAlert();

// Get student ID from URL
$studentId = (int)($_GET['id'] ?? 0);
if (!$studentId) {
    showAlert('Invalid student ID', 'error');
    redirect('applicants.php');
}

// Get student details with additional information
try {
    $stmt = $db->prepare("
        SELECT s.*, 
               CONCAT(s.first_name, 
                      CASE WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                           THEN CONCAT(' ', s.middle_name, ' ') 
                           ELSE ' ' END, 
                      s.last_name) as full_name,
               af.home_address, af.birth_date, af.birth_place, af.mobile_number,
               af.father_name, af.mother_name, af.guardian_name,
               af.last_school, af.course_first, af.course_second, af.course_third
        FROM students s 
        LEFT JOIN admission_forms af ON s.id = af.student_id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        showAlert('Student not found', 'error');
        redirect('applicants.php');
    }
} catch (PDOException $e) {
    showAlert('Database error: ' . $e->getMessage(), 'error');
    redirect('applicants.php');
}

// Get admission form details
try {
    $stmt = $db->prepare("SELECT * FROM admission_forms WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $admissionForm = $stmt->fetch();
} catch (PDOException $e) {
    $admissionForm = null;
}

// Get requirements
try {
    $stmt = $db->prepare("SELECT * FROM requirements WHERE student_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$studentId]);
    $requirements = $stmt->fetchAll();
} catch (PDOException $e) {
    $requirements = [];
}

// Check for uploaded files in the file system
$uploadedFiles = [];
$uploadsDir = '../uploads/requirements/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && strpos($file, $studentId . '_') === 0) {
            $uploadedFiles[] = $file;
        }
    }
}

// Get test permit
try {
    $stmt = $db->prepare("SELECT * FROM test_permits WHERE student_id = ? ORDER BY issued_at DESC LIMIT 1");
    $stmt->execute([$studentId]);
    $testPermit = $stmt->fetch();
} catch (PDOException $e) {
    $testPermit = null;
}

// Get F2 Personal Data Form
try {
    $stmt = $db->prepare("SELECT * FROM f2_personal_data_forms WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $f2Form = $stmt->fetch();
} catch (PDOException $e) {
    $f2Form = null;
}

// Get test results
try {
    $stmt = $db->prepare("SELECT * FROM test_results WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $testResults = $stmt->fetch();
} catch (PDOException $e) {
    $testResults = null;
}

// Calculate progress using the same logic as student dashboard
$auth = new Auth();
$studentProgress = $auth->getStudentProgress($studentId);

// Calculate progress percentage (same as student dashboard)
$totalSteps = 7; // Registration, Requirements, Admission Form, Test Permit, Exam, Personal Data, Test Results
$completedSteps = 1; // Registration is always completed if logged in
if ($studentProgress['requirements']) $completedSteps++;
if ($studentProgress['admission_form']) $completedSteps++;
if ($studentProgress['test_permit_approved']) $completedSteps++;
if ($studentProgress['exam_completed'] || $studentProgress['test_results_available']) $completedSteps++;
if ($studentProgress['f2_form_completed']) $completedSteps++;
if ($studentProgress['test_results_available']) $completedSteps++;

$progress = $completedSteps;
$progressPercentage = ($completedSteps / $totalSteps) * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicant - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .clickable:hover {
            background-color: #f8f9fa !important;
            transition: background-color 0.2s ease;
        }
        
        .clickable:active {
            background-color: #e9ecef !important;
        }
        
        .status-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .status-verified {
            background-color: #17a2b8 !important;
            color: #fff !important;
        }
        
        .status-approved {
            background-color: #28a745 !important;
            color: #fff !important;
        }
        
        .status-rejected {
            background-color: #dc3545 !important;
            color: #fff !important;
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
                <a class="nav-link" href="applicants.php">
                    <i class="fas fa-users me-1"></i>Applicants
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
                                <i class="fas fa-user me-2 text-primary"></i>
                                Student Details
                            </h2>
                            <p class="text-muted mb-0">View complete information for <?php echo htmlspecialchars($student['full_name']); ?></p>
                        </div>
                        <div>
                            <a href="applicants.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Applicants
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Student Overview -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-user-circle me-2 text-primary"></i>
                        Basic Information
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                            <p><strong>Student Type:</strong> 
                                <span class="badge bg-info"><?php echo $student['type']; ?></span>
                            </p>
                            <p><strong>Status:</strong> 
                                <?php 
                                $status = $student['status'] ?? 'Pending';
                                $statusClass = strtolower($status);
                                ?>
                                <span class="badge status-<?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </p>
                            <p><strong>Student ID:</strong> #<?php echo $student['id']; ?></p>
                            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($student['mobile_number'] ?? $student['phone_number'] ?? 'N/A'); ?></p>
                            <p><strong>Birth Date:</strong> <?php echo $student['birth_date'] ? date('F d, Y', strtotime($student['birth_date'])) : 'N/A'; ?></p>
                            <p><strong>Birth Place:</strong> <?php echo htmlspecialchars($student['birth_place'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Registration Date:</strong> <?php echo $student['created_at'] ? date('F d, Y', strtotime($student['created_at'])) : 'N/A'; ?></p>
                            <p><strong>Last Updated:</strong> <?php echo ($student['updated_at'] ?? null) ? date('F d, Y', strtotime($student['updated_at'])) : 'N/A'; ?></p>
                            <p><strong>Requirements Submitted:</strong> <?php echo count($requirements); ?></p>
                            <p><strong>Application Progress:</strong> <?php echo $progress; ?>/<?php echo $totalSteps; ?> steps</p>
                            <p><strong>Last Login:</strong> Never</p>
                            <p><strong>Account Status:</strong> 
                                <span class="badge bg-success">Active</span>
                            </p>
                            <p><strong>Home Address:</strong> <?php echo htmlspecialchars($student['home_address'] ?? 'N/A'); ?></p>
                            <p><strong>Last School:</strong> <?php echo htmlspecialchars($student['last_school'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Course Preferences -->
                    <?php if ($student['course_first'] || $student['course_second'] || $student['course_third']): ?>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Course Preferences</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>1st Choice:</strong> <?php echo htmlspecialchars($student['course_first'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>2nd Choice:</strong> <?php echo htmlspecialchars($student['course_second'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>3rd Choice:</strong> <?php echo htmlspecialchars($student['course_third'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Family Information -->
                    <?php if ($student['father_name'] || $student['mother_name'] || $student['guardian_name']): ?>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Family Information</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Father:</strong> <?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Mother:</strong> <?php echo htmlspecialchars($student['mother_name'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Guardian:</strong> <?php echo htmlspecialchars($student['guardian_name'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2 text-success"></i>
                        Progress Overview
                    </h5>
                    <div class="text-center mb-3">
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $progressPercentage; ?>%">
                                <?php echo round($progressPercentage); ?>%
                            </div>
                        </div>
                        <small class="text-muted"><?php echo $progress; ?> of <?php echo $totalSteps; ?> steps completed</small>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center clickable" 
                             onclick="scrollToTab('requirements-tab')" style="cursor: pointer;">
                            <span><i class="fas fa-file-upload me-2"></i>Requirements</span>
                            <span class="badge <?php echo $studentProgress['requirements'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $studentProgress['requirements'] ? 'Completed' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center clickable" 
                             onclick="scrollToTab('admission-tab')" style="cursor: pointer;">
                            <span><i class="fas fa-file-alt me-2"></i>Admission Form</span>
                            <span class="badge <?php echo $studentProgress['admission_form'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $studentProgress['admission_form'] ? 'Completed' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center clickable" 
                             onclick="scrollToTab('test-permit-tab')" style="cursor: pointer;">
                            <span><i class="fas fa-id-card me-2"></i>Test Permit</span>
                            <span class="badge <?php echo $studentProgress['test_permit_approved'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $studentProgress['test_permit_approved'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center clickable" 
                             onclick="scrollToTab('personal-data-tab')" style="cursor: pointer;">
                            <span><i class="fas fa-user-edit me-2"></i>Personal Data Form</span>
                            <span class="badge <?php echo $studentProgress['f2_form_completed'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $studentProgress['f2_form_completed'] ? 'Completed' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center clickable" 
                             onclick="scrollToTab('test-results-tab')" style="cursor: pointer;">
                            <span><i class="fas fa-chart-bar me-2"></i>Test Results</span>
                            <span class="badge <?php echo $studentProgress['test_results_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $studentProgress['test_results_available'] ? 'Available' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <ul class="nav nav-tabs" id="detailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="admission-tab" data-bs-toggle="tab" data-bs-target="#admission" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Admission Form
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-permit-tab" data-bs-toggle="tab" data-bs-target="#test-permit" type="button" role="tab">
                                <i class="fas fa-id-card me-2"></i>Test Permit
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab">
                                <i class="fas fa-file-upload me-2"></i>Requirements (<?php echo count($requirements); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="personal-data-tab" data-bs-toggle="tab" data-bs-target="#personal-data" type="button" role="tab">
                                <i class="fas fa-user-edit me-2"></i>Personal Data Form
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-results-tab" data-bs-toggle="tab" data-bs-target="#test-results" type="button" role="tab">
                                <i class="fas fa-chart-bar me-2"></i>Test Results
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="detailTabsContent">
                        <!-- Admission Form Tab -->
                        <div class="tab-pane fade show active" id="admission" role="tabpanel">
                            <div class="p-4">
                                <?php if ($admissionForm): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Personal Information</h6>
                                        <p><strong>First Name:</strong> <?php echo htmlspecialchars($admissionForm['first_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Middle Name:</strong> <?php echo htmlspecialchars($admissionForm['middle_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($admissionForm['last_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Birth Date:</strong> <?php echo $admissionForm['birth_date'] ? date('F d, Y', strtotime($admissionForm['birth_date'])) : 'N/A'; ?></p>
                                        <p><strong>Sex:</strong> <?php echo htmlspecialchars($admissionForm['sex'] ?? 'N/A'); ?></p>
                                        <p><strong>Mobile Number:</strong> <?php echo htmlspecialchars($admissionForm['mobile_number'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Contact Information</h6>
                                        <p><strong>Home Address:</strong> <?php echo htmlspecialchars($admissionForm['home_address'] ?? 'N/A'); ?></p>
                                        <p><strong>Birth Place:</strong> <?php echo htmlspecialchars($admissionForm['birth_place'] ?? 'N/A'); ?></p>
                                        <p><strong>Ethnic Affiliation:</strong> <?php echo htmlspecialchars($admissionForm['ethnic_affiliation'] ?? 'N/A'); ?></p>
                                        <p><strong>Civil Status:</strong> <?php echo htmlspecialchars($admissionForm['civil_status'] ?? 'N/A'); ?></p>
                                        <p><strong>Disability:</strong> <?php echo htmlspecialchars($admissionForm['disability'] ?? 'None'); ?></p>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Family Information</h6>
                                        <p><strong>Father's Name:</strong> <?php echo htmlspecialchars($admissionForm['father_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Father's Occupation:</strong> <?php echo htmlspecialchars($admissionForm['father_occupation'] ?? 'N/A'); ?></p>
                                        <p><strong>Mother's Name:</strong> <?php echo htmlspecialchars($admissionForm['mother_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Mother's Occupation:</strong> <?php echo htmlspecialchars($admissionForm['mother_occupation'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Educational Background</h6>
                                        <p><strong>Last School:</strong> <?php echo htmlspecialchars($admissionForm['last_school'] ?? 'N/A'); ?></p>
                                        <p><strong>School Address:</strong> <?php echo htmlspecialchars($admissionForm['school_address'] ?? 'N/A'); ?></p>
                                        <p><strong>Overall GWA:</strong> <?php echo htmlspecialchars($admissionForm['overall_gwa'] ?? 'N/A'); ?></p>
                                        <p><strong>Course Choices:</strong> <?php echo htmlspecialchars($admissionForm['course_choices'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Guardian Information</h6>
                                        <p><strong>Guardian Name:</strong> <?php echo htmlspecialchars($admissionForm['guardian_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Guardian Contact:</strong> <?php echo htmlspecialchars($admissionForm['guardian_contact'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Form Details</h6>
                                        <p><strong>Submitted:</strong> <?php echo $admissionForm['created_at'] ? date('F d, Y g:i A', strtotime($admissionForm['created_at'])) : 'N/A'; ?></p>
                                        <p><strong>Last Updated:</strong> <?php echo ($admissionForm['updated_at'] ?? null) ? date('F d, Y g:i A', strtotime($admissionForm['updated_at'])) : 'N/A'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="../view_pdf.php?student_id=<?php echo $studentId; ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-pdf me-2"></i>View PDF
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Admission Form Submitted</h5>
                                    <p class="text-muted">The student has not yet submitted their admission form.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Requirements Tab -->
                        <div class="tab-pane fade" id="requirements" role="tabpanel">
                            <div class="p-4">
                                <?php if (!empty($requirements) || !empty($uploadedFiles)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Document Type</th>
                                                <th>File Name</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Reviewed</th>
                                                <th>Remarks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requirements as $req): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($req['document_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars(basename($req['file_path'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $req['status'] === 'Approved' ? 'bg-success' : ($req['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                                        <?php echo $req['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($req['uploaded_at'])); ?></td>
                                                <td><?php echo $req['reviewed_at'] ? date('M d, Y', strtotime($req['reviewed_at'])) : 'Not reviewed'; ?></td>
                                                <td><?php echo htmlspecialchars($req['remarks'] ?? 'No remarks'); ?></td>
                                                <td>
                                                    <a href="../<?php echo $req['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php foreach ($uploadedFiles as $file): ?>
                                            <tr>
                                                <td>
                                                    <strong>Uploaded File</strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($file); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">Not in Database</span>
                                                </td>
                                                <td>Unknown</td>
                                                <td>Not reviewed</td>
                                                <td>File exists but not tracked in database</td>
                                                <td>
                                                    <a href="../uploads/requirements/<?php echo $file; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-upload display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Requirements Submitted</h5>
                                    <p class="text-muted">The student has not yet submitted any requirements.</p>
                                    
                                    <!-- Debug Information -->
                                    <div class="mt-4 p-3 bg-light rounded">
                                        <h6 class="text-info">Debug Information:</h6>
                                        <small class="text-muted">
                                            Student ID: <?php echo $studentId; ?><br>
                                            Requirements Count: <?php echo count($requirements); ?><br>
                                            Uploaded Files Count: <?php echo count($uploadedFiles); ?><br>
                                            Requirements Array: <?php echo empty($requirements) ? 'Empty' : 'Has data'; ?><br>
                                            <?php if (!empty($uploadedFiles)): ?>
                                            Uploaded Files: <?php echo implode(', ', $uploadedFiles); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($requirements)): ?>
                                            First Requirement: <?php echo htmlspecialchars($requirements[0]['document_name'] ?? 'N/A'); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Test Permit Tab -->
                        <div class="tab-pane fade" id="test-permit" role="tabpanel">
                            <div class="p-4">
                                <?php if ($testPermit || $studentProgress['test_permit_approved']): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Permit Information</h6>
                                        <p><strong>Status:</strong> 
                                            <span class="badge <?php echo $studentProgress['test_permit_approved'] ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $studentProgress['test_permit_approved'] ? 'Approved' : 'Pending'; ?>
                                            </span>
                                        </p>
                                        <p><strong>Permit Number:</strong> <?php echo htmlspecialchars($testPermit['permit_number'] ?? 'N/A'); ?></p>
                                        <p><strong>Issued Date:</strong> <?php echo ($testPermit && $testPermit['issued_at']) ? date('F d, Y', strtotime($testPermit['issued_at'])) : 'N/A'; ?></p>
                                        <p><strong>Approved Date:</strong> <?php echo ($testPermit && $testPermit['approved_at']) ? date('F d, Y', strtotime($testPermit['approved_at'])) : 'N/A'; ?></p>
                                        <p><strong>Approved By:</strong> <?php echo ($testPermit && $testPermit['approved_by']) ? 'Admin ID: ' . htmlspecialchars($testPermit['approved_by']) : 'N/A'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Exam Details</h6>
                                        <p><strong>Exam Date:</strong> <?php echo ($testPermit && $testPermit['exam_date']) ? date('F d, Y', strtotime($testPermit['exam_date'])) : 'Not scheduled'; ?></p>
                                        <p><strong>Exam Time:</strong> <?php echo ($testPermit && $testPermit['exam_time']) ? htmlspecialchars($testPermit['exam_time']) : 'Not scheduled'; ?></p>
                                        <p><strong>Room:</strong> <?php echo ($testPermit && $testPermit['exam_room']) ? htmlspecialchars($testPermit['exam_room']) : 'Not specified'; ?></p>
                                    </div>
                                </div>
                                
                                <?php if (($testPermit && $testPermit['remarks']) || ($testPermit && $testPermit['admin_remarks'])): ?>
                                <hr class="my-4">
                                <div class="row">
                                    <div class="col-12">
                                        <?php if ($testPermit && $testPermit['remarks']): ?>
                                        <h6 class="text-primary mb-3">Student Remarks</h6>
                                        <p><?php echo htmlspecialchars($testPermit['remarks']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($testPermit && $testPermit['admin_remarks']): ?>
                                        <h6 class="text-primary mb-3">Admin Remarks</h6>
                                        <p><?php echo htmlspecialchars($testPermit['admin_remarks']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <?php if ($studentProgress['test_permit_approved']): ?>
                                    <a href="../view_test_permit.php?student_id=<?php echo $studentId; ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-pdf me-2"></i>View Test Permit PDF
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-id-card display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Test Permit Requested</h5>
                                    <p class="text-muted">The student has not yet requested a test permit.</p>
                                    
                                    <!-- Debug Information -->
                                    <div class="mt-4 p-3 bg-light rounded">
                                        <h6 class="text-info">Debug Information:</h6>
                                        <small class="text-muted">
                                            Student ID: <?php echo $studentId; ?><br>
                                            Test Permit Record Exists: <?php echo $testPermit ? 'Yes' : 'No'; ?><br>
                                            Progress Test Permit: <?php echo $studentProgress['test_permit'] ? 'Yes' : 'No'; ?><br>
                                            Progress Test Permit Approved: <?php echo $studentProgress['test_permit_approved'] ? 'Yes' : 'No'; ?><br>
                                            Exam Completed: <?php echo $studentProgress['exam_completed'] ? 'Yes' : 'No'; ?><br>
                                            Test Results Available: <?php echo $studentProgress['test_results_available'] ? 'Yes' : 'No'; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Personal Data Form Tab -->
                        <div class="tab-pane fade" id="personal-data" role="tabpanel">
                            <div class="p-4">
                                <?php if ($f2Form): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Personal Information</h6>
                                        <p><strong>First Name:</strong> <?php echo htmlspecialchars($f2Form['first_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Middle Name:</strong> <?php echo htmlspecialchars($f2Form['middle_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($f2Form['last_name'] ?? 'N/A'); ?></p>
                                        <p><strong>Birth Date:</strong> <?php echo ($f2Form['date_of_birth'] ?? null) ? date('F d, Y', strtotime($f2Form['date_of_birth'])) : 'N/A'; ?></p>
                                        <p><strong>Age:</strong> <?php echo htmlspecialchars($f2Form['age'] ?? 'N/A'); ?></p>
                                        <p><strong>Sex:</strong> <?php echo htmlspecialchars($f2Form['sex'] ?? 'N/A'); ?></p>
                                        <p><strong>Civil Status:</strong> <?php echo htmlspecialchars($f2Form['civil_status'] ?? 'N/A'); ?></p>
                                        <p><strong>Religion:</strong> <?php echo htmlspecialchars($f2Form['religion'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Contact Information</h6>
                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($f2Form['address'] ?? 'N/A'); ?></p>
                                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($f2Form['contact_number'] ?? 'N/A'); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($f2Form['email'] ?? 'N/A'); ?></p>
                                        <p><strong>Place of Birth:</strong> <?php echo htmlspecialchars($f2Form['place_of_birth'] ?? 'N/A'); ?></p>
                                        <p><strong>Ethnicity:</strong> <?php echo htmlspecialchars($f2Form['ethnicity'] ?? 'N/A'); ?></p>
                                        <p><strong>Submitted:</strong> <?php echo $f2Form['created_at'] ? date('F d, Y g:i A', strtotime($f2Form['created_at'])) : 'N/A'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="view_f2_pdf.php?student_id=<?php echo $studentId; ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-pdf me-2"></i>View F2 Form PDF
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-user-edit display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Personal Data Form Submitted</h5>
                                    <p class="text-muted">The student has not yet submitted their personal data form.</p>
                                    
                                    <!-- Debug Information -->
                                    <div class="mt-4 p-3 bg-light rounded">
                                        <h6 class="text-info">Debug Information:</h6>
                                        <small class="text-muted">
                                            Student ID: <?php echo $studentId; ?><br>
                                            F2 Form Record Exists: <?php echo $f2Form ? 'Yes' : 'No'; ?><br>
                                            <?php if ($f2Form): ?>
                                            Available Fields: <?php echo implode(', ', array_keys($f2Form)); ?><br>
                                            Birth Date Value: <?php echo htmlspecialchars($f2Form['date_of_birth'] ?? 'N/A'); ?><br>
                                            Phone Number Value: <?php echo htmlspecialchars($f2Form['contact_number'] ?? 'N/A'); ?><br>
                                            Email Value: <?php echo htmlspecialchars($f2Form['email'] ?? 'N/A'); ?><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Test Results Tab -->
                        <div class="tab-pane fade" id="test-results" role="tabpanel">
                            <div class="p-4">
                                <?php if ($testResults): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Test Information</h6>
                                        <p><strong>Exam Date:</strong> <?php echo ($testResults['exam_date'] ?? null) ? date('F d, Y', strtotime($testResults['exam_date'])) : 'N/A'; ?></p>
                                        <p><strong>Permit Number:</strong> <?php echo htmlspecialchars($testResults['permit_number'] ?? 'N/A'); ?></p>
                                        <p><strong>Raw Score:</strong> 
                                            <?php if (isset($testResults['raw_score']) && $testResults['raw_score'] !== null): ?>
                                            <span class="badge <?php echo $testResults['raw_score'] >= 200 ? 'bg-success' : ($testResults['raw_score'] >= 150 ? 'bg-warning' : 'bg-danger'); ?>">
                                                <?php echo $testResults['raw_score']; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Subject Scores</h6>
                                        <p><strong>English (Raw):</strong> <?php echo $testResults['english_raw'] ?? 'N/A'; ?></p>
                                        <p><strong>Filipino (Raw):</strong> <?php echo $testResults['filipino_raw'] ?? 'N/A'; ?></p>
                                        <p><strong>General Info (Raw):</strong> <?php echo $testResults['gen_info_raw'] ?? 'N/A'; ?></p>
                                        <p><strong>Filipino (Transmuted):</strong> <?php echo $testResults['filipino_transmuted'] ?? 'N/A'; ?></p>
                                        <p><strong>General Info (Transmuted):</strong> <?php echo $testResults['gen_info_transmuted'] ?? 'N/A'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="print_test_result.php?id=<?php echo $testResults['id']; ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-pdf me-2"></i>View Test Results PDF
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar display-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No Test Results Available</h5>
                                    <p class="text-muted">Test results have not been recorded for this student yet.</p>
                                    
                                    <!-- Debug Information -->
                                    <div class="mt-4 p-3 bg-light rounded">
                                        <h6 class="text-info">Debug Information:</h6>
                                        <small class="text-muted">
                                            Student ID: <?php echo $studentId; ?><br>
                                            Test Results Record Exists: <?php echo $testResults ? 'Yes' : 'No'; ?><br>
                                            <?php if ($testResults): ?>
                                            Available Fields: <?php echo implode(', ', array_keys($testResults)); ?><br>
                                            Raw Score Value: <?php echo htmlspecialchars($testResults['raw_score'] ?? 'N/A'); ?><br>
                                            Exam Date Value: <?php echo htmlspecialchars($testResults['exam_date'] ?? 'N/A'); ?><br>
                                            English Raw: <?php echo htmlspecialchars($testResults['english_raw'] ?? 'N/A'); ?><br>
                                            Filipino Raw: <?php echo htmlspecialchars($testResults['filipino_raw'] ?? 'N/A'); ?><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to scroll to and activate a specific tab
        function scrollToTab(tabId) {
            // Get the tab button and content
            const tabButton = document.getElementById(tabId);
            const tabContent = document.querySelector(tabButton.getAttribute('data-bs-target'));
            
            if (tabButton && tabContent) {
                // Activate the tab
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
                
                // Scroll to the tab content
                setTimeout(() => {
                    tabContent.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 100);
            }
        }
        
        // Add hover effects to progress items
        document.addEventListener('DOMContentLoaded', function() {
            const progressItems = document.querySelectorAll('.clickable');
            
            progressItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>
