<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/requirements.php';
require_once '../includes/admission_form.php';
require_once '../includes/test_permit.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$studentId = $user['id'];

// Get additional student data including Personal Data form status
$db = getDB();
$stmt = $db->prepare("SELECT f2_form_enabled, f2_form_completed, test_result_available FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$studentData = $stmt->fetch();
if ($studentData) {
    $user = array_merge($user, $studentData);
}

// Initialize classes
$requirements = new Requirements();
$admissionForm = new AdmissionForm();
$testPermit = new TestPermit();
$f2Form = new F2PersonalDataForm();

// Get all documents
$uploadedRequirements = $requirements->getStudentRequirements($studentId);
$admissionFormData = $admissionForm->getAdmissionForm($studentId);
$testPermitData = $testPermit->getTestPermit($studentId);
$f2FormData = $f2Form->getF2FormData($studentId);

// Get requirements list for display names
$requirementsList = $requirements->getRequirementsList($user['type']);

// Check if PDFs should be available
$admissionFormPDF = null;
$testPermitPDF = null;
$f2FormPDF = null;

// Admission form PDF is available if form is completed
if ($admissionFormData) {
    $admissionFormPDF = true; // We'll generate it on demand
}

// Test permit PDF is available if permit exists and is approved
if ($testPermitData && $testPermitData['status'] === 'Approved') {
    $testPermitPDF = true; // We'll generate it on demand
}

// Personal Data form PDF is available if form is completed
if ($f2FormData && $f2FormData['submitted_at']) {
    $f2FormPDF = true; // We'll generate it on demand
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Center - <?php echo SITE_NAME; ?></title>
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
                <span class="navbar-text me-3">
                    <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>
                </span>
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
                        <i class="fas fa-folder me-2 text-primary"></i>
                        Document Center
                    </h2>
                    <p class="text-muted mb-0">Manage all your admission documents and forms.</p>
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


        <!-- Document Categories -->
        <div class="row">
            <!-- Uploaded Requirements -->
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-upload me-2 text-primary"></i>
                        Uploaded Requirements
                    </h5>
                    
                    <?php if (empty($uploadedRequirements)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-upload display-1 text-muted mb-3"></i>
                        <h6 class="text-muted">No requirements uploaded</h6>
                        <a href="requirements.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Upload Requirements
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($uploadedRequirements as $req): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($requirementsList[$req['document_name']] ?? $req['document_name'] ?? 'Unknown Document'); ?></strong><br>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($req['uploaded_at'])); ?></small>
                            </div>
                            <div class="d-flex gap-1">
                                <span class="badge status-<?php echo strtolower($req['status']); ?>">
                                    <?php echo $req['status']; ?>
                                </span>
                                <div class="btn-group btn-group-sm">
                                    <a href="../<?php echo $req['file_path']; ?>" target="_blank" 
                                       class="btn btn-outline-primary document-action-btn" title="View">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <a href="../<?php echo $req['file_path']; ?>" download 
                                       class="btn btn-outline-success document-action-btn" title="Download">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <button onclick="printDocument('../<?php echo $req['file_path']; ?>')" 
                                            class="btn btn-outline-warning document-action-btn" title="Print">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Generated Forms -->
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <h5 class="mb-3">
                        <i class="fas fa-file-pdf me-2 text-primary"></i>
                        Generated Forms
                    </h5>
                    
                    <div class="list-group list-group-flush">
                        <!-- Pre-Admission Form -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>Pre-Admission Form</strong><br>
                                <small class="text-muted">
                                    <?php if ($admissionFormData): ?>
                                        <?php if ($testPermitData && $testPermitData['status'] === 'Approved'): ?>
                                            Status: <span class="badge bg-success">Approved</span>
                                            <br>Completed on <?php echo date('M d, Y', strtotime($admissionFormData['created_at'])); ?>
                                        <?php else: ?>
                                            Completed on <?php echo date('M d, Y', strtotime($admissionFormData['created_at'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Not completed
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?php if ($admissionFormData): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="admission_form.php" class="btn btn-outline-primary document-action-btn" title="View/Edit">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <?php if ($admissionFormPDF): ?>
                                    <a href="../view_pdf.php" target="_blank" 
                                       class="btn btn-outline-info document-action-btn" title="View PDF">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                    <a href="../view_pdf.php?download=1" download 
                                       class="btn btn-outline-success document-action-btn" title="Download PDF">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <button onclick="printDocument('../view_pdf.php')" 
                                            class="btn btn-outline-warning document-action-btn" title="Print PDF">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <a href="admission_form.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Create
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Test Permit -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>Test Permit</strong><br>
                                <small class="text-muted">
                                    <?php if ($testPermitData): ?>
                                        Status: <span class="badge bg-<?php echo $testPermitData['status'] === 'Approved' ? 'success' : ($testPermitData['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $testPermitData['status']; ?>
                                        </span>
                                        <br>Requested on <?php echo $testPermitData['issued_at'] ? date('M d, Y', strtotime($testPermitData['issued_at'])) : 'Unknown'; ?>
                                    <?php else: ?>
                                        Not requested
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?php if ($testPermitData): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="test_permit.php" class="btn btn-outline-primary document-action-btn" title="View Status">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <?php if ($testPermitData['status'] === 'Approved'): ?>
                                    <a href="<?php echo SITE_URL; ?>/view_test_permit.php" target="_blank" 
                                       class="btn btn-outline-info document-action-btn" title="View PDF">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/view_test_permit.php" download 
                                       class="btn btn-outline-success document-action-btn" title="Download PDF">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <button onclick="printDocument('<?php echo SITE_URL; ?>/view_test_permit.php')" 
                                            class="btn btn-outline-warning document-action-btn" title="Print PDF">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <a href="test_permit.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Request
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Personal Data Form -->
                        <?php if ($user['f2_form_enabled']): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>Personal Data Form</strong><br>
                                <small class="text-muted">
                                    <?php if ($f2FormData && $f2FormData['submitted_at']): ?>
                                        Status: <span class="badge bg-success">Completed</span>
                                        <br>Submitted on <?php echo date('M d, Y', strtotime($f2FormData['submitted_at'])); ?>
                                    <?php else: ?>
                                        Not completed
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?php if ($f2FormData): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="f2_personal_data_form.php" class="btn btn-outline-primary document-action-btn" title="View/Edit">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <?php if ($f2FormPDF): ?>
                                    <a href="<?php echo SITE_URL; ?>/student/view_f2_pdf.php" target="_blank" 
                                       class="btn btn-outline-info document-action-btn" title="View PDF">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/student/view_f2_pdf.php" download 
                                       class="btn btn-outline-success document-action-btn" title="Download PDF">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <button onclick="printDocument('<?php echo SITE_URL; ?>/student/view_f2_pdf.php')" 
                                            class="btn btn-outline-warning document-action-btn" title="Print PDF">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <a href="f2_personal_data_form.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Create
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printDocument(filePath) {
            // Open document in new window for printing
            const printWindow = window.open(filePath, '_blank');
            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            } else {
                alert('Please allow popups to print documents.');
            }
        }

        function printAllDocuments() {
            // Print admission form PDF
            <?php if ($admissionFormPDF): ?>
            setTimeout(() => {
                printDocument('../<?php echo $admissionFormPDF; ?>');
            }, 1000);
            <?php endif; ?>
            
            // Print test permit PDF
            <?php if ($testPermitPDF): ?>
            setTimeout(() => {
                printDocument('../<?php echo $testPermitPDF; ?>');
            }, 2000);
            <?php endif; ?>
            
            // Print uploaded requirements
            <?php foreach ($uploadedRequirements as $req): ?>
            setTimeout(() => {
                printDocument('../<?php echo $req['file_path']; ?>');
            }, <?php echo (array_search($req, $uploadedRequirements) + 3) * 1000; ?>);
            <?php endforeach; ?>
        }
    </script>
</body>
</html>
