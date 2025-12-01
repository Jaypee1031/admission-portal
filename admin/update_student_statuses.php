<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();

// Handle status update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_all_statuses') {
        $result = $auth->updateAllStudentStatuses();
        if ($result['success']) {
            showAlert($result['message'], 'success');
        } else {
            showAlert($result['message'], 'error');
        }
    } elseif ($_POST['action'] === 'update_single_status' && isset($_POST['student_id']) && isset($_POST['new_status'])) {
        $result = $auth->updateStudentStatus($_POST['student_id'], $_POST['new_status']);
        if ($result['success']) {
            showAlert($result['message'], 'success');
        } else {
            showAlert($result['message'], 'error');
        }
    }
    
    redirect('update_student_statuses.php');
}

// Get all students with their current status
$db = getDB();
$stmt = $db->prepare("
    SELECT s.id, s.first_name, s.last_name, s.email, s.status, s.type,
           COUNT(r.id) as requirements_count,
           COUNT(CASE WHEN r.status = 'Approved' THEN 1 END) as approved_requirements,
           af.id as has_admission_form,
           tp.id as has_test_permit,
           tp.status as permit_status,
           s.f2_form_enabled, s.f2_form_completed, s.test_result_available
    FROM students s
    LEFT JOIN requirements r ON s.id = r.student_id
    LEFT JOIN admission_forms af ON s.id = af.student_id
    LEFT JOIN test_permits tp ON s.id = tp.student_id
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
");
$stmt->execute();
$students = $stmt->fetchAll();

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Statuses - <?php echo SITE_NAME; ?></title>
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
                                <i class="fas fa-sync-alt me-2 text-primary"></i>
                                Update Student Statuses
                            </h2>
                            <p class="text-muted mb-0">Automatically update student statuses based on their application progress.</p>
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

        <!-- Bulk Update Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-cogs me-2 text-primary"></i>
                        Bulk Status Update
                    </h5>
                    <p class="text-muted mb-3">
                        This will automatically update all student statuses based on their current application progress.
                        The system will evaluate each student's requirements, forms, permits, and test results to determine the appropriate status.
                    </p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to update all student statuses? This action cannot be undone.')">
                        <input type="hidden" name="action" value="update_all_statuses">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>Update All Student Statuses
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Student Status Overview
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by name or email..." onkeyup="searchTable()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchTable()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Current Status</th>
                                    <th>Requirements</th>
                                    <th>Admission Form</th>
                                    <th>Test Permit</th>
                                    <th>F2 Form</th>
                                    <th>Test Results</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No students found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $student['type'] === 'Freshman' ? 'info' : 'warning'; ?>">
                                            <?php echo $student['type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower($student['status']); ?>">
                                            <?php echo $student['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['requirements_count'] > 0): ?>
                                            <span class="badge bg-<?php echo $student['approved_requirements'] == $student['requirements_count'] ? 'success' : 'warning'; ?>">
                                                <?php echo $student['approved_requirements']; ?>/<?php echo $student['requirements_count']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['has_admission_form']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Complete
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>Incomplete
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['has_test_permit']): ?>
                                            <span class="badge bg-<?php echo $student['permit_status'] === 'Approved' ? 'success' : 'warning'; ?>">
                                                <?php echo $student['permit_status'] ?? 'Pending'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['f2_form_enabled']): ?>
                                            <?php if ($student['f2_form_completed']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Complete
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-lock me-1"></i>Disabled
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['test_result_available']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Available
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>Not Available
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-info btn-sm" onclick="updateSingleStatus(<?php echo $student['id']; ?>, '<?php echo $student['status']; ?>')" title="Update Status">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
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

    <!-- Update Single Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">
                        <i class="fas fa-sync-alt me-2"></i>Update Student Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_single_status">
                        <input type="hidden" name="student_id" id="modalStudentId">
                        
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">New Status</label>
                            <select class="form-select" id="newStatus" name="new_status" required>
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
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search table function
        function searchTable() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const nameCell = row.cells[0]; // Student Name column
                const emailCell = row.cells[1]; // Email column
                
                const nameText = nameCell.textContent.toLowerCase();
                const emailText = emailCell.textContent.toLowerCase();
                
                if (nameText.includes(searchTerm) || emailText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
        
        // Update single status function
        function updateSingleStatus(studentId, currentStatus) {
            document.getElementById('modalStudentId').value = studentId;
            document.getElementById('newStatus').value = currentStatus;
            
            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }
    </script>
</body>
</html>
