<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$f2Form = new F2PersonalDataForm();

// Handle Personal Data form enable/disable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $studentId = (int)$_POST['student_id'];
    
    if ($_POST['action'] === 'enable_f2') {
        $result = $f2Form->enableF2Form($studentId);
    } elseif ($_POST['action'] === 'disable_f2') {
        $result = $f2Form->disableF2Form($studentId);
    }
    
    if ($result['success']) {
        showAlert($result['message'], 'success');
    } else {
        showAlert($result['message'], 'error');
    }
    
    redirect('/admin/f2_forms_management.php');
}

// Get all students with Personal Data form status
$students = $f2Form->getAllStudentsWithF2Status();
$stats = $f2Form->getF2FormStats();

// Pagination for students table (display only)
$studentsPerPage = 10;
$studentsPage = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$studentsTotal = count($students);
$studentsOffset = ($studentsPage - 1) * $studentsPerPage;
$studentsPageItems = array_slice($students, $studentsOffset, $studentsPerPage);
$studentsStart = $studentsTotal > 0 ? $studentsOffset + 1 : 0;
$studentsEnd = $studentsTotal > 0 ? min($studentsOffset + count($studentsPageItems), $studentsTotal) : 0;
$hasPrevStudents = $studentsPage > 1;
$hasNextStudents = $studentsOffset + $studentsPerPage < $studentsTotal;

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Data Forms Management - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item active" href="f2_forms_management.php">
                            <i class="fas fa-file-alt me-2"></i>Personal Data Forms
                        </a></li>
                        <li><a class="dropdown-item" href="test_results_management.php">
                            <i class="fas fa-chart-line me-2"></i>Test Results
                        </a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                F2 Personal Data Forms Management
                            </h2>
                            <p class="text-muted mb-0">Manage F2 personal data forms (COLLEGE-ADMISSION-TEST-Rev1.docx format) and view submitted forms.</p>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-primary"></i>
                    </div>
                    <h4 class="text-primary"><?php echo $stats['total_students']; ?></h4>
                    <p class="text-muted mb-0">Total Students</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-unlock fa-3x text-success"></i>
                    </div>
                    <h4 class="text-success"><?php echo $stats['f2_enabled']; ?></h4>
                    <p class="text-muted mb-0">Personal Data Form Enabled</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-info"></i>
                    </div>
                    <h4 class="text-info"><?php echo $stats['f2_completed']; ?></h4>
                    <p class="text-muted mb-0">Forms Completed</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-clock fa-3x text-warning"></i>
                    </div>
                    <h4 class="text-warning"><?php echo $stats['f2_pending']; ?></h4>
                    <p class="text-muted mb-0">Forms Pending</p>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Students Personal Data Form Status
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by name or email..." onkeyup="searchTable()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchTable()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm active" onclick="filterTable('all', this)">All</button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="filterTable('enabled', this)">Enabled</button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="filterTable('completed', this)">Completed</button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterTable('pending', this)">Pending</button>
                            </div>
                        </div>
                    </div>
                    <?php
                        // Build base query string for pagination links (preserve filters)
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $prevQuery = $baseQuery;
                        $nextQuery = $baseQuery;
                        $prevQuery['page'] = ($studentsPage > 1) ? $studentsPage - 1 : 1;
                        $nextQuery['page'] = $studentsPage + 1;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($studentsTotal)): ?>
                                Showing applicants <?php echo $studentsStart; ?>–<?php echo $studentsEnd; ?> of <?php echo $studentsTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($studentsPageItems); ?> students
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="F2 forms navigation">
                            <?php if (!empty($hasPrevStudents) && $hasPrevStudents): ?>
                                <a href="f2_forms_management.php?<?php echo http_build_query($prevQuery); ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if (!empty($hasNextStudents) && $hasNextStudents): ?>
                                <a href="f2_forms_management.php?<?php echo http_build_query($nextQuery); ?>" class="btn btn-outline-secondary">Next</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-2">Click 'Next' to view more applicants.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Personal Data Form Status</th>
                                    <th>Submitted Date</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($studentsPageItems)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No students found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($studentsPageItems as $student): ?>
                                <tr data-status="<?php echo $student['f2_form_enabled'] ? ($student['has_f2_form'] ? 'completed' : 'pending') : 'disabled'; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></strong>
                                                <?php if ($student['middle_name']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($student['middle_name']); ?></small>
                                                <?php endif; ?>
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
                                        <?php if ($student['f2_form_enabled']): ?>
                                            <?php if ($student['has_f2_form']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Completed
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
                                        <?php if ($student['submitted_at']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($student['submitted_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-column text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($student['f2_form_enabled']): ?>
                                                <?php if ($student['has_f2_form']): ?>
                                                    <button type="button" class="btn btn-info" onclick="viewF2Form(<?php echo $student['id']; ?>)" title="View Form">
                                                        <i class="fas fa-eye"></i>
                                                        <span class="ms-1">View</span>
                                                    </button>
                                                    <a href="view_f2_pdf.php?student_id=<?php echo $student['id']; ?>" class="btn btn-warning" target="_blank" title="View PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                        <span class="ms-1">PDF</span>
                                                    </a>
                                                <?php endif; ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to disable Personal Data form access for this student?')">
                                                    <input type="hidden" name="action" value="disable_f2">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-warning" title="Disable Personal Data Form">
                                                        <i class="fas fa-lock"></i>
                                                        <span class="ms-1">Disable</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to enable Personal Data form access for this student?')">
                                                    <input type="hidden" name="action" value="enable_f2">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-success" title="Enable Personal Data Form">
                                                        <i class="fas fa-unlock"></i>
                                                        <span class="ms-1">Enable</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

    <!-- View Personal Data Form Modal -->
    <div class="modal fade" id="viewF2FormModal" tabindex="-1" aria-labelledby="viewF2FormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewF2FormModalLabel">
                        <i class="fas fa-file-alt me-2"></i>F2 Personal Data Form
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="f2FormContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" onclick="viewF2PDF()">
                        <i class="fas fa-file-pdf me-1"></i>View PDF
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printF2Form()">
                        <i class="fas fa-print me-1"></i>Print Form
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/autocomplete.js"></script>
    <script>
        let currentStudentId = null;
        // Filter table function
        function filterTable(status, button) {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const rowStatus = row.getAttribute('data-status');
                const nameCell = row.cells[0]; // Student Name column
                const emailCell = row.cells[1]; // Email column
                
                const nameText = nameCell.textContent.toLowerCase();
                const emailText = emailCell.textContent.toLowerCase();
                
                const matchesSearch = searchTerm === '' || nameText.includes(searchTerm) || emailText.includes(searchTerm);

                let matchesFilter = false;
                if (status === 'all') {
                    matchesFilter = true;
                } else if (status === 'enabled') {
                    // "Enabled" tab: show only students that still need to be enabled
                    matchesFilter = rowStatus === 'disabled';
                } else {
                    // completed or pending
                    matchesFilter = rowStatus === status;
                }
                
                if (matchesSearch && matchesFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Update button states (only within this filter group)
            const filterGroup = document.querySelector('.btn-group[role="group"]');
            if (filterGroup) {
                filterGroup.querySelectorAll('.btn').forEach(btn => {
                    btn.classList.remove('active');
                });
            }
            if (button) {
                button.classList.add('active');
            }
        }
        
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
        
        // View Personal Data form function
        function viewF2Form(studentId) {
            currentStudentId = studentId;
            const modal = new bootstrap.Modal(document.getElementById('viewF2FormModal'));
            const content = document.getElementById('f2FormContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading form data...</p>
                </div>
            `;
            
            modal.show();
            
            // Load form data
            fetch(`view_f2_form.php?student_id=${studentId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading form data. Please try again.
                        </div>
                    `;
                });
        }
        
        // View F2 PDF function
        function viewF2PDF() {
            if (currentStudentId) {
                window.open(`view_f2_pdf.php?student_id=${currentStudentId}`, '_blank');
            }
        }
        
        // Initialize autocomplete for search fields
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize autocomplete for table search
            const tableSearchInput = document.getElementById('searchInput');
            if (tableSearchInput) {
                new AutocompleteSearch(tableSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'f2_forms',
                    displayField: 'display',
                    valueField: 'name'
                });
            }
        });
        
        // Print Personal Data form function
        function printF2Form() {
            const content = document.getElementById('f2FormContent');
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>F2 Personal Data Form</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            @media print {
                                .no-print { display: none !important; }
                            }
                        </style>
                    </head>
                    <body>
                        ${content.innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
