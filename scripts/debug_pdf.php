<?php
// Debug script for PDF generation
require_once 'config/config.php';

echo "<h2>Debug PDF Generation</h2>";

// Test database connection
try {
    $db = getDB();
    echo "<p>✓ Database connection successful</p>";
    
    // Test if we can get student data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM students");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✓ Students in database: " . $result['count'] . "</p>";
    
    // Test if we can get admission form data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM admission_forms");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✓ Admission forms in database: " . $result['count'] . "</p>";
    
    // Test PDF directory
    $pdfDir = PDF_PATH . 'admission_forms/';
    echo "<p>PDF Directory: " . $pdfDir . "</p>";
    
    if (is_dir($pdfDir)) {
        echo "<p>✓ PDF directory exists</p>";
    } else {
        echo "<p>✗ PDF directory does not exist, creating...</p>";
        mkdir($pdfDir, 0755, true);
        echo "<p>✓ PDF directory created</p>";
    }
    
    // Test if directory is writable
    if (is_writable($pdfDir)) {
        echo "<p>✓ PDF directory is writable</p>";
    } else {
        echo "<p>✗ PDF directory is not writable</p>";
    }
    
    // Test FPDF class
    require_once 'includes/fpdf/fpdf.php';
    $pdf = new FPDF();
    echo "<p>✓ FPDF class loaded successfully</p>";
    
    // Test PDF generation
    $testFile = $pdfDir . 'test_' . time() . '.pdf';
    $result = $pdf->output($testFile, 'F');
    
    if ($result && file_exists($testFile)) {
        echo "<p>✓ Test PDF created successfully: " . basename($testFile) . "</p>";
        echo "<p>File size: " . filesize($testFile) . " bytes</p>";
    } else {
        echo "<p>✗ Test PDF creation failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='student/admission_form.php'>Go to Admission Form</a></p>";
?>
