<?php
// Start output buffering to prevent any output before PDF
ob_start();

require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/test_permit.php';
require_once 'includes/test_permit_pdf.php';

// Determine which student ID to render for:
// - If admin passes ?student_id=, allow viewing that student's permit
// - Else if logged in student, use their own ID
// - Otherwise redirect
$studentId = null;
if (isAdmin() && isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];
} elseif (isStudent()) {
    $user = $auth->getCurrentUser();
    $studentId = $user['id'];
} else {
    ob_end_clean();
    redirect('index.php');
}

// Initialize classes
$testPermit = new TestPermit();

// Get test permit data
$permitData = $testPermit->getTestPermit($studentId);

if (!$permitData) {
    ob_end_clean(); // Clear any output buffer
    showAlert('No test permit found. Please request a test permit first.', 'error');
    redirect('student/test_permit.php');
}
// Debug: show permit data (HTML comment + error_log)
error_log('TEST_PERMIT permitData: ' . print_r($permitData, true));
echo "<!-- DEBUG permitData approved_by=" . ($permitData['approved_by'] ?? 'NULL') . " status=" . ($permitData['status'] ?? 'NULL') . " -->";

// Get student data and admission form data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$studentData = $stmt->fetch();

if (!$studentData) {
    ob_end_clean(); // Clear any output buffer
    showAlert('Student data not found.', 'error');
    redirect('student/dashboard.php');
}

// Get admission form data for course information
require_once 'includes/admission_form.php';
$admissionForm = new AdmissionForm();
$formData = $admissionForm->getAdmissionForm($studentId);

// Get profile photo from admission_forms table (same as view_pdf.php)
$profilePhotoData = ['profile_photo' => $formData['profile_photo'] ?? ''];

// Get admin full name who approved the permit (match logic used in view_pdf.php)
$adminName = 'N/A';
if (!empty($permitData['approved_by'])) {
    try {
        $stmt = $db->prepare("SELECT full_name FROM admins WHERE id = ?");
        $stmt->execute([$permitData['approved_by']]);
        $adminRow = $stmt->fetch();
        if ($adminRow && !empty($adminRow['full_name'])) {
            $adminName = $adminRow['full_name'];
        }
    } catch (Exception $e) {
        // ignore; will fall back to N/A
    }
}
// Debug: show resolved admin name
error_log('TEST_PERMIT adminName: ' . $adminName);
echo "<!-- DEBUG adminName=" . $adminName . " -->";
// Prepare admin data for PDF generator
$pdfAdminData = [
    'full_name' => $adminName,
    'approved_by' => $permitData['approved_by'] ?? null,
];

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
$pdf = $pdfGenerator->generateTestPermit($permitData, $pdfStudentData, $formData, $profilePhotoData, $pdfAdminData);

// Clear any output buffer before sending PDF
ob_end_clean();

// Output PDF
$pdf->Output('test_permit_' . $studentId . '.pdf', 'I');
?>
