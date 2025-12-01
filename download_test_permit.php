<?php
// Start output buffering to prevent any output before PDF
ob_start();

require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/test_permit.php';
require_once 'includes/test_permit_pdf.php';

// Determine which student ID to render for:
// - If admin passes ?student_id=, allow download for that student
// - Else if logged-in student, use their own ID
// - Otherwise redirect to home
$studentId = null;
if (isAdmin() && isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];
} elseif (isStudent()) {
    $user = $auth->getCurrentUser();
    $studentId = $user['id'];
} else {
    ob_end_clean(); // Clear any output buffer
    redirect('index.php');
}

// Initialize classes
$testPermit = new TestPermit();

// Get test permit data
$permitData = $testPermit->getTestPermit($studentId);

if (!$permitData) {
    ob_end_clean(); // Clear any output buffer
    showAlert('No test permit found. Please request a test permit first.', 'error');
    if (isAdmin()) {
        redirect('admin/test_permits.php');
    } else {
        redirect('student/test_permit.php');
    }
}

// Get student data and admission form data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$studentData = $stmt->fetch();

if (!$studentData) {
    ob_end_clean(); // Clear any output buffer
    showAlert('Student data not found.', 'error');
    if (isAdmin()) {
        redirect('admin/test_permits.php');
    } else {
        redirect('student/dashboard.php');
    }
}

// Get admission form data for course information
require_once 'includes/admission_form.php';
$admissionForm = new AdmissionForm();
$formData = $admissionForm->getAdmissionForm($studentId);

// Get profile photo from admission_forms table (same as view_pdf.php)
$profilePhotoData = ['profile_photo' => $formData['profile_photo'] ?? ''];

// Prepare student data for PDF (name from students table, courses from admission form)
$pdfStudentData = [
    'id' => $studentId,
    'first_name' => $studentData['first_name'] ?? '',
    'last_name' => $studentData['last_name'] ?? '',
    'name' => ($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''),
    'email' => $studentData['email'] ?? '',
    'course_first' => $formData['course_first'] ?? 'N/A',
    'course_second' => $formData['course_second'] ?? 'N/A',
    'course_third' => $formData['course_third'] ?? 'N/A'
];

// Generate PDF
$pdfGenerator = new TestPermitPDF();
$pdf = $pdfGenerator->generateTestPermit($permitData, $pdfStudentData, $formData, $profilePhotoData);

// Clear any output buffer before sending PDF
ob_end_clean();

// Output PDF for download
$pdf->Output('test_permit_' . $studentId . '.pdf', 'D');
?>
