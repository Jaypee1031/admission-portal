<?php
require_once '../config/config.php';
includeFile('includes/auth');
includeFile('includes/requirements');
includeFile('includes/favicon');

if (!isset($auth) || !($auth instanceof Auth)) {
    $auth = new Auth();
}

// Redirect if not logged in as student
requireStudent();

$user = $auth->getCurrentUser();
$studentType = $user['type']; // Use 'type' instead of 'student_type'
$requirements = new Requirements();
$requirementsList = $requirements->getRequirementsList($studentType, $user['id']);
$uploadedRequirements = $requirements->getStudentRequirements($user['id']);
$stats = $requirements->getRequirementStats($user['id']);

// Check if all requirements are uploaded
$allRequirementsUploaded = $requirements->areAllRequirementsUploaded($user['id'], $studentType);

$alert = getAlert();

// Show success message if all requirements are uploaded
if ($allRequirementsUploaded && !$alert) {
    $alert = ['message' => 'All requirements uploaded successfully! You can now proceed to fill out the Pre-Admission Form.', 'type' => 'success'];
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['requirement_file'])) {
    $documentName = sanitizeInput($_POST['document_name']);
    $file = $_FILES['requirement_file'];
    
    if (empty($documentName)) {
        showAlert('Please select a document type', 'error');
    } else {
        $result = $requirements->uploadRequirement($user['id'], $documentName, $file);
        if ($result['success']) {
            showAlert($result['message'], 'success');
        } else {
            showAlert($result['message'], 'error');
        }
        redirect('/student/requirements');
    }
}

// Handle file deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $result = $requirements->deleteRequirement($_GET['delete'], $user['id']);
    if ($result['success']) {
        showAlert($result['message'], 'success');
    } else {
        showAlert($result['message'], 'error');
    }
    redirect('/student/requirements');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Requirements - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../">
                <img src="../assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard">
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
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fas fa-upload me-2 text-primary"></i>
                                Upload Requirements
                            </h2>
                            <p class="text-muted mb-0">
                                Admission Type: <span class="badge bg-primary"><?php echo $studentType; ?></span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <div class="row text-center">
                                <div class="col-3">
                                    <div class="stats-number text-primary"><?php echo $stats['total']; ?></div>
                                    <div class="stats-label">Total</div>
                                </div>
                                <div class="col-3">
                                    <div class="stats-number text-warning"><?php echo $stats['pending']; ?></div>
                                    <div class="stats-label">Pending</div>
                                </div>
                                <div class="col-3">
                                    <div class="stats-number text-success"><?php echo $stats['approved']; ?></div>
                                    <div class="stats-label">Approved</div>
                                </div>
                                <div class="col-3">
                                    <div class="stats-number text-danger"><?php echo $stats['rejected']; ?></div>
                                    <div class="stats-label">Rejected</div>
                                </div>
                            </div>
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

        <!-- Upload Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-4">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Upload New Requirement
                    </h4>
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="document_name" class="form-label">Document Type</label>
                                <select class="form-select" id="document_name" name="document_name" required>
                                    <option value="">Select Document Type</option>
                                    <?php 
                                    $uploadedDocs = array_column($uploadedRequirements, 'document_name');
                                    foreach ($requirementsList as $key => $name): 
                                        $isUploaded = in_array($key, $uploadedDocs);
                                    ?>
                                    <?php if (!$isUploaded): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                
                                <?php if (empty(array_diff(array_keys($requirementsList), $uploadedDocs))): ?>
                                <div class="alert alert-success mt-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>All required documents uploaded!</strong> You can upload additional files using the "Upload Again" buttons below.
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="requirement_file" class="form-label">Choose File</label>
                                <input type="file" class="form-control" id="requirement_file" name="requirement_file" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                <div class="form-text">
                                    Maximum file size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB
                                    <br>Allowed formats: PDF, JPG, PNG, DOC, DOCX
                                </div>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i>Upload File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Requirements List -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-4">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Required Documents for <?php echo $studentType; ?>
                    </h4>
                    
                    <?php if (empty($uploadedRequirements)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-upload display-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No documents uploaded yet</h5>
                        <p class="text-muted">Start by uploading your requirements using the form above.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th class="actions-column text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uploadedRequirements as $req): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $requirementsList[$req['document_name']] ?? $req['document_name']; ?></strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-file me-2"></i>
                                        <?php echo basename($req['file_path']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($req['uploaded_at'])); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower($req['status']); ?>">
                                            <?php echo $req['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($req['remarks']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['remarks']); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-column text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../<?php echo $req['file_path']; ?>" target="_blank" 
                                               class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                                <span class="ms-1">View</span>
                                            </a>
                                            <a href="../<?php echo $req['file_path']; ?>" download 
                                               class="btn btn-outline-success" title="Download">
                                                <i class="fas fa-download"></i>
                                                <span class="ms-1">Download</span>
                                            </a>
                                            <button type="button" class="btn btn-outline-warning" title="Upload Again"
                                                    onclick="uploadAgain('<?php echo $req['document_name']; ?>', '<?php echo $requirementsList[$req['document_name']] ?? $req['document_name']; ?>')">
                                                <i class="fas fa-upload"></i>
                                                <span class="ms-1">Upload</span>
                                            </button>
                                            <a href="?delete=<?php echo $req['id']; ?>" 
                                               class="btn btn-outline-danger" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this file?')">
                                                <i class="fas fa-trash"></i>
                                                <span class="ms-1">Delete</span>
                                            </a>
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

        <!-- Requirements Checklist -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-4">
                        <i class="fas fa-check-square me-2 text-primary"></i>
                        Requirements Checklist
                    </h4>
                    
                    <?php 
                    // Check if student is married
                    $db = getDB();
                    $stmt = $db->prepare("SELECT civil_status FROM admission_forms WHERE student_id = ?");
                    $stmt->execute([$user['id']]);
                    $admissionForm = $stmt->fetch();
                    $isMarried = ($admissionForm && $admissionForm['civil_status'] === 'Married');
                    ?>
                    
                    <?php if (!$isMarried): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Marriage Certificate is only required for married students. 
                        If you are not married, you can skip this requirement.
                    </div>
                    <?php endif; ?>
                    <div class="row">
                        <?php 
                        $uploadedDocs = array_column($uploadedRequirements, 'document_name');
                        foreach ($requirementsList as $key => $name): 
                            $isUploaded = in_array($key, $uploadedDocs);
                            $uploadedReq = null;
                            if ($isUploaded) {
                                foreach ($uploadedRequirements as $req) {
                                    if ($req['document_name'] === $key) {
                                        $uploadedReq = $req;
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($isUploaded): ?>
                                            <i class="fas fa-check-circle text-success fa-2x"></i>
                                            <?php else: ?>
                                            <i class="fas fa-circle text-muted fa-2x"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo $name; ?></h6>
                                            <?php if ($isUploaded && $uploadedReq): ?>
                                            <small class="text-muted">
                                                Status: <span class="badge status-<?php echo strtolower($uploadedReq['status']); ?>">
                                                    <?php echo $uploadedReq['status']; ?>
                                                </span>
                                            </small>
                                            <?php else: ?>
                                            <small class="text-muted">Not uploaded</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($requirements->areAllRequirementsUploaded($user['id'], $studentType)): ?>
                    <div class="alert alert-success mt-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>All documents uploaded!</strong> You have successfully uploaded all required documents. 
                        Now you can proceed to fill out your Pre-Admission Form and request a Test Permit.
                        <div class="mt-3">
                            <a href="admission_form.php" class="btn btn-success">
                                <i class="fas fa-arrow-right me-2"></i>Fill Pre-Admission Form
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Information about approval process -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Document Approval Process:</strong> Your uploaded documents will be automatically approved when an administrator approves your Test Permit request. 
                        You don't need to wait for individual document approvals - just upload all required documents and proceed with your application.
                        <br><br>
                        <strong>Workflow:</strong> Upload Documents → Fill Pre-Admission Form → Request Test Permit → Admin Approval (approves both Test Permit and Documents)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Again Modal -->
    <div class="modal fade" id="uploadAgainModal" tabindex="-1" aria-labelledby="uploadAgainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadAgainModalLabel">
                        <i class="fas fa-upload me-2"></i>Upload Again
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to upload a new file for: <strong id="modalDocumentName"></strong></p>
                    <p class="text-muted">This will replace your existing file. Are you sure you want to continue?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmUploadAgain">
                        <i class="fas fa-upload me-2"></i>Upload New File
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload validation and display
        document.getElementById('requirement_file').addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = <?php echo MAX_FILE_SIZE; ?>;
            
            if (file) {
                // Show file name
                const fileLabel = this.nextElementSibling;
                if (fileLabel && fileLabel.classList.contains('form-text')) {
                    const fileName = file.name;
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    
                    // Update the form text to show selected file
                    fileLabel.innerHTML = `
                        <div class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Selected: <strong>${fileName}</strong> (${fileSize} MB)
                        </div>
                        Maximum file size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB
                        <br>Allowed formats: PDF, JPG, PNG, DOC, DOCX
                    `;
                }
                
                // Validate file size
                if (file.size > maxSize) {
                    alert('File size exceeds maximum limit of <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB');
                    this.value = '';
                    // Reset the display
                    const fileLabel = this.nextElementSibling;
                    if (fileLabel && fileLabel.classList.contains('form-text')) {
                        fileLabel.innerHTML = `
                            Maximum file size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB
                            <br>Allowed formats: PDF, JPG, PNG, DOC, DOCX
                        `;
                    }
                }
            } else {
                // Reset display when no file selected
                const fileLabel = this.nextElementSibling;
                if (fileLabel && fileLabel.classList.contains('form-text')) {
                    fileLabel.innerHTML = `
                        Maximum file size: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB
                        <br>Allowed formats: PDF, JPG, PNG, DOC, DOCX
                    `;
                }
            }
        });

        // Form submission with loading state
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const documentSelect = document.getElementById('document_name');
            const fileInput = document.getElementById('requirement_file');
            
            // Validate form before submission
            if (!documentSelect.value) {
                e.preventDefault();
                alert('Please select a document type');
                documentSelect.focus();
                return;
            }
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a file to upload');
                fileInput.focus();
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            submitBtn.disabled = true;
        });

        // Upload Again functionality
        let currentDocumentKey = '';
        let currentDocumentName = '';
        
        function uploadAgain(documentKey, documentName) {
            currentDocumentKey = documentKey;
            currentDocumentName = documentName;
            
            // Update modal content
            document.getElementById('modalDocumentName').textContent = documentName;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('uploadAgainModal'));
            modal.show();
        }
        
        // Handle confirm upload again
        document.getElementById('confirmUploadAgain').addEventListener('click', function() {
            // Set the document type in the dropdown
            const documentSelect = document.getElementById('document_name');
            documentSelect.value = currentDocumentKey;
            
            // Focus on the file input
            const fileInput = document.getElementById('requirement_file');
            fileInput.focus();
            
            // Scroll to the upload form
            document.getElementById('uploadForm').scrollIntoView({ behavior: 'smooth' });
            
            // Show a message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                <strong>Upload Again:</strong> Please select a new file for "${currentDocumentName}". This will replace your existing file.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert the alert before the upload form
            const uploadForm = document.getElementById('uploadForm');
            uploadForm.parentNode.insertBefore(alertDiv, uploadForm);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('uploadAgainModal'));
            modal.hide();
        });
    </script>
</body>
</html>
