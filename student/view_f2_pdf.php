<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/f2_personal_data_pdf.php';

 
// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$f2Form = new F2PersonalDataForm();

// Check if Personal Data form is enabled for this student
if (!$f2Form->isF2FormEnabled($user['id'])) {
    die('Personal Data Form is not available for your account.');
}

// Get Personal Data form data
$formData = $f2Form->getF2FormData($user['id']);

if (!$formData) {
    die('No Personal Data form data found. Please fill out the form first.');
}

// Check if form data is valid
if (empty($formData)) {
    die('Personal Data form data is empty. Please fill out the form first.');
}

try {
    // Generate PDF
    $pdfGenerator = new F2PersonalDataPDF();
    $pdf = $pdfGenerator->generateF2PersonalDataPDF($formData, $user);
    
    // Output PDF
    $pdf->Output('F2_Personal_Data_Form_' . $user['id'] . '.pdf', 'I');
} catch (Exception $e) {
    die('Error generating PDF: ' . $e->getMessage());
}
?>
