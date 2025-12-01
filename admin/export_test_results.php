<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$testResults = new TestResults();

// Get filters from URL parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'exam_date' => $_GET['exam_date'] ?? '',
    'overall_rating' => $_GET['overall_rating'] ?? ''
];

// Export to CSV (Excel-compatible format)
$filepath = $testResults->exportToCSV($filters);

if ($filepath && file_exists($filepath)) {
    $filename = 'TEST RESULT 1st Semester ' . date('Y') . '-' . (date('Y') + 1) . '.csv';
    
    // Set headers for file download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output file content
    readfile($filepath);
    
    // Clean up temporary file
    unlink($filepath);
    
    exit;
} else {
    // Redirect back with error message
    showAlert('No test results found to export or export failed.', 'error');
    redirect('/admin/test_results_management.php');
}
?>
