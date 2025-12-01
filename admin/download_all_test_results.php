<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';
require_once '../includes/cat_result_pdf.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$testResults = new TestResults();
$db = getDB();

// Get all test results with the same extra fields used by print_test_result.php
$stmt = $db->prepare("
    SELECT 
        tr.*,
        s.id as student_id,
        s.first_name,
        s.last_name,
        s.middle_name,
        s.email,
        s.type,
        af.course_first,
        af.course_second,
        af.course_third,
        af.last_school,
        af.school_address,
        f2.general_average as f2_gwa,
        tp.exam_date as permit_exam_date,
        tp.exam_time,
        tp.exam_room,
        a.full_name as processed_by_name
    FROM test_results tr
    JOIN students s ON tr.student_id = s.id
    LEFT JOIN admission_forms af ON s.id = af.student_id
    LEFT JOIN f2_personal_data_forms f2 ON s.id = f2.student_id
    LEFT JOIN test_permits tp ON tr.permit_number = tp.permit_number
    LEFT JOIN admins a ON tr.processed_by = a.id
    ORDER BY tr.processed_at DESC
");
$stmt->execute();
$results = $stmt->fetchAll();

if (empty($results)) {
    die('No test results found to download.');
}

// Create a ZIP file
$zip = new ZipArchive();
$zipFileName = 'All_Test_Results_' . date('Y-m-d') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipFileName;

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    die('Cannot create ZIP file.');
}

foreach ($results as $result) {
    // Generate individual PDF using CATResultPDF (same as single print)
    $pdfGenerator = new CATResultPDF();
    $pdf = $pdfGenerator->generateCATResult($result);
    
    // Get PDF content
    $pdfContent = $pdf->Output('', 'S');
    
    // Add to ZIP
    $fileName = 'Test_Result_' . $result['first_name'] . '_' . $result['last_name'] . '_' . $result['permit_number'] . '.pdf';
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
