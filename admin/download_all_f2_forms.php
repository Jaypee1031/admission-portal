<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/f2_personal_data_pdf.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$f2Form = new F2PersonalDataForm();
$db = getDB();

// Get all students with completed Personal Data forms
$stmt = $db->prepare("
    SELECT s.*, f2.*
    FROM students s
    JOIN f2_personal_data_forms f2 ON s.id = f2.student_id
    WHERE f2.first_name IS NOT NULL AND f2.last_name IS NOT NULL
    ORDER BY f2.submitted_at DESC
");
$stmt->execute();
$students = $stmt->fetchAll();

if (empty($students)) {
    die('No Personal Data forms found to download.');
}

// Create a ZIP file
$zip = new ZipArchive();
$zipFileName = 'All_F2_Personal_Data_Forms_' . date('Y-m-d') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipFileName;

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    die('Cannot create ZIP file.');
}

foreach ($students as $student) {
    // Convert individual columns to the expected format
    $formData = [
        'personal_info' => [
            'last_name' => $student['last_name'] ?? '',
            'first_name' => $student['first_name'] ?? '',
            'middle_name' => $student['middle_name'] ?? '',
            'civil_status' => $student['civil_status'] ?? '',
            'spouse_name' => $student['spouse_name'] ?? '',
            'course_year_level' => $student['course_year_level'] ?? '',
            'sex' => $student['sex'] ?? '',
            'ethnicity' => $student['ethnicity'] ?? '',
            'ethnicity_others_specify' => $student['ethnicity_others_specify'] ?? '',
            'date_of_birth' => $student['date_of_birth'] ?? '',
            'age' => $student['age'] ?? '',
            'place_of_birth' => $student['place_of_birth'] ?? '',
            'religion' => $student['religion'] ?? '',
            'address' => $student['address'] ?? '',
            'contact_number' => $student['contact_number'] ?? ''
        ],
        'family_info' => [
            'father_name' => $student['father_name'] ?? '',
            'father_occupation' => $student['father_occupation'] ?? '',
            'father_ethnicity' => $student['father_ethnicity'] ?? '',
            'mother_name' => $student['mother_name'] ?? '',
            'mother_occupation' => $student['mother_occupation'] ?? '',
            'mother_ethnicity' => $student['mother_ethnicity'] ?? '',
            'parents_living_together' => $student['parents_living_together'] ?? '',
            'parents_separated' => $student['parents_separated'] ?? '',
            'separation_reason' => $student['separation_reason'] ?? '',
            'living_with' => $student['living_with'] ?? '',
            'age_when_separated' => $student['age_when_separated'] ?? '',
            'guardian_name' => $student['guardian_name'] ?? '',
            'guardian_relationship' => $student['guardian_relationship'] ?? '',
            'guardian_address' => $student['guardian_address'] ?? '',
            'guardian_contact_number' => $student['guardian_contact_number'] ?? '',
            'siblings_info' => $student['siblings_info'] ?? ''
        ],
        'education' => [
            'elementary_school' => $student['elementary_school'] ?? '',
            'secondary_school' => $student['secondary_school'] ?? '',
            'school_university_last_attended' => $student['school_university_last_attended'] ?? '',
            'school_name' => $student['school_name'] ?? '',
            'school_address' => $student['school_address'] ?? '',
            'general_average' => $student['general_average'] ?? '',
            'course_first_choice' => $student['course_first_choice'] ?? '',
            'course_second_choice' => $student['course_second_choice'] ?? '',
            'course_third_choice' => $student['course_third_choice'] ?? '',
            'parents_choice' => $student['parents_choice'] ?? '',
            'nature_of_schooling_continuous' => $student['nature_of_schooling_continuous'] ?? '',
            'reason_if_interrupted' => $student['reason_if_interrupted'] ?? ''
        ],
        'skills' => [
            'talents' => $student['talents'] ?? '',
            'awards' => $student['awards'] ?? '',
            'hobbies' => $student['hobbies'] ?? ''
        ],
        'health_record' => [
            'disability_specify' => $student['disability_specify'] ?? '',
            'confined_rehabilitated' => $student['confined_rehabilitated'] ?? '',
            'confined_when' => $student['confined_when'] ?? '',
            'treated_for_illness' => $student['treated_for_illness'] ?? '',
            'treated_when' => $student['treated_when'] ?? ''
        ],
        'declaration' => [
            'signature_over_printed_name' => $student['signature_over_printed_name'] ?? '',
            'date_accomplished' => $student['date_accomplished'] ?? ''
        ]
    ];
    
    // Generate individual PDF
    $pdfGenerator = new F2PersonalDataPDF();
    $pdf = $pdfGenerator->generateF2PersonalDataPDF($formData, $student);
    
    // Get PDF content
    $pdfContent = $pdf->Output('', 'S');
    
    // Add to ZIP
    $fileName = 'F2_Form_' . $student['first_name'] . '_' . $student['last_name'] . '_' . $student['id'] . '.pdf';
    $zip->addFromString($fileName, $pdfContent);
}

$zip->close();

// Download the ZIP file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);

// Clean up
unlink($zipPath);
exit;
?>
