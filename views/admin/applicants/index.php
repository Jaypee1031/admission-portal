<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Force all dropdowns to drop down only */
        .dropdown-menu {
            top: 100% !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 1055 !important;
            position: absolute !important;
        }

        /* Ensure dropdown container doesn't clip the dropdown */
        .dropdown {
            position: relative !important;
            overflow: visible !important;
        }

        /* Table row positioning fix */
        td.actions-column {
            position: relative !important;
            overflow: visible !important;
        }
        
        /* Fix dropdown positioning and visibility - AGGRESSIVE FIXES */
        .table-responsive {
            overflow: visible !important;
            position: relative !important;
        }
        
        .table-responsive .table {
            overflow: visible !important;
        }
        
        .dropdown-menu {
            z-index: 9999 !important;
            position: absolute !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3) !important;
            border: 2px solid rgba(0, 0, 0, 0.2) !important;
            background-color: #ffffff !important;
            min-width: 200px !important;
            margin-top: 2px !important;
        }
        
        .dropdown-menu.show {
            display: block !important;
            z-index: 9999 !important;
        }
        
        /* Force dropdown container to be on top */
        .dropdown {
            position: relative !important;
            z-index: 1000 !important;
        }
        
        .dropdown.show {
            z-index: 9999 !important;
        }
        
        /* Override any container restrictions */
        .container, .container-fluid {
            overflow: visible !important;
        }
        
        .card, .dashboard-card {
            overflow: visible !important;
        }
        
        /* Ensure table cells don't clip */
        td {
            overflow: visible !important;
        }
        
        /* Make dropdown items more visible */
        .dropdown-item {
            padding: 8px 16px !important;
            color: #212529 !important;
            background-color: transparent !important;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa !important;
            color: #16181b !important;
        }
        
        /* Status badge styles */
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
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="applicantsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i>Applicants
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="applicantsDropdown">
                        <li><a class="dropdown-item active" href="applicants.php">
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
                        <i class="fas fa-users me-2 text-primary"></i>
                        Manage Applicants
                    </h2>
                    <p class="text-muted mb-0">Review and manage student applications.</p>
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

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                       placeholder="Name or email...">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Verified" <?php echo $statusFilter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    <option value="Freshman" <?php echo $typeFilter === 'Freshman' ? 'selected' : ''; ?>>Freshman</option>
                                    <option value="Transferee" <?php echo $typeFilter === 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?php echo array_sum($statusStats); ?></div>
                    <div class="stats-label">Total</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo $statusStats['Pending'] ?? 0; ?></div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-info"><?php echo $statusStats['Verified'] ?? 0; ?></div>
                    <div class="stats-label">Verified</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $statusStats['Approved'] ?? 0; ?></div>
                    <div class="stats-label">Approved</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-danger"><?php echo $statusStats['Rejected'] ?? 0; ?></div>
                    <div class="stats-label">Rejected</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-secondary"><?php echo ($typeStats['Freshman'] ?? 0) + ($typeStats['Transferee'] ?? 0); ?></div>
                    <div class="stats-label">All Types</div>
                </div>
            </div>
        </div>

        <!-- Applicants Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Applicants List
                        </h5>
                        <span class="badge bg-primary"><?php echo $applicantsTotal ?? count($applicants); ?> applicants</span>
                    </div>
                    
                    <?php if (empty($applicants)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users display-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No applicants found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later.</p>
                    </div>
                    <?php else: ?>
                    <?php
                        // Build base query string for pagination links (preserve filters)
                        $baseQuery = $_GET;
                        unset($baseQuery['page']);
                        $prevQuery = $baseQuery;
                        $nextQuery = $baseQuery;
                        $prevQuery['page'] = max(1, ($applicantsPage ?? 1) - 1);
                        $nextQuery['page'] = ($applicantsPage ?? 1) + 1;
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($applicantsTotal)): ?>
                                Showing applicants <?php echo $applicantsStart; ?>–<?php echo $applicantsEnd; ?> of <?php echo $applicantsTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($applicants); ?> applicants
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Applicants navigation">
                            <?php if (!empty($hasPrevApplicants) && $hasPrevApplicants): ?>
                                <a href="applicants.php?<?php echo http_build_query($prevQuery); ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if (!empty($hasNextApplicants) && $hasNextApplicants): ?>
                                <a href="applicants.php?<?php echo http_build_query($nextQuery); ?>" class="btn btn-outline-secondary">Next</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-2">Click 'Next' to view more applicants.</p>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Requirements</th>
                                    <th>Progress</th>
                                    <th>Registered</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($applicant['name'] ?? 'Unknown Student'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $applicant['type']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $applicant['status'] ?? 'Pending';
                                        $statusClass = strtolower($status);
                                        ?>
                                        <span class="badge status-<?php echo $statusClass; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo $applicant['approved_requirements']; ?>/<?php echo $applicant['requirements_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 80px; height: 8px;">
                                                <?php 
                                                $progress = 0;
                                                if ($applicant['requirements_count'] > 0) $progress += 33;
                                                if ($applicant['has_admission_form']) $progress += 33;
                                                if ($applicant['has_test_permit']) $progress += 34;
                                                ?>
                                                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                            <small><?php echo $progress; ?>%</small>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                    <td class="actions-column text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_applicant.php?id=<?php echo $applicant['id']; ?>" 
                                               class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                                <span class="ms-1">View</span>
                                            </a>
                                            <button type="button" class="btn btn-outline-success" 
                                                    data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $applicant['id']; ?>" 
                                                    title="Update Status">
                                                <i class="fas fa-edit"></i>
                                                <span class="ms-1">Status</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $applicant['id']; ?>" 
                                                    title="Delete Student">
                                                <i class="fas fa-trash"></i>
                                                <span class="ms-1">Delete</span>
                                            </button>
                                            
                                            <!-- PDF Documents Dropdown -->
                                            <?php 
                                            $hasPDFs = $applicant['has_admission_form'] || $applicant['has_f2_form'] || 
                                                      $applicant['has_approved_test_permit'] || $applicant['has_test_results'];
                                            ?>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" 
                                                        data-bs-toggle="dropdown" aria-expanded="false" title="View PDFs"
                                                        id="pdfDropdown<?php echo $applicant['id']; ?>"
                                                        <?php echo !$hasPDFs ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-file-pdf"></i>
                                                    <span class="ms-1">PDFs</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pdfDropdown<?php echo $applicant['id']; ?>" style="z-index: 1050;">
                                                    <?php if ($applicant['has_admission_form']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="../view_pdf.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-file-alt me-2 text-primary"></i>Admission Form
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($applicant['has_f2_form']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="view_f2_pdf.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-user-edit me-2 text-success"></i>Personal Data Form
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($applicant['has_approved_test_permit']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="../view_test_permit.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-id-card me-2 text-warning"></i>Test Permit
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($applicant['has_test_results']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="print_test_result.php?id=<?php echo $applicant['test_result_id']; ?>" target="_blank">
                                                            <i class="fas fa-chart-bar me-2 text-info"></i>Test Results
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$hasPDFs): ?>
                                                    <li>
                                                        <span class="dropdown-item-text text-muted">
                                                            <i class="fas fa-info-circle me-2"></i>No PDFs available
                                                        </span>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modals -->
    <?php foreach ($applicants as $applicant): ?>
    <div class="modal fade" id="statusModal<?php echo $applicant['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status - <?php echo htmlspecialchars($applicant['name'] ?? 'Unknown Student'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" value="<?php echo $applicant['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                        
                        <div class="mb-3">
                            <label for="status<?php echo $applicant['id']; ?>" class="form-label">New Status</label>
                            <select class="form-select" id="status<?php echo $applicant['id']; ?>" name="status" required>
                                <option value="Pending" <?php echo $applicant['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Verified" <?php echo $applicant['status'] === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                                <option value="Approved" <?php echo $applicant['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $applicant['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks<?php echo $applicant['id']; ?>" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks<?php echo $applicant['id']; ?>" name="remarks" rows="3" 
                                      placeholder="Optional remarks about the status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Delete Confirmation Modals -->
    <?php foreach ($applicants as $applicant): ?>
    <div class="modal fade" id="deleteModal<?php echo $applicant['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Delete Student - <?php echo htmlspecialchars($applicant['name'] ?? 'Unknown Student'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" value="<?php echo $applicant['id']; ?>">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>
                        
                        <p>You are about to permanently delete the following student and all their associated data:</p>
                        
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Student Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($applicant['name'] ?? 'Unknown Student'); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($applicant['email']); ?></p>
                                <p class="mb-1"><strong>Type:</strong> <?php echo $applicant['type']; ?></p>
                                <p class="mb-0"><strong>Status:</strong> <?php echo $applicant['status']; ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <h6>This will delete:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-danger me-2"></i>Student account and profile</li>
                                <li><i class="fas fa-check text-danger me-2"></i>All uploaded requirements (<?php echo $applicant['requirements_count']; ?> files)</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Admission form data</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Test permit information</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Personal data form (if submitted)</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Test results (if available)</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmation<?php echo $applicant['id']; ?>" class="form-label">
                                <strong>Type "DELETE" to confirm:</strong>
                            </label>
                            <input type="text" class="form-control" id="confirmation<?php echo $applicant['id']; ?>" 
                                   name="confirmation" placeholder="Type DELETE here" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="delete_student" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/autocomplete.js"></script>
    <script>
        // Fix dropdown positioning issues and initialize autocomplete
        document.addEventListener('DOMContentLoaded', function() {
            // Force dropdown positioning
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('show.bs.dropdown', function(e) {
                    // Force high z-index when dropdown opens
                    const menu = this.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.style.zIndex = '9999';
                        menu.style.position = 'absolute';
                        menu.style.display = 'block';
                        
                        // Ensure parent containers don't clip
                        let parent = this.parentElement;
                        while (parent && parent !== document.body) {
                            parent.style.overflow = 'visible';
                            parent = parent.parentElement;
                        }
                    }
                });
                
                dropdown.addEventListener('hide.bs.dropdown', function(e) {
                    // Clean up when dropdown closes
                    const menu = this.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.style.display = '';
                    }
                });
            });
            
            // Initialize autocomplete for search field
            const searchInput = document.getElementById('search');
            if (searchInput) {
                new AutocompleteSearch(searchInput, {
                    endpoint: '../includes/autocomplete.php',
                    context: 'applicants',
                    displayField: 'display',
                    valueField: 'name',
                    onSelect: function(suggestion) {
                        // Auto-submit the form when suggestion is selected
                        searchInput.value = suggestion.display;
                        searchInput.form.submit();
                    }
                });
            }
        });
        
        // Auto-refresh page every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
