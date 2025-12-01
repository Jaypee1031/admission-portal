<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h2 class="mb-2">
                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                        Admin Dashboard
                    </h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?>!</p>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?php echo $totalApplicants; ?></div>
                    <div class="stats-label">Total Applicants</div>
                    <i class="fas fa-users fa-2x text-primary opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo $pendingApplicants; ?></div>
                    <div class="stats-label">Pending Review</div>
                    <i class="fas fa-clock fa-2x text-warning opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $approvedApplicants; ?></div>
                    <div class="stats-label">Approved</div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-danger"><?php echo $rejectedApplicants; ?></div>
                    <div class="stats-label">Rejected</div>
                    <i class="fas fa-times-circle fa-2x text-danger opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Test Permit Statistics -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo $pendingTestPermits; ?></div>
                    <div class="stats-label">Pending Test Permits</div>
                    <i class="fas fa-id-card fa-2x text-warning opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $approvedTestPermits; ?></div>
                    <div class="stats-label">Approved Test Permits</div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Personal Data Form Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-info"><?php echo $f2Stats['f2_enabled']; ?></div>
                    <div class="stats-label">Personal Data Forms Enabled</div>
                    <i class="fas fa-unlock fa-2x text-info opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $f2Stats['f2_completed']; ?></div>
                    <div class="stats-label">Personal Data Forms Completed</div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo $f2Stats['f2_pending']; ?></div>
                    <div class="stats-label">Personal Data Forms Pending</div>
                    <i class="fas fa-clock fa-2x text-warning opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?php echo $testResultStats['total_results']; ?></div>
                    <div class="stats-label">Test Results</div>
                    <i class="fas fa-chart-line fa-2x text-primary opacity-25 position-absolute top-0 end-0 me-3 mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions for Pending Test Permits -->
        <?php if ($pendingTestPermits > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card bg-warning bg-opacity-10 border-warning">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                Pending Test Permit Approvals
                            </h5>
                            <p class="text-muted mb-0">You have <?php echo $pendingTestPermits; ?> test permit(s) waiting for approval.</p>
                        </div>
                        <div class="ms-3">
                            <a href="test_permits.php" class="btn btn-warning">
                                <i class="fas fa-id-card me-2"></i>Review Test Permits
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Capacity Monitoring -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-users-cog me-2 text-info"></i>
                        Exam Slot Capacity Monitoring
                    </h5>
                    <div class="row">
                        <?php if (empty($upcomingSlots)): ?>
                        <div class="col-12">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>No upcoming exam slots scheduled</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($upcomingSlots as $slot): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 <?php echo ($slot['remaining_slots'] ?? 0) <= 5 ? 'border-warning' : 'border-success'; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            <?php echo date('M d, Y', strtotime($slot['exam_date'])); ?>
                                        </h6>
                                        <span class="badge <?php echo ($slot['remaining_slots'] ?? 0) <= 5 ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $slot['remaining_slots']; ?> left
                                        </span>
                                    </div>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $slot['exam_time']; ?><br>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo $slot['exam_room']; ?>
                                        </small>
                                    </p>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <?php 
                                        $currentBookings = $slot['current_bookings'] ?? 0;
                                        $maxCapacity    = $slot['max_capacity'] ?? 1;
                                        $percentage     = $maxCapacity > 0 ? ($currentBookings / $maxCapacity) * 100 : 0;
                                        $colorClass     = $percentage >= 90 ? 'bg-danger' : ($percentage >= 75 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <div class="progress-bar <?php echo $colorClass; ?>" 
                                             style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $currentBookings; ?>/<?php echo $maxCapacity; ?> students
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="test_permit_settings.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cog me-1"></i>Manage Capacity Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions for Personal Data Forms and Test Results -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <i class="fas fa-file-alt me-2 text-success"></i>
                                Personal Data Forms
                            </h5>
                            <p class="text-muted mb-0">Manage student personal data forms (COLLEGE-ADMISSION-TEST-Rev1.docx format)</p>
                        </div>
                        <div class="ms-3">
                            <div class="btn-group dropdown" role="group">
                                <a href="f2_forms_management.php" class="btn btn-success">
                                    <i class="fas fa-file-alt me-2"></i>Manage Personal Data Forms
                                </a>
                                <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="f2_forms_management.php">
                                        <i class="fas fa-cog me-2"></i>Manage Forms
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="printAllF2Forms()">
                                        <i class="fas fa-print me-2"></i>Print All Personal Data Forms
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="downloadAllF2Forms()">
                                        <i class="fas fa-download me-2"></i>Download All Personal Data Forms
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="dashboard-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <i class="fas fa-chart-line me-2 text-info"></i>
                                Test Results Management
                            </h5>
                            <p class="text-muted mb-0">Upload and manage test results (TEST-RESULT.xlsx 	 TO-PRINT-TEST-RESULT-2026.xlsx)</p>
                        </div>
                        <div class="ms-3">
                            <div class="btn-group dropdown" role="group">
                                <a href="test_results_management.php" class="btn btn-info">
                                    <i class="fas fa-chart-line me-2"></i>Manage Results
                                </a>
                                <button type="button" class="btn btn-outline-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="test_results_management.php">
                                        <i class="fas fa-cog me-2"></i>Manage Results
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="printAllTestResults()">
                                        <i class="fas fa-print me-2"></i>Print All Results
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="downloadAllTestResults()">
                                        <i class="fas fa-download me-2"></i>Download All Results
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Applicants by Type
                    </h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stats-number text-info"><?php echo $typeStats['Freshman'] ?? 0; ?></div>
                            <div class="stats-label">Freshmen</div>
                        </div>
                        <div class="col-6">
                            <div class="stats-number text-warning"><?php echo $typeStats['Transferee'] ?? 0; ?></div>
                            <div class="stats-label">Transferees</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Upcoming Examinations
                    </h5>
                    <?php if (empty($upcomingExams)): ?>
                    <p class="text-muted text-center">No upcoming examinations scheduled.</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingExams as $exam): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars(($exam['first_name'] ?? '') . ' ' . ($exam['last_name'] ?? 'Unknown Student')); ?></strong><br>
                                <small class="text-muted">
                                    <?php 
                                    $examRoomLabels = [
                                        'qsu_student_center' => 'QSU Student Center - Testing room'
                                    ];
                                    echo $examRoomLabels[$exam['exam_room']] ?? $exam['exam_room'];
                                    ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary"><?php echo date('M d', strtotime($exam['exam_date'])); ?></span><br>
                                <small class="text-muted">
                                    <?php 
                                    $examTimeLabels = [
                                        '08:30' => '8:30 AM - 11:00 AM',
                                        '13:00' => '1:00 PM - 3:30 PM'
                                    ];
                                    echo $examTimeLabels[$exam['exam_time']] ?? $exam['exam_time'];
                                    ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Applicants -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Recent Applicants
                        </h5>
                        <a href="applicants.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>View All
                        </a>
                    </div>

                    <?php if (empty($recentApplicants)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No applicants found</h5>
                        <p class="text-muted">No students have registered yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <?php if (!empty($recentTotal)): ?>
                                Showing applicants <?php echo $recentStart; ?>–<?php echo $recentEnd; ?> of <?php echo $recentTotal; ?>
                            <?php else: ?>
                                Showing <?php echo count($recentApplicants); ?> Recent Applicants
                            <?php endif; ?>
                        </small>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Recent applicants navigation">
                            <?php if ($recentHasPrev): ?>
                                <a href="dashboard.php?recent_page=<?php echo $recentPage - 1; ?>" class="btn btn-outline-secondary">Previous</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary disabled">Previous</span>
                            <?php endif; ?>
                            <?php if ($recentHasNext): ?>
                                <a href="dashboard.php?recent_page=<?php echo $recentPage + 1; ?>" class="btn btn-outline-secondary">Next</a>
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
                                    <th>Progress</th>
                                    <th>Registered</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApplicants as $applicant): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(substr($applicant['first_name'] ?? '', 0, 1) . substr($applicant['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($applicant['name'] ?? 'Unknown Student'); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($applicant['type'] ?? '') === 'Freshman' ? 'info' : 'warning'; ?>">
                                            <?php echo $applicant['type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower($applicant['status']); ?>">
                                            <?php echo $applicant['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 80px; height: 8px;">
                                                <?php 
                                                $progress = 0;
                                                if (($applicant['requirements_count'] ?? 0) > 0) $progress += 25;
                                                if (!empty($applicant['has_admission_form'])) $progress += 25;
                                                if (!empty($applicant['has_test_permit'])) $progress += 25;
                                                if (!empty($applicant['has_f2_form']) || !empty($applicant['has_test_results'])) $progress += 25;
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
                                                    onclick="updateStudentStatus(<?php echo $applicant['id']; ?>, '<?php echo $applicant['status']; ?>', '<?php echo htmlspecialchars($applicant['name']); ?>')" 
                                                    title="Update Status">
                                                <i class="fas fa-edit"></i>
                                                <span class="ms-1">Status</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteStudent(<?php echo $applicant['id']; ?>, '<?php echo htmlspecialchars($applicant['name']); ?>', <?php echo $applicant['requirements_count']; ?>)" 
                                                    title="Delete Student">
                                                <i class="fas fa-trash"></i>
                                                <span class="ms-1">Delete</span>
                                            </button>
                                            <?php 
                                            $hasPDFs = !empty($applicant['has_admission_form']) || !empty($applicant['has_f2_form']) || 
                                                       !empty($applicant['has_approved_test_permit']) || !empty($applicant['has_test_results']);
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
                                                    <?php if (!empty($applicant['has_admission_form'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="../view_pdf.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-file-alt me-2 text-primary"></i>Admission Form
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if (!empty($applicant['has_f2_form'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="view_f2_pdf.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-user-edit me-2 text-success"></i>Personal Data Form
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if (!empty($applicant['has_approved_test_permit'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="../view_test_permit.php?student_id=<?php echo $applicant['id']; ?>" target="_blank">
                                                            <i class="fas fa-id-card me-2 text-warning"></i>Test Permit
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    <?php if (!empty($applicant['has_test_results'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="view_cat_result.php?id=<?php echo $applicant['test_result_id']; ?>" target="_blank">
                                                            <i class="fas fa-chart-line me-2 text-info"></i>CAT Result
                                                        </a>
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

    <!-- Update Student Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">
                        <i class="fas fa-edit me-2"></i>Update Student Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="student_id" id="statusStudentId">

                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="statusStudentName" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="new_status" name="status" required>
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

                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add any remarks or notes..."></textarea>
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
    </div>

    <!-- Delete Student Confirmation Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Delete Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_student">
                        <input type="hidden" name="student_id" id="deleteStudentId">

                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>

                        <p>You are about to permanently delete the following student and all their associated data:</p>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Student Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <span id="deleteStudentName"></span></p>
                                <p class="mb-0"><strong>Requirements:</strong> <span id="deleteRequirementsCount"></span> files</p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6>This will delete:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-danger me-2"></i>Student account and profile</li>
                                <li><i class="fas fa-check text-danger me-2"></i>All uploaded requirements</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Admission form data</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Test permit information</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Personal data form (if submitted)</li>
                                <li><i class="fas fa-check text-danger me-2"></i>Test results (if available)</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="deleteConfirmation" class="form-label">
                                <strong>Type "DELETE" to confirm:</strong>
                            </label>
                            <input type="text" class="form-control" id="deleteConfirmation" 
                                   name="confirmation" placeholder="Type DELETE here" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Personal Data Forms functions
        function printAllF2Forms() {
            window.open('print_all_f2_forms.php', '_blank');
        }

        function downloadAllF2Forms() {
            window.open('download_all_f2_forms.php', '_blank');
        }

        // Test Results functions
        function printAllTestResults() {
            window.open('print_all_test_results.php', '_blank');
        }

        function downloadAllTestResults() {
            window.open('download_all_test_results.php', '_blank');
        }

        // Update student status function
        function updateStudentStatus(studentId, currentStatus, studentName) {
            document.getElementById('statusStudentId').value = studentId;
            document.getElementById('statusStudentName').value = studentName;
            document.getElementById('new_status').value = currentStatus;
            document.getElementById('remarks').value = '';

            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }

        // Delete student function
        function deleteStudent(studentId, studentName, requirementsCount) {
            document.getElementById('deleteStudentId').value = studentId;
            document.getElementById('deleteStudentName').textContent = studentName;
            document.getElementById('deleteRequirementsCount').textContent = requirementsCount;
            document.getElementById('deleteConfirmation').value = '';

            const modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
            modal.show();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var isClickInside = event.target.closest('.dropdown');
            if (!isClickInside) {
                var openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                openDropdowns.forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
