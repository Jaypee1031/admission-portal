<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/test_results.php';
require_once '../includes/favicon.php';

// Delegate to MVC controller (new implementation)
$controller = new StudentDashboardController();
$controller->index();
exit;
?>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-user-graduate me-2 text-primary"></i>
                                Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>!
                            </h2>
                            <p class="text-muted mb-3">
                                Admission Type: <span class="badge bg-primary"><?php echo $user['student_type']; ?></span>
                                | Status: <span class="badge status-<?php echo strtolower($user['status']); ?>"><?php echo $user['status']; ?></span>
                            </p>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%"></div>
                            </div>
                            <small class="text-muted">Application Progress: <?php echo round($progressPercentage); ?>% Complete</small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $completedSteps; ?>/<?php echo $totalSteps; ?></div>
                                <div class="stats-label">Steps Completed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step-by-Step Tutorial -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-4">
                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                        How to Complete Your Admission Process
                    </h4>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Follow these steps in order to complete your university admission successfully.</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Step 1: Registration -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo $progress['requirements'] ? 'completed' : 'current'; ?>">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-upload me-2"></i>Upload Requirements</h5>
                                    <p class="text-muted">Upload all required documents for your admission type.</p>
                                    <ul class="step-details">
                                        <li>Valid ID (PSA, Birth Certificate)</li>
                                        <li>Form 138 (Report Card)</li>
                                        <li>Good Moral Certificate</li>
                                        <li>2x2 ID Picture</li>
                                        <?php if ($user['student_type'] === 'Transferee'): ?>
                                        <li>Transcript of Records</li>
                                        <li>Honorable Dismissal</li>
                                        <?php endif; ?>
                                    </ul>
                                    <?php if (!$progress['requirements']): ?>
                                    <a href="requirements.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-upload me-1"></i>Start Upload
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Admission Form -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo $progress['admission_form'] ? 'completed' : ($progress['requirements'] ? 'current' : 'locked'); ?>">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-file-alt me-2"></i>Fill Admission Form</h5>
                                    <p class="text-muted">Complete your personal and educational information.</p>
                                    <ul class="step-details">
                                        <li>Personal Information</li>
                                        <li>Contact Details</li>
                                        <li>Family Background</li>
                                        <li>Educational Background</li>
                                        <li>Course Preferences</li>
                                    </ul>
                                    <?php if ($progress['requirements'] && !$progress['admission_form']): ?>
                                    <a href="admission_form.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit me-1"></i>Fill Form
                                    </a>
                                    <?php elseif ($progress['admission_form']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Locked
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Test Permit -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo $progress['test_permit_approved'] ? 'completed' : ($progress['admission_form'] ? 'current' : 'locked'); ?>">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-id-card me-2"></i>Request Test Permit</h5>
                                    <p class="text-muted">Generate your entrance exam permit.</p>
                                    <ul class="step-details">
                                        <li>Review your information</li>
                                        <li>Generate test permit</li>
                                        <li>Wait for admin approval</li>
                                        <li>Print approved permit</li>
                                    </ul>
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        <div style="color: black;">
                                            <strong>Review Process:</strong>
                                            <br>
                                            <small style="color: black;">Your test permit request is now in the approval queue. QSU administration will verify your information and approve your permit within <strong>1-2 days</strong>. Please check this website after 1-2 days for updates.</small>
                                        </div>
                                    </div>
                                    <?php if ($progress['admission_form'] && !$progress['test_permit']): ?>
                                    <a href="test_permit.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-id-card me-1"></i>Request Permit
                                    </a>
                                    <?php elseif ($progress['test_permit'] && !$progress['test_permit_approved']): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Under Review
                                    </span>
                                    <?php elseif ($progress['test_permit_approved']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Approved
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Locked
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Take Exam -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo ($progress['exam_completed'] || $progress['test_results_available']) ? 'completed' : ($progress['test_permit_approved'] ? 'current' : 'locked'); ?>">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-graduation-cap me-2"></i>Take Entrance Exam</h5>
                                    <p class="text-muted">Attend your scheduled entrance examination.</p>
                                    <ul class="step-details">
                                        <li>Bring your test permit</li>
                                        <li>Arrive 30 minutes early</li>
                                        <li>Bring Pen (Black)</li>
                                        <li>Complete the exam</li>
                                    </ul>
                                    <?php if ($progress['test_permit_approved'] && !$progress['exam_completed'] && !$progress['test_results_available']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-calendar me-1"></i>Schedule Exam
                                    </span>
                                    <?php elseif ($progress['exam_completed'] || $progress['test_results_available']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Locked
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Personal Data Form -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo $progress['f2_form_completed'] ? 'completed' : ($progress['f2_form_enabled'] ? 'current' : 'locked'); ?>">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-user-edit me-2"></i>Personal Data Form</h5>
                                    <p class="text-muted">Complete additional personal information.</p>
                                    <ul class="step-details">
                                        <li>Detailed personal info</li>
                                        <li>Family background</li>
                                        <li>Emergency contacts</li>
                                        <li>Medical information</li>
                                    </ul>
                                    <div class="alert alert-warning mt-2 mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <div style="color: black;">
                                            <strong>Status:</strong>
                                            <br>
                                            <small style="color: black;">This form is currently locked. QSU administration will unlock it when ready.</small>
                                        </div>
                                    </div>
                                    <?php if ($progress['f2_form_enabled'] && !$progress['f2_form_completed']): ?>
                                    <a href="f2_personal_data_form.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user-edit me-1"></i>Fill Form
                                    </a>
                                    <?php elseif ($progress['f2_form_completed']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Not Available
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: Test Results -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="tutorial-step <?php echo $progress['test_results_available'] ? 'completed' : 'locked'; ?>">
                                <div class="step-number">6</div>
                                <div class="step-content">
                                    <h5><i class="fas fa-chart-line me-2"></i>View Test Results</h5>
                                    <p class="text-muted">Check your entrance exam results.</p>
                                    <ul class="step-details">
                                        <li>Exam scores</li>
                                        <li>Overall rating</li>
                                        <li>Admission status</li>
                                        <li>Next steps</li>
                                    </ul>
                                    <?php if ($progress['test_results_available']): ?>
                                    <a href="test_results.php" class="btn btn-info btn-sm">
                                        <i class="fas fa-chart-line me-1"></i>View Results
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Not Available
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notes:</h6>
                                <ul class="mb-0">
                                    <li><strong>Complete steps in order:</strong> Each step must be completed before moving to the next.</li>
                                    <li><strong>Document requirements:</strong> Ensure all uploaded documents are clear and valid.</li>
                                    <li><strong>Test permit:</strong> Must be approved before taking the entrance exam.</li>
                                    <li><strong>Contact support:</strong> If you encounter any issues, contact the admission office.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-4">
                        <i class="fas fa-tasks me-2 text-primary"></i>
                        Application Progress
                    </h4>
                    <div class="progress-steps">
                        <div class="progress-step completed">
                            <i class="fas fa-user-check"></i>
                            <div class="progress-step-label">Registration</div>
                            <small>Completed</small>
                        </div>
                        <div class="progress-step <?php echo $progress['requirements'] ? 'completed' : ($currentStep == 1 ? 'active' : ''); ?>">
                            <i class="fas fa-<?php echo $progress['requirements'] ? 'check-circle' : 'upload'; ?>"></i>
                            <div class="progress-step-label">Requirements Upload</div>
                            <small><?php echo $progress['requirements'] ? 'Completed' : ($currentStep == 1 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                        <div class="progress-step <?php echo $progress['admission_form'] ? 'completed' : ($currentStep == 2 ? 'active' : ''); ?>">
                            <i class="fas fa-file-alt"></i>
                            <div class="progress-step-label">Pre-Admission Form</div>
                            <small><?php echo $progress['admission_form'] ? 'Completed' : ($currentStep == 2 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                        <div class="progress-step <?php echo $progress['test_permit_approved'] ? 'completed' : ($currentStep == 3 ? 'active' : ''); ?>">
                            <i class="fas fa-id-card"></i>
                            <div class="progress-step-label">Test Permit</div>
                            <small><?php echo $progress['test_permit_approved'] ? 'Completed' : ($currentStep == 3 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                        <div class="progress-step <?php echo ($progress['exam_completed'] || $progress['test_results_available']) ? 'completed' : ($currentStep == 4 ? 'active' : ''); ?>">
                            <i class="fas fa-graduation-cap"></i>
                            <div class="progress-step-label">Take Exam</div>
                            <small><?php echo ($progress['exam_completed'] || $progress['test_results_available']) ? 'Completed' : ($currentStep == 4 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                        <div class="progress-step <?php echo $progress['f2_form_completed'] ? 'completed' : ($currentStep == 5 ? 'active' : ''); ?>">
                            <i class="fas fa-user-edit"></i>
                            <div class="progress-step-label">Personal Data</div>
                            <small><?php echo $progress['f2_form_completed'] ? 'Completed' : ($currentStep == 5 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                        <div class="progress-step <?php echo $progress['test_results_available'] ? 'completed' : ($currentStep == 6 ? 'active' : ''); ?>">
                            <i class="fas fa-chart-line"></i>
                            <div class="progress-step-label">Test Results</div>
                            <small><?php echo $progress['test_results_available'] ? 'Completed' : ($currentStep == 6 ? 'In progress' : 'Pending'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Status -->
        <?php if ($approvalStatus && $approvalStatus['permit_status']): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card <?php echo $approvalStatus['permit_status'] === 'Approved' ? 'bg-success bg-opacity-10 border-success' : ($approvalStatus['permit_status'] === 'Rejected' ? 'bg-danger bg-opacity-10 border-danger' : 'bg-warning bg-opacity-10 border-warning'); ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <i class="fas fa-<?php echo $approvalStatus['permit_status'] === 'Approved' ? 'check-circle text-success' : ($approvalStatus['permit_status'] === 'Rejected' ? 'times-circle text-danger' : 'clock text-warning'); ?> me-2"></i>
                                Test Permit Status: <?php echo $approvalStatus['permit_status']; ?>
                            </h5>
                            <p class="text-muted mb-0">
                                <?php if ($approvalStatus['permit_status'] === 'Approved'): ?>
                                    Congratulations! Your test permit has been approved. All your requirements have also been approved.
                                    <?php if ($approvalStatus['permit_approved_at']): ?>
                                        <br><small>Approved on: <?php echo date('M d, Y H:i', strtotime($approvalStatus['permit_approved_at'])); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($approvalStatus['permit_status'] === 'Rejected'): ?>
                                    Your test permit has been rejected. Please contact the administration for more information.
                                <?php else: ?>
                                    Your test permit is currently under review. Please wait for admin approval.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="ms-3">
                            <?php if ($approvalStatus['permit_status'] === 'Approved'): ?>
                            <a href="documents.php" class="btn btn-success">
                                <i class="fas fa-print me-2"></i>Print Documents
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                                <i class="fas fa-user me-2"></i>Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                                <i class="fas fa-folder me-2"></i>Documents
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab">
                                <i class="fas fa-upload me-2"></i>Requirements
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="forms-tab" data-bs-toggle="tab" data-bs-target="#forms" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Forms
                            </button>
                        </li>
                        <?php if ($f2FormEnabled): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="f2-form-tab" data-bs-toggle="tab" data-bs-target="#f2-form" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Personal Data
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php if ($testResultAvailable): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-results-tab" data-bs-toggle="tab" data-bs-target="#test-results" type="button" role="tab">
                                <i class="fas fa-chart-line me-2"></i>Test Results
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="tab-content mt-4" id="dashboardTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Personal Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Full Name:</strong></td>
                                            <td><?php echo htmlspecialchars($user['name'] ?? 'Student'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Admission Type:</strong></td>
                                            <td><?php echo $user['student_type']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge status-<?php echo strtolower($user['student_status']); ?>">
                                                    <?php echo $user['student_status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Quick Actions</h5>
                                    <div class="d-grid gap-2">
                                        <!-- Step 1: Upload Requirements -->
                                        <?php if (!$progress['requirements']): ?>
                                        <a href="requirements.php" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Upload Requirements
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-check me-2"></i>Requirements Uploaded
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Step 2: Fill Admission Form -->
                                        <?php if ($progress['requirements'] && !$progress['admission_form']): ?>
                                        <a href="admission_form.php" class="btn btn-primary">
                                            <i class="fas fa-file-alt me-2"></i>Fill Admission Form
                                        </a>
                                        <?php elseif ($progress['admission_form']): ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-check me-2"></i>Admission Form Complete
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-lock me-2"></i>Complete Requirements First
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Step 3: Request Test Permit -->
                                        <?php if ($progress['admission_form'] && !$progress['test_permit']): ?>
                                        <a href="test_permit.php" class="btn btn-primary">
                                            <i class="fas fa-id-card me-2"></i>Request Test Permit
                                        </a>
                                        <?php elseif ($progress['test_permit'] && !$progress['test_permit_approved']): ?>
                                        <button class="btn btn-warning" disabled>
                                            <i class="fas fa-clock me-2"></i>Waiting for Approval
                                        </button>
                                        <?php elseif ($progress['test_permit_approved']): ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-check me-2"></i>Test Permit Approved
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-lock me-2"></i>Complete Admission Form First
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Step 4: Take Exam -->
                                        <?php if ($progress['test_permit_approved'] && !$progress['exam_completed'] && !$progress['test_results_available']): ?>
                                        <button class="btn btn-info" disabled>
                                            <i class="fas fa-graduation-cap me-2"></i>Go to School for Exam
                                        </button>
                                        <?php elseif ($progress['exam_completed'] || $progress['test_results_available']): ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-check me-2"></i>Exam Completed
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-lock me-2"></i>Wait for Test Permit Approval
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Step 5: Fill F2 Personal Data -->
                                        <?php if ($progress['f2_form_enabled'] && !$progress['f2_form_completed']): ?>
                                        <a href="f2_personal_data_form.php" class="btn btn-primary">
                                            <i class="fas fa-user-edit me-2"></i>Fill Personal Data
                                        </a>
                                        <?php elseif ($progress['f2_form_completed']): ?>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-check me-2"></i>Personal Data Complete
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-warning" disabled>
                                            <i class="fas fa-clock me-2"></i>Waiting for Personal Data Access
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Step 6: View Test Results -->
                                        <?php if ($progress['test_results_available']): ?>
                                        <a href="test_results.php" class="btn btn-info">
                                            <i class="fas fa-chart-line me-2"></i>View Test Results
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-lock me-2"></i>Test Results Not Available
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements Tab -->
                        <div class="tab-pane fade" id="requirements" role="tabpanel">
                            <div class="text-center">
                                <h5 class="mb-3">Upload Your Requirements</h5>
                                <p class="text-muted mb-4">Please upload all required documents for your admission type.</p>
                                <a href="requirements.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Requirements
                                </a>
                            </div>
                        </div>

                        <!-- Forms Tab -->
                        <div class="tab-pane fade" id="forms" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="feature-icon bg-primary text-white rounded-circle mx-auto mb-3">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <h5 class="card-title">Pre-Admission Form</h5>
                                            <p class="card-text">Fill out your personal and educational information.</p>
                                            <?php if ($progress['admission_form']): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>Form Completed
                                            </div>
                                            <button class="btn btn-outline-primary" onclick="viewAdmissionForm()">
                                                <i class="fas fa-eye me-2"></i>View Form
                                            </button>
                                            <?php else: ?>
                                            <?php if ($progress['requirements']): ?>
                                            <a href="admission_form.php" class="btn btn-primary">
                                                <i class="fas fa-edit me-2"></i>Fill Form
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-lock me-2"></i>Complete Requirements First
                                            </button>
                                            <small class="text-muted d-block mt-2">Upload all required documents first</small>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="feature-icon bg-success text-white rounded-circle mx-auto mb-3">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <h5 class="card-title">Test Permit</h5>
                                            <p class="card-text">Generate your entrance exam permit.</p>
                                            <?php if ($progress['test_permit']): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>Permit Generated
                                            </div>
                                            <button class="btn btn-outline-primary" onclick="viewTestPermit()">
                                                <i class="fas fa-eye me-2"></i>View Permit
                                            </button>
                                            <?php else: ?>
                                            <?php if ($progress['admission_form']): ?>
                                            <a href="test_permit.php" class="btn btn-primary">
                                                <i class="fas fa-id-card me-2"></i>Generate Permit
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-lock me-2"></i>Complete Form First
                                            </button>
                                            <small class="text-muted d-block mt-2">Complete admission form first</small>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="feature-icon bg-info text-white rounded-circle mx-auto mb-3">
                                                <i class="fas fa-folder-open"></i>
                                            </div>
                                            <h5 class="card-title">Document Center</h5>
                                            <p class="card-text">View, download, and print all your documents.</p>
                                            <a href="documents.php" class="btn btn-primary">
                                                <i class="fas fa-folder-open me-2"></i>Open Document Center
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Data Tab -->
                        <?php if ($f2FormEnabled): ?>
                        <div class="tab-pane fade" id="f2-form" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="feature-icon bg-success text-white rounded-circle mx-auto mb-3">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <h5 class="card-title">F2 Personal Data Form</h5>
                                            <p class="card-text">Fill out additional personal information required for admission.</p>
                                            <?php if ($f2FormCompleted): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Personal Data form has been completed and submitted.
                                            </div>
                                            <a href="f2_personal_data_form.php" class="btn btn-outline-primary">
                                                <i class="fas fa-edit me-2"></i>Update Form
                                            </a>
                                            <a href="view_f2_pdf.php" class="btn btn-outline-info mt-2" target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>View PDF
                                            </a>
                                            <?php elseif ($progress['f2_form_enabled']): ?>
                                            <a href="f2_personal_data_form.php" class="btn btn-success">
                                                <i class="fas fa-file-alt me-2"></i>Fill Personal Data Form
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-warning" disabled>
                                                <i class="fas fa-clock me-2"></i>Personal Data Form Not Available
                                            </button>
                                            <small class="text-muted d-block mt-2">Personal Data form access will be enabled by administrator.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Test Results Tab -->
                        <?php if ($testResultAvailable): ?>
                        <div class="tab-pane fade" id="test-results" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="feature-icon bg-info text-white rounded-circle mx-auto mb-3">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <h5 class="card-title">Test Results</h5>
                                            <p class="card-text">View your college admission test results and performance.</p>
                                            <?php if ($testResult): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Your test results are now available.
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <h4 class="text-primary"><?php echo number_format($testResult['exam_rating'] ?? 0, 1); ?></h4>
                                                        <small class="text-muted">Exam Rating</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <span class="badge bg-<?php 
                                                            echo $testResult['overall_rating'] === 'Excellent' ? 'success' : 
                                                                ($testResult['overall_rating'] === 'Very Good' ? 'info' :
                                                                ($testResult['overall_rating'] === 'Passed' || $testResult['overall_rating'] === 'PASSED' ? 'success' : 
                                                                ($testResult['overall_rating'] === 'Conditional' ? 'warning' : 'danger'))); 
                                                        ?> fs-6">
                                                            <?php echo htmlspecialchars($testResult['overall_rating'] ?? 'N/A'); ?>
                                                        </span>
                                                        <br><small class="text-muted">Overall Rating</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="test_results.php" class="btn btn-info">
                                                <i class="fas fa-chart-line me-2"></i>View Full Results
                                            </a>
                                            <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-clock me-2"></i>
                                                Test results are being processed. Please check back later.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pre-Admission Form Modal -->
    <div class="modal fade" id="admissionFormModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>Pre-Admission Form
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="admissionFormFrame" src="admission_form.php" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Permit Modal -->
    <div class="modal fade" id="testPermitModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-id-card me-2"></i>Test Permit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="testPermitFrame" src="test_permit.php" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchTab(tabId) {
            const tab = document.getElementById(tabId);
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }

        function openAdmissionForm() {
            const modal = new bootstrap.Modal(document.getElementById('admissionFormModal'));
            modal.show();
        }

        function viewAdmissionForm() {
            window.open('admission_form.php', '_blank');
        }

        function openTestPermit() {
            const modal = new bootstrap.Modal(document.getElementById('testPermitModal'));
            modal.show();
        }

        function viewTestPermit() {
            window.open('test_permit.php', '_blank');
        }

        // Auto-refresh progress every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);

        // Fix form input auto-capitalization
        function fixFormInputs() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], textarea');
            inputs.forEach(input => {
                // Prevent auto-capitalization
                input.style.textTransform = 'none';
                input.setAttribute('autocapitalize', 'none');
                input.setAttribute('autocorrect', 'off');
                
                // Set specific autocapitalize based on field type
                const name = input.name.toLowerCase();
                if (name.includes('name') && !name.includes('email') && !name.includes('phone') && !name.includes('number')) {
                    input.setAttribute('autocapitalize', 'words');
                } else if (name.includes('email')) {
                    input.setAttribute('autocapitalize', 'none');
                    input.setAttribute('autocorrect', 'off');
                } else if (name.includes('phone') || name.includes('number') || name.includes('contact')) {
                    input.setAttribute('autocapitalize', 'none');
                    input.setAttribute('autocorrect', 'off');
                    input.setAttribute('inputmode', 'tel');
                } else if (name.includes('address')) {
                    input.setAttribute('autocapitalize', 'words');
                } else if (name.includes('occupation') || name.includes('school') || name.includes('university')) {
                    input.setAttribute('autocapitalize', 'words');
                }
            });
        }

        // Apply fixes when page loads
        document.addEventListener('DOMContentLoaded', fixFormInputs);
        
        // Apply fixes when forms are dynamically loaded
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    fixFormInputs();
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    </script>
</body>
</html>
