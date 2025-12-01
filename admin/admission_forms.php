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

// Get filter parameters
$search = $_GET['search'] ?? '';
$courseChoice = $_GET['course_choice'] ?? '';
$type = $_GET['type'] ?? '';

// Build query with filters
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

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Pagination settings
$formsPerPage = 10;
$formsPage = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$formsOffset = ($formsPage - 1) * $formsPerPage;

// Get admission forms with student data (paginated)
try {
    // Total count for current filters
    $countSql = "
        SELECT COUNT(*)
        FROM admission_forms af
        JOIN students s ON af.student_id = s.id
        LEFT JOIN test_permits tp ON s.id = tp.student_id
        $whereClause
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $admissionFormsTotal = (int)$countStmt->fetchColumn();

    // Paged data query
    $stmt = $db->prepare("
        SELECT af.*, s.email, s.status as student_status, s.type,
               tp.status as permit_status, tp.permit_number
        FROM admission_forms af
        JOIN students s ON af.student_id = s.id
        LEFT JOIN test_permits tp ON s.id = tp.student_id
        $whereClause
        ORDER BY af.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $dataParams = $params;
    $dataParams[] = $formsPerPage;
    $dataParams[] = $formsOffset;
    $stmt->execute($dataParams);
    $admissionForms = $stmt->fetchAll();

    $formsStart = $admissionFormsTotal > 0 ? $formsOffset + 1 : 0;
    $formsEnd = $admissionFormsTotal > 0 ? min($formsOffset + count($admissionForms), $admissionFormsTotal) : 0;
    $hasPrevForms = $formsPage > 1;
    $hasNextForms = $formsOffset + $formsPerPage < $admissionFormsTotal;
} catch (PDOException $e) {
    $admissionForms = [];
    $admissionFormsTotal = 0;
    $formsStart = 0;
    $formsEnd = 0;
    $hasPrevForms = false;
    $hasNextForms = false;
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Forms - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item active" href="admission_forms.php">
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
                        <i class="fas fa-file-alt me-2 text-primary"></i>
                        Admission Forms
                    </h2>
                    <p class="text-muted mb-0">View and manage all submitted admission forms.</p>
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
                                <option value="Bachelor of Science in Business Administration" <?php echo ($_GET['course_choice'] ?? '') === 'Bachelor of Science in Business Administration' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration</option>
                                <option value="Bachelor of Science in Education" <?php echo ($_GET['course_choice'] ?? '') === 'Bachelor of Science in Education' ? 'selected' : ''; ?>>Bachelor of Science in Education</option>
                                <option value="Bachelor of Science in Information Technology" <?php echo ($_GET['course_choice'] ?? '') === 'Bachelor of Science in Information Technology' ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
                                <option value="Bachelor of Science in Computer Science" <?php echo ($_GET['course_choice'] ?? '') === 'Bachelor of Science in Computer Science' ? 'selected' : ''; ?>>Bachelor of Science in Computer Science</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="Freshman" <?php echo ($_GET['type'] ?? '') === 'Freshman' ? 'selected' : ''; ?>>Freshman</option>
                                <option value="Transferee" <?php echo ($_GET['type'] ?? '') === 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo $admissionFormsTotal; ?></h4>
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
                                <h4 class="mb-0"><?php echo count(array_filter($admissionForms, fn($f) => $f['student_status'] === 'Pending')); ?></h4>
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
                                <h4 class="mb-0"><?php echo count(array_filter($admissionForms, fn($f) => $f['student_status'] === 'Approved')); ?></h4>
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
                                <h4 class="mb-0"><?php echo count(array_filter($admissionForms, fn($f) => $f['student_status'] === 'Rejected')); ?></h4>
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

        <!-- Admission Forms Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>Admission Forms Management
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search by name or email..." onkeyup="searchTable()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchTable()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <span class="badge bg-primary"><?php echo $admissionFormsTotal; ?> Forms</span>
                        </div>
                    </div>
                    <?php
                        // Build base query string for pagination links (preserve filters)
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $prevQuery = $baseQuery;
                        $nextQuery = $baseQuery;
                        $prevQuery['page'] = ($formsPage > 1) ? $formsPage - 1 : 1;
                        $nextQuery['page'] = $formsPage + 1;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($admissionFormsTotal)): ?>
                                Showing applicants <?php echo $formsStart; ?>–<?php echo $formsEnd; ?> of <?php echo $admissionFormsTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($admissionForms); ?> forms
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Admission forms navigation">
                            <?php if (!empty($hasPrevForms) && $hasPrevForms): ?>
                                <a href="admission_forms.php?<?php echo http_build_query($prevQuery); ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if (!empty($hasNextForms) && $hasNextForms): ?>
                                <a href="admission_forms.php?<?php echo http_build_query($nextQuery); ?>" class="btn btn-outline-secondary">Next</a>
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
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Course Choices</th>
                                    <th>Student<br>Status</th>
                                    <th>Test Permit</th>
                                    <th>Submitted</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admissionForms)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No admission forms found.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($admissionForms as $form): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars(($form['first_name'] ?? '') . ' ' . ($form['last_name'] ?? '')); ?></strong>
                                            <br><small class="text-muted">ID: <?php echo $form['student_id']; ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($form['email']); ?></td>
                                    <td>
                                        <div class="small">
                                            <strong>1st:</strong> <?php echo htmlspecialchars($form['course_first'] ?? 'N/A'); ?><br>
                                            <strong>2nd:</strong> <?php echo htmlspecialchars($form['course_second'] ?? 'N/A'); ?><br>
                                            <strong>3rd:</strong> <?php echo htmlspecialchars($form['course_third'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $studentStatus = $form['student_status'] ?? '';
                                        $statusText = $studentStatus !== '' ? $studentStatus : 'No Status';
                                        $statusColor = $studentStatus === 'Approved' ? 'success' : ($studentStatus === 'Pending' ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?php echo $statusColor; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($form['permit_number']): ?>
                                            <span class="badge bg-<?php echo $form['permit_status'] === 'Approved' ? 'success' : ($form['permit_status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo $form['permit_status']; ?>
                                            </span>
                                            <br><small class="text-muted"><?php echo $form['permit_number']; ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">No permit</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($form['created_at'])); ?></td>
                                    <td class="actions-column text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-warning" onclick="printSingleForm(<?php echo $form['student_id']; ?>)" title="Print PDF">
                                                <i class="fas fa-print"></i>
                                                <span class="ms-1">Print</span>
                                            </button>
                                            <a class="btn btn-primary" href="../view_pdf.php?student_id=<?php echo $form['student_id']; ?>&download=1" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                                <span class="ms-1">Download</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/autocomplete.js"></script>
    <script>
        // Print single admission form
        function printSingleForm(studentId) {
            const printWindow = window.open(`../view_pdf.php?student_id=${studentId}`, '_blank');
            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            }
        }
        
        // Initialize autocomplete for search fields
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize autocomplete for filter search
            const filterSearchInput = document.getElementById('search');
            if (filterSearchInput) {
                new AutocompleteSearch(filterSearchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'admission_forms',
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
                    context: 'admission_forms',
                    displayField: 'display',
                    valueField: 'name'
                });
            }
        });
        
        // Search table function
        function searchTable() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const table = document.querySelector('.table-responsive table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const nameCell = row.cells[0]; // Student column (index 0)
                const emailCell = row.cells[1]; // Email column (index 1)
                
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
    </script>
</body>
</html>
