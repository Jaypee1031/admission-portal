<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$db = getDB();

// Handle student password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_student_password') {
    $studentId = (int)$_POST['student_id'];
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        showAlert('New password and confirmation password do not match', 'error');
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        showAlert('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long', 'error');
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE students SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $studentId])) {
                showAlert('Student password changed successfully', 'success');
            } else {
                showAlert('Failed to change password', 'error');
            }
        } catch (PDOException $e) {
            showAlert('Database error: ' . $e->getMessage(), 'error');
        }
    }
    redirect('manage_students.php');
}

// Handle student email change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_student_email') {
    $studentId = (int)$_POST['student_id'];
    $newEmail = sanitizeInput($_POST['new_email'] ?? '');
    
    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        showAlert('Please enter a valid email address', 'error');
    } else {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $studentId]);
            if ($stmt->rowCount() > 0) {
                showAlert('Email address is already in use by another student', 'error');
            } else {
                $stmt = $db->prepare("UPDATE students SET email = ? WHERE id = ?");
                if ($stmt->execute([$newEmail, $studentId])) {
                    showAlert('Student email changed successfully', 'success');
                } else {
                    showAlert('Failed to change email', 'error');
                }
            }
        } catch (PDOException $e) {
            showAlert('Database error: ' . $e->getMessage(), 'error');
        }
    }
    redirect('manage_students.php');
}

// Handle student status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_student_status') {
    $studentId = (int)$_POST['student_id'];
    $newStatus = sanitizeInput($_POST['new_status'] ?? '');
    
    try {
        $stmt = $db->prepare("UPDATE students SET status = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $studentId])) {
            showAlert('Student status changed successfully', 'success');
        } else {
            showAlert('Failed to change status', 'error');
        }
    } catch (PDOException $e) {
        showAlert('Database error: ' . $e->getMessage(), 'error');
    }
    redirect('manage_students.php');
}

// Get search results
$searchResults = [];
$searchTerm = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    try {
        $stmt = $db->prepare("
            SELECT s.*, 
                   COUNT(r.id) as requirements_count,
                   COUNT(CASE WHEN r.status = 'Approved' THEN 1 END) as approved_requirements,
                   af.id as has_admission_form,
                   tp.id as has_test_permit,
                   tp.status as permit_status
            FROM students s
            LEFT JOIN requirements r ON s.id = r.student_id
            LEFT JOIN admission_forms af ON s.id = af.student_id
            LEFT JOIN test_permits tp ON s.id = tp.student_id
            WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.id = ?
            GROUP BY s.id
            ORDER BY s.last_name, s.first_name
            LIMIT 50
        ");
        $searchPattern = "%{$searchTerm}%";
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchTerm]);
        $searchResults = $stmt->fetchAll();
    } catch (PDOException $e) {
        showAlert('Search error: ' . $e->getMessage(), 'error');
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item active" href="manage_students.php">
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
                                <i class="fas fa-user-cog me-2 text-primary"></i>
                                Manage Students
                            </h2>
                            <p class="text-muted mb-0">Search for students and manage their accounts, passwords, and email addresses.</p>
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

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($alert['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-search me-2 text-primary"></i>
                        Search Students
                    </h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <label for="search" class="form-label">Search by Name, Email, or Student ID</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                   placeholder="Enter student name, email, or ID...">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                            <?php if (!empty($searchTerm)): ?>
                            <a href="manage_students.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <?php if (!empty($searchResults)): ?>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-users me-2 text-primary"></i>
                        Search Results (<?php echo count($searchResults); ?> found)
                    </h5>
                    
                    <div class="table-responsive" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <table class="table table-hover" style="color: #2c3e50 !important; margin-bottom: 0;">
                            <thead class="table-dark" style="background-color: #343a40 !important;">
                                <tr style="color: #ffffff !important;">
                                    <th style="color: #ffffff !important; font-weight: 600;">Student Info</th>
                                    <th style="color: #ffffff !important; font-weight: 600;">Email</th>
                                    <th style="color: #ffffff !important; font-weight: 600;">Type</th>
                                    <th style="color: #ffffff !important; font-weight: 600;">Status</th>
                                    <th style="color: #ffffff !important; font-weight: 600;">Progress</th>
                                    <th style="color: #ffffff !important; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults as $student): ?>
                                <tr style="color: #2c3e50 !important; font-weight: 500; vertical-align: middle;">
                                    <td style="color: #2c3e50 !important;">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="background-color: #0d6efd !important;">
                                                <?php echo strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong style="color: #2c3e50 !important;"><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong>
                                                <br><small class="text-muted" style="color: #6c757d !important;">ID: <?php echo $student['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: #2c3e50 !important;"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td style="color: #2c3e50 !important;">
                                        <span class="badge bg-<?php echo $student['type'] === 'Freshman' ? 'info' : 'warning'; ?>" style="font-weight: 600; padding: 0.4em 0.8em;">
                                            <?php echo $student['type']; ?>
                                        </span>
                                    </td>
                                    <td style="color: #2c3e50 !important;">
                                        <span class="badge status-<?php echo strtolower($student['status']); ?>" style="font-weight: 600; padding: 0.4em 0.8em;">
                                            <?php echo $student['status']; ?>
                                        </span>
                                    </td>
                                    <td style="color: #2c3e50 !important;">
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if ($student['requirements_count'] > 0): ?>
                                                <span class="badge bg-<?php echo $student['approved_requirements'] == $student['requirements_count'] ? 'success' : 'warning'; ?>" style="font-weight: 600; padding: 0.4em 0.8em;">
                                                    R: <?php echo $student['approved_requirements']; ?>/<?php echo $student['requirements_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($student['has_admission_form']): ?>
                                                <span class="badge bg-success" style="font-weight: 600; padding: 0.4em 0.8em;">Form</span>
                                            <?php endif; ?>
                                            <?php if ($student['has_test_permit']): ?>
                                                <span class="badge bg-<?php echo $student['permit_status'] === 'Approved' ? 'success' : 'warning'; ?>" style="font-weight: 600; padding: 0.4em 0.8em;">
                                                    Permit: <?php echo $student['permit_status'] ?? 'Pending'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="color: #2c3e50 !important;">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm" style="background-color: #17a2b8; border-color: #17a2b8; color: #ffffff; padding: 6px 10px; border-radius: 4px; font-size: 13px;" onclick="changePassword(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" title="Change Password">
                                                <i class="fas fa-key" style="color: #ffffff; margin-right: 4px;"></i>
                                                Password
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color: #ffc107; border-color: #ffc107; color: #212529; padding: 6px 10px; border-radius: 4px; font-size: 13px; margin-left: 5px;" onclick="changeEmail(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['email']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" title="Change Email">
                                                <i class="fas fa-envelope" style="color: #212529; margin-right: 4px;"></i>
                                                Email
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color: #6c757d; border-color: #6c757d; color: #ffffff; padding: 6px 10px; border-radius: 4px; font-size: 13px; margin-left: 5px;" onclick="changeStatus(<?php echo $student['id']; ?>, '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" title="Change Status">
                                                <i class="fas fa-edit" style="color: #ffffff; margin-right: 4px;"></i>
                                                Status
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color: #dc3545; border-color: #dc3545; color: #ffffff; padding: 6px 10px; border-radius: 4px; font-size: 13px; margin-left: 5px;" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" title="Delete Student">
                                                <i class="fas fa-trash" style="color: #ffffff; margin-right: 4px;"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif (!empty($searchTerm)): ?>
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">No Students Found</h4>
                    <p class="text-muted mb-4">
                        No students found matching your search criteria: "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"
                    </p>
                    <a href="manage_students.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search Again
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Change Student Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="changePasswordForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_student_password">
                        <input type="hidden" name="student_id" id="passwordStudentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="passwordStudentName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                            <div id="password_match" class="form-text"></div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This will change the student's login password. Make sure to inform the student about this change.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Email Modal -->
    <div class="modal fade" id="changeEmailModal" tabindex="-1" aria-labelledby="changeEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeEmailModalLabel">
                        <i class="fas fa-envelope me-2"></i>Change Student Email
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="changeEmailForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_student_email">
                        <input type="hidden" name="student_id" id="emailStudentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="emailStudentName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_email" class="form-label">Current Email</label>
                            <input type="email" class="form-control" id="current_email" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_email" class="form-label">New Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                            <div class="form-text">
                                Enter a valid email address. This will be used for login and notifications.
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> The student will need to use the new email address to log in.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Change Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">
                        <i class="fas fa-edit me-2"></i>Change Student Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="changeStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_student_status">
                        <input type="hidden" name="student_id" id="statusStudentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="statusStudentName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="">Select Status</option>
                                <option value="PENDING">PENDING</option>
                                <option value="REQUIREMENTS_SUBMITTED">REQUIREMENTS_SUBMITTED</option>
                                <option value="FORM_COMPLETED">FORM_COMPLETED</option>
                                <option value="UNDER_REVIEW">UNDER_REVIEW</option>
                                <option value="PERMIT_APPROVED">PERMIT_APPROVED</option>
                                <option value="EXAM_COMPLETED">EXAM_COMPLETED</option>
                                <option value="APPROVED">APPROVED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> This will manually override the automatic status calculation for this student.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Change Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const matchDiv = document.getElementById('password_match');

            if (newPassword && confirmPassword && matchDiv) {
                function checkPasswordMatch() {
                    if (confirmPassword.value === '') {
                        matchDiv.innerHTML = '';
                        return;
                    }

                    if (newPassword.value === confirmPassword.value) {
                        matchDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Passwords match';
                        matchDiv.className = 'form-text text-success';
                        confirmPassword.setCustomValidity('');
                    } else {
                        matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Passwords do not match';
                        matchDiv.className = 'form-text text-danger';
                        confirmPassword.setCustomValidity('Passwords do not match');
                    }
                }

                newPassword.addEventListener('input', checkPasswordMatch);
                confirmPassword.addEventListener('input', checkPasswordMatch);
            }
        });

        // Change password function
        function changePassword(studentId, studentName) {
            document.getElementById('passwordStudentId').value = studentId;
            document.getElementById('passwordStudentName').value = studentName;
            
            // Clear form
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('password_match').innerHTML = '';
            
            const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        }

        // Change email function
        function changeEmail(studentId, currentEmail, studentName) {
            document.getElementById('emailStudentId').value = studentId;
            document.getElementById('emailStudentName').value = studentName;
            document.getElementById('current_email').value = currentEmail;
            document.getElementById('new_email').value = currentEmail;
            
            const modal = new bootstrap.Modal(document.getElementById('changeEmailModal'));
            modal.show();
        }

        // Change status function
        function changeStatus(studentId, currentStatus, studentName) {
            document.getElementById('statusStudentId').value = studentId;
            document.getElementById('statusStudentName').value = studentName;
            document.getElementById('new_status').value = currentStatus;
            
            const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
            modal.show();
        }
    </script>
</body>
</html>
