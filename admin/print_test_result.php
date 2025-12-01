<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';
require_once '../includes/cat_result_pdf.php';

// Check if user is logged in (admin or student)
if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$studentId = (int)($_GET['student_id'] ?? 0);
$resultId = (int)($_GET['id'] ?? 0);
$download = isset($_GET['download']) && $_GET['download'] == '1';

// If student is accessing, they can only view their own results
if (isStudent() && $studentId !== $user['id']) {
    die('Access denied');
}

$testResults = new TestResults();
$db = getDB();

// Get comprehensive result details with all required data
if ($studentId) {
    // Get by student ID
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
        WHERE tr.student_id = ?
        ORDER BY tr.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$studentId]);
} else {
    // Get by result ID (admin access)
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
        WHERE tr.id = ?
    ");
    $stmt->execute([$resultId]);
}

$result = $stmt->fetch();

if (!$result) {
    die('Test result not found');
}

// Generate CAT Result PDF using TO-PRINT-TEST-RESULT-2026.xlsx format
$pdfGenerator = new CATResultPDF();
$pdf = $pdfGenerator->generateCATResult($result);

// Output PDF
$outputMode = $download ? 'D' : 'I';
$filename = 'CAT_Result_' . $result['permit_number'] . '_' . date('Y') . '.pdf';
$pdf->Output($filename, $outputMode);
?>
