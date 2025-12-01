<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_permit.php';
require_once '../includes/favicon.php';
require_once '../includes/courses.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$testPermit = new TestPermit();
$coursesManager = new Courses();
$filterCourses = $coursesManager->getActiveCourses();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Single permit action
        $permitId = (int)$_POST['permit_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'reject') {
            $status = $action === 'approve' ? 'Approved' : 'Rejected';
            
            try {
                $db = getDB();
                
                // Start transaction
                $db->beginTransaction();
                
                // Update test permit status
                $stmt = $db->prepare("UPDATE test_permits SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $user['id'], $permitId]);
                
                // If approving, also update all requirements for this student to Approved
                if ($action === 'approve') {
                    // Get student_id from the test permit
                    $stmt = $db->prepare("SELECT student_id FROM test_permits WHERE id = ?");
                    $stmt->execute([$permitId]);
                    $permitData = $stmt->fetch();
                    
                    if ($permitData) {
                        $studentId = $permitData['student_id'];
                        
                        // Update all requirements for this student to Approved
                        $stmt = $db->prepare("UPDATE requirements SET status = 'Approved', reviewed_at = NOW() WHERE student_id = ? AND status = 'Pending'");
                        $stmt->execute([$studentId]);
                    }
                }
                
                // Commit transaction
                $db->commit();
                
                if ($stmt->rowCount() > 0) {
                    $message = $action === 'approve' ? 'Test permit and requirements approved successfully!' : 'Test permit rejected successfully!';
                    showAlert($message, 'success');
                } else {
                    showAlert('Failed to update test permit status.', 'error');
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollback();
                showAlert('An error occurred while updating the test permit.', 'error');
            }
        }
    } elseif (isset($_POST['bulk_action'])) {
        // Bulk permit action
        $bulkAction = $_POST['bulk_action'];
        $permitIds = $_POST['permit_ids'] ?? [];
        
        if (!empty($permitIds) && ($bulkAction === 'approve' || $bulkAction === 'reject')) {
            $status = $bulkAction === 'approve' ? 'Approved' : 'Rejected';
            $placeholders = str_repeat('?,', count($permitIds) - 1) . '?';
            
            try {
                $db = getDB();
                
                // Start transaction
                $db->beginTransaction();
                
                // Update test permits status
                $stmt = $db->prepare("UPDATE test_permits SET status = ?, approved_by = ?, approved_at = NOW() WHERE id IN ($placeholders)");
                $params = array_merge([$status, $user['id']], $permitIds);
                $stmt->execute($params);
                
                // If bulk approving, also update all requirements for these students to Approved
                if ($bulkAction === 'approve') {
                    // Get all student_ids from the approved test permits
                    $stmt = $db->prepare("SELECT DISTINCT student_id FROM test_permits WHERE id IN ($placeholders)");
                    $stmt->execute($permitIds);
                    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($studentIds)) {
                        $studentPlaceholders = str_repeat('?,', count($studentIds) - 1) . '?';
                        
                        // Update all requirements for these students to Approved
                        $stmt = $db->prepare("UPDATE requirements SET status = 'Approved', reviewed_at = NOW() WHERE student_id IN ($studentPlaceholders) AND status = 'Pending'");
                        $stmt->execute($studentIds);
                    }
                }
                
                // Commit transaction
                $db->commit();
                
                $count = $stmt->rowCount();
                $message = $bulkAction === 'approve' ? 
                    "Successfully approved $count test permit(s) and their requirements!" : 
                    "Successfully rejected $count test permit(s)!";
                showAlert($message, 'success');
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollback();
                showAlert('An error occurred while updating the test permits.', 'error');
            }
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$courseChoice = $_GET['course_choice'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

// Build query with filters
$db = getDB();
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(af.first_name LIKE ? OR af.last_name LIKE ? OR s.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($courseChoice)) {
    $whereConditions[] = "(af.course_first = ? OR af.course_second = ? OR af.course_third = ?)";
    $params[] = $courseChoice;
    $params[] = $courseChoice;
    $params[] = $courseChoice;
}

if (!empty($type)) {
    $whereConditions[] = "s.type = ?";
    $params[] = $type;
}

if (!empty($status)) {
    $whereConditions[] = "tp.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Pagination settings
$permitsPerPage = 10;
$permitsPage = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$permitsOffset = ($permitsPage - 1) * $permitsPerPage;

// Total count for current filters
$countSql = "
    SELECT COUNT(*)
    FROM test_permits tp
    JOIN students s ON tp.student_id = s.id
    LEFT JOIN admission_forms af ON s.id = af.student_id
    $whereClause
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$permitsTotal = (int)$countStmt->fetchColumn();

// Paged data query
$stmt = $db->prepare("
    SELECT tp.*, s.email as student_email, s.type,
           af.first_name, af.last_name, af.course_first, af.course_second, af.course_third
    FROM test_permits tp
    JOIN students s ON tp.student_id = s.id
    LEFT JOIN admission_forms af ON s.id = af.student_id
    $whereClause
    ORDER BY tp.issued_at DESC
    LIMIT ? OFFSET ?
");

$dataParams = $params;
$dataParams[] = $permitsPerPage;
$dataParams[] = $permitsOffset;

$stmt->execute($dataParams);
$testPermits = $stmt->fetchAll();

$permitsStart = $permitsTotal > 0 ? $permitsOffset + 1 : 0;
$permitsEnd = $permitsTotal > 0 ? min($permitsOffset + count($testPermits), $permitsTotal) : 0;
$hasPrevPermits = $permitsPage > 1;
$hasNextPermits = $permitsOffset + $permitsPerPage < $permitsTotal;

// Get alert message
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Permit Requests - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item active" href="test_permits.php">
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
                    <h2 class="mb-2">
                        <i class="fas fa-id-card me-2 text-primary"></i>
                        Test Permit Requests
                    </h2>
                    <p class="text-muted mb-0">Review and manage student test permit requests.</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Name or email..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="course_choice" class="form-label">Course Choice</label>
                            <select class="form-select" id="course_choice" name="course_choice">
                                <option value="">All Courses</option>
                                <?php foreach ($filterCourses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($_GET['course_choice'] ?? '') === $course ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="Freshman" <?php echo ($_GET['type'] ?? '') === 'Freshman' ? 'selected' : ''; ?>>Freshman</option>
                                <option value="Transferee" <?php echo ($_GET['type'] ?? '') === 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo ($_GET['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Need to approve (Pending)</option>
                                <option value="Approved" <?php echo ($_GET['status'] ?? '') === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $permitsTotal; ?></h4>
                                <p class="mb-0">Total Requests</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo count(array_filter($testPermits, fn($p) => $p['status'] === 'Pending')); ?></h4>
                                <p class="mb-0">Pending</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo count(array_filter($testPermits, fn($p) => $p['status'] === 'Approved')); ?></h4>
                                <p class="mb-0">Approved</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo count(array_filter($testPermits, fn($p) => $p['status'] === 'Rejected')); ?></h4>
                                <p class="mb-0">Rejected</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-times fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Test Permit Requests Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>Test Permits Management
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by name or email..." onkeyup="searchTable()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchTable()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <a href="download_all_test_permits.php<?php echo !empty($_GET) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-success">
                                <i class="fas fa-download me-2"></i>Download All Test Permits
                            </a>
                        </div>
                    </div>
                    <?php
                        // Build base query string for pagination links (preserve filters)
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $prevQuery = $baseQuery;
                        $nextQuery = $baseQuery;
                        $prevQuery['page'] = max(1, $permitsPage);
                        if ($permitsPage > 1) {
                            $prevQuery['page'] = $permitsPage - 1;
                        }
                        $nextQuery['page'] = $permitsPage + 1;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($permitsTotal)): ?>
                                Showing applicants <?php echo $permitsStart; ?>–<?php echo $permitsEnd; ?> of <?php echo $permitsTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($testPermits); ?> test permits
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Test permits navigation">
                            <?php if (!empty($hasPrevPermits) && $hasPrevPermits): ?>
                                <a href="test_permits.php?<?php echo http_build_query($prevQuery); ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if (!empty($hasNextPermits) && $hasNextPermits): ?>
                                <a href="test_permits.php?<?php echo http_build_query($nextQuery); ?>" class="btn btn-outline-secondary">Next</a>
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
                                    <th>Permit #</th>
                                    <th>Student</th>
                                    <th>Course Choice</th>
                                    <th>Exam Date</th>
                                    <th>Exam Time</th>
                                    <th>Room</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($testPermits)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No test permit requests found.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($testPermits as $permit): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($permit['permit_number']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars(($permit['first_name'] ?? '') . ' ' . ($permit['last_name'] ?? '')); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($permit['student_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>1st:</strong> <?php echo htmlspecialchars($permit['course_first'] ?? 'N/A'); ?><br>
                                            <small class="text-muted">2nd: <?php echo htmlspecialchars($permit['course_second'] ?? 'N/A'); ?></small><br>
                                            <small class="text-muted">3rd: <?php echo htmlspecialchars($permit['course_third'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($permit['exam_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $examTimeLabels = [
                                            '08:30' => '8:30 AM - 11:00 AM',
                                            '13:00' => '1:00 PM - 3:30 PM'
                                        ];
                                        echo $examTimeLabels[$permit['exam_time']] ?? $permit['exam_time'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $examRoomLabels = [
                                            'qsu_student_center' => 'QSU Student Center - Testing room'
                                        ];
                                        echo $examRoomLabels[$permit['exam_room']] ?? $permit['exam_room'];
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $permit['status'] === 'Approved' ? 'success' : ($permit['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $permit['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($permit['issued_at'])); ?></td>
                                    <td class="actions-column text-center">
                                        <?php if ($permit['status'] === 'Pending'): ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="permit_id" value="<?php echo $permit['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this test permit request?')" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                    <span class="ms-1">Approve</span>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="permit_id" value="<?php echo $permit['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this test permit request?')" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                    <span class="ms-1">Reject</span>
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning" onclick="printSinglePermit(<?php echo $permit['student_id']; ?>)" title="Print PDF">
                                                <i class="fas fa-print"></i>
                                                <span class="ms-1">Print</span>
                                            </button>
                                            <a class="btn btn-primary" href="../download_test_permit.php?student_id=<?php echo $permit['student_id']; ?>" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                                <span class="ms-1">Download</span>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($permit['remarks'])): ?>
                                <tr>
                                    <td colspan="9" class="bg-light">
                                        <small><strong>Remarks:</strong> <?php echo htmlspecialchars($permit['remarks']); ?></small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/autocomplete.js"></script>
    <script>
        
        // Search table function
        function searchTable() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const table = document.querySelector('.table-responsive table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const studentCell = row.cells[1]; // Student column (index 1)
                
                if (studentCell) {
                    const studentText = studentCell.textContent.toLowerCase();
                    
                    if (studentText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }
        
        // Export to Excel
        function exportToExcel() {
            const table = document.querySelector('table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            let csvContent = '';
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('th, td'));
                const rowData = cells.map(cell => {
                    // Skip checkbox column
                    if (cell.querySelector('input[type="checkbox"]')) return '';
                    return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
                }).join(',');
                csvContent += rowData + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'test_permits_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        
        // Print single permit
        function printSinglePermit(studentId) {
            const printWindow = window.open(`../view_test_permit.php?student_id=${studentId}`, '_blank');
            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            }
        }
        
        // View permit details
        function viewPermitDetails(permitId) {
            // Create modal for viewing permit details
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Test Permit Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading permit details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Load permit details via AJAX (you can implement this)
            setTimeout(() => {
                modal.querySelector('.modal-body').innerHTML = `
                    <p>Permit ID: ${permitId}</p>
                    <p>This feature will load detailed permit information.</p>
                `;
            }, 1000);
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }
        
        // Download permit PDF
        function downloadPermit(permitId) {
            window.open(`../download_test_permit.php?permit_id=${permitId}`, '_blank');
        }
        
        // Initialize autocomplete for search fields
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize autocomplete for filter search
            const filterSearchInput = document.getElementById('search');
            if (filterSearchInput) {
                new AutocompleteSearch(filterSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'test_permits',
                    displayField: 'display',
                    valueField: 'name',
                    onSelect: function(suggestion) {
                        // Auto-submit the form when suggestion is selected
                        filterSearchInput.value = suggestion.display;
                        filterSearchInput.form.submit();
                    }
                });
            }
            
            // Initialize autocomplete for table search
            const tableSearchInput = document.getElementById('searchInput');
            if (tableSearchInput) {
                new AutocompleteSearch(tableSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'test_permits',
                    displayField: 'display',
                    valueField: 'name'
                });
            }
        });
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>