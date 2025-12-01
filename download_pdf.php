<?php
// PDF Downloader for Admission Forms - Generate PDF directly with database data
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/test_permit.php';
require_once 'includes/qsu_simple_generator.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

// Get current user
$user = $auth->getCurrentUser();

// Determine which student ID to use:
// - If admin passes ?student_id=, allow viewing that student's form
// - Else if logged in student, use their own ID
// - Otherwise redirect
$studentId = null;
if (isAdmin() && isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];
} elseif (isStudent()) {
    $studentId = $user['id'];
} else {
    redirect('index.php');
}

// Check if student has completed test permit (only for students, not admins)
$testPermit = new TestPermit();
if (isStudent() && !$testPermit->hasTestPermit($studentId)) {
    // Show error message and redirect
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - ' . SITE_NAME . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Access Denied
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                            <h4>Test Permit Required</h4>
                            <p class="text-muted">You need to request your test permit before you can download this PDF.</p>
                            <div class="mt-4">
                                <a href="student/test_permit.php" class="btn btn-warning me-2">
                                    <i class="fas fa-file-alt me-2"></i>Request Test Permit
                                </a>
                                <a href="student/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Generate PDF directly from database data
$pdfGenerator = new QSUSimpleGenerator();
$result = $pdfGenerator->generateAdmissionFormPDF($studentId);

if ($result['success']) {
    // Generate a user-friendly filename
    $userFriendlyName = 'QSU_F1A_Admission_Form_' . date('Y-m-d') . '.pdf';
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $userFriendlyName . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output the generated PDF content
    $pdfPath = PDF_PATH . 'admission_forms/' . $result['file_name'];
    if (file_exists($pdfPath)) {
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
    } else {
        die('PDF file not found');
    }
    exit;
} else {
    die('PDF generation failed: ' . $result['message']);
}
?>
