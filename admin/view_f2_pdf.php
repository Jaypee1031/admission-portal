<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/f2_personal_data_pdf.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$studentId = (int)($_GET['student_id'] ?? 0);

if (!$studentId) {
    die('Student ID is required.');
}

$f2Form = new F2PersonalDataForm();
$db = getDB();

// Get student information
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found.');
}

// Get Personal Data form data
$formData = $f2Form->getF2FormData($studentId);

if (!$formData) {
    // Check if there's any F2 form record in the database
    $stmt = $db->prepare("SELECT COUNT(*) FROM f2_personal_data_forms WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $hasF2Record = $stmt->fetchColumn() > 0;
    
    if ($hasF2Record) {
        // There's a record but getF2FormData returned null, try to get raw data
        $stmt = $db->prepare("SELECT * FROM f2_personal_data_forms WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $rawData = $stmt->fetch();
        
        if ($rawData) {
            // Create basic form data structure from raw data
            $formData = [
                'personal_info' => [
                    'first_name' => $rawData['first_name'] ?? '',
                    'last_name' => $rawData['last_name'] ?? '',
                    'middle_name' => $rawData['middle_name'] ?? '',
                    'address' => $rawData['address'] ?? '',
                    'contact_number' => $rawData['contact_number'] ?? '',
                    'email' => $rawData['email'] ?? '',
                    'date_of_birth' => $rawData['date_of_birth'] ?? '',
                    'age' => $rawData['age'] ?? '',
                    'place_of_birth' => $rawData['place_of_birth'] ?? '',
                    'sex' => $rawData['sex'] ?? '',
                    'civil_status' => $rawData['civil_status'] ?? '',
                    'religion' => $rawData['religion'] ?? '',
                    'ethnicity' => $rawData['ethnicity'] ?? ''
                ],
                'family_info' => [
                    'father_name' => $rawData['father_name'] ?? '',
                    'father_occupation' => $rawData['father_occupation'] ?? '',
                    'mother_name' => $rawData['mother_name'] ?? '',
                    'mother_occupation' => $rawData['mother_occupation'] ?? '',
                    'guardian_name' => $rawData['guardian_name'] ?? '',
                    'guardian_contact_number' => $rawData['guardian_contact_number'] ?? ''
                ],
                'education' => [
                    'elementary_school' => $rawData['elementary_school'] ?? '',
                    'secondary_school' => $rawData['secondary_school'] ?? '',
                    'school_university_last_attended' => $rawData['school_university_last_attended'] ?? '',
                    'general_average' => $rawData['general_average'] ?? '',
                    'course_first_choice' => $rawData['course_first_choice'] ?? '',
                    'course_second_choice' => $rawData['course_second_choice'] ?? '',
                    'course_third_choice' => $rawData['course_third_choice'] ?? ''
                ],
                'skills' => [
                    'talents' => $rawData['talents'] ?? '',
                    'awards' => $rawData['awards'] ?? '',
                    'hobbies' => $rawData['hobbies'] ?? ''
                ],
                'health_record' => [
                    'disability_specify' => $rawData['disability_specify'] ?? '',
                    'treated_for_illness' => $rawData['treated_for_illness'] ?? ''
                ],
                'declaration' => [
                    'signature_over_printed_name' => $rawData['signature_over_printed_name'] ?? '',
                    'date_accomplished' => $rawData['date_accomplished'] ?? ''
                ]
            ];
        }
    }
    
    if (!$formData) {
        die('No Personal Data form data found for this student.');
    }
}

// Check if form data is valid - the data is stored in nested structure
// Allow PDF generation even if some fields are empty, as long as basic info exists
if (empty($formData['personal_info']['first_name']) && empty($formData['personal_info']['last_name'])) {
    // Try to get student name from the main students table as fallback
    $stmt = $db->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $studentInfo = $stmt->fetch();
    
    if ($studentInfo && (!empty($studentInfo['first_name']) || !empty($studentInfo['last_name']))) {
        // Use student info from main table as fallback
        $formData['personal_info']['first_name'] = $studentInfo['first_name'] ?? '';
        $formData['personal_info']['last_name'] = $studentInfo['last_name'] ?? '';
    } else {
        die('Personal Data form data is incomplete for this student. Please ensure at least first name or last name is provided.');
    }
}

try {
    // Generate PDF
    $pdfGenerator = new F2PersonalDataPDF();
    
    // Convert the individual column data back to the format expected by the PDF generator
    $pdfData = $formData; // The getF2FormData already returns the right format
    
    $pdf = $pdfGenerator->generateF2PersonalDataPDF($pdfData, $student);
    
    // Output PDF
    $filename = 'F2_Personal_Data_Form_' . $student['first_name'] . '_' . $student['last_name'] . '_' . $studentId . '.pdf';
    $pdf->Output($filename, 'I');
} catch (Exception $e) {
    die('Error generating PDF: ' . $e->getMessage());
}
?>