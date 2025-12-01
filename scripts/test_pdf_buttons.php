anymore ?php
// Test PDF buttons functionality
require_once 'config/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Please log in first";
    exit;
}

$user = $auth->getCurrentUser();
$studentId = $user['id'];

echo "<h2>Test PDF Buttons</h2>";
echo "<p>Student ID: " . $studentId . "</p>";

// Get the latest PDF file for this student
$pattern = PDF_PATH . 'admission_forms/QSU_F1A_admission_form_' . $studentId . '_*.pdf';
$files = glob($pattern);

if (!empty($files)) {
    $latestFile = max($files);
    $latestPdfFile = basename($latestFile);
    
    echo "<p>Latest PDF: " . $latestPdfFile . "</p>";
    echo "<p>File exists: " . (file_exists($latestFile) ? 'Yes' : 'No') . "</p>";
    echo "<p>File size: " . filesize($latestFile) . " bytes</p>";
    
    echo "<h3>Test Buttons:</h3>";
    echo "<p><a href='view_pdf.php?file=" . urlencode($latestPdfFile) . "' target='_blank' class='btn btn-success'>View PDF</a></p>";
    echo "<p><a href='download_pdf.php?file=" . urlencode($latestPdfFile) . "' class='btn btn-outline-success'>Download PDF</a></p>";
    
} else {
    echo "<p>No PDF files found for student ID: " . $studentId . "</p>";
    echo "<p>Pattern searched: " . $pattern . "</p>";
    
    // List all PDF files
    $allFiles = glob(PDF_PATH . 'admission_forms/*.pdf');
    echo "<p>All PDF files in directory:</p>";
    foreach ($allFiles as $file) {
        echo "<p>- " . basename($file) . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='student/admission_form.php'>Go to Admission Form</a></p>";
?>
