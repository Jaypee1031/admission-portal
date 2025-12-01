<?php
/**
 * Secure PDF Download Handler
 * All PDF downloads must go through this file
 * Prevents direct access to PDF files
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/security.php';

// Require login
requireLogin();

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

// Get file identifier
$fileId = validateInteger($_GET['id'] ?? 0);
if ($fileId === false || $fileId <= 0) {
    http_response_code(400);
    die('Invalid file ID');
}

// Get file type
$fileType = sanitizeInput($_GET['type'] ?? '');
if (!in_array($fileType, ['admission', 'permit', 'result', 'f2'])) {
    http_response_code(400);
    die('Invalid file type');
}

try {
    $db = getDB();
    
    // Determine table and ownership based on file type
    $table = '';
    $ownershipCheck = '';
    
    switch ($fileType) {
        case 'admission':
            $table = 'admission_forms';
            $ownershipCheck = 'student_id';
            break;
        case 'permit':
            $table = 'test_permits';
            $ownershipCheck = 'student_id';
            break;
        case 'result':
            $table = 'test_results';
            $ownershipCheck = 'student_id';
            break;
        case 'f2':
            $table = 'f2_personal_data_forms';
            $ownershipCheck = 'student_id';
            break;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT $ownershipCheck FROM $table WHERE id = ?");
    $stmt->execute([$fileId]);
    $record = $stmt->fetch();
    
    if (!$record) {
        logSecurityEvent('PDF_DOWNLOAD_NOT_FOUND', 'warning', 'File ID: ' . $fileId . ' Type: ' . $fileType);
        http_response_code(404);
        die('File not found');
    }
    
    // Check ownership (student can only download their own, admin can download any)
    if (!isAdmin() && (int)$record[$ownershipCheck] !== (int)$_SESSION['user_id']) {
        logSecurityEvent('UNAUTHORIZED_PDF_DOWNLOAD', 'critical', 'User: ' . $_SESSION['user_id'] . ' Attempted: ' . $fileId);
        http_response_code(403);
        die('Access denied');
    }
    
    // Generate PDF file path based on type
    $fileName = '';
    $filePath = '';
    
    switch ($fileType) {
        case 'admission':
            $fileName = 'admission_form_' . $fileId . '.pdf';
            $filePath = PDF_PATH . 'admission_forms/' . $fileName;
            break;
        case 'permit':
            $fileName = 'test_permit_' . $fileId . '.pdf';
            $filePath = PDF_PATH . 'test_permits/' . $fileName;
            break;
        case 'result':
            $fileName = 'test_result_' . $fileId . '.pdf';
            $filePath = PDF_PATH . 'test_results/' . $fileName;
            break;
        case 'f2':
            $fileName = 'f2_form_' . $fileId . '.pdf';
            $filePath = PDF_PATH . 'f2_forms/' . $fileName;
            break;
    }
    
    // Verify file exists
    if (!file_exists($filePath)) {
        logSecurityEvent('PDF_FILE_NOT_FOUND', 'warning', 'Path: ' . $filePath);
        http_response_code(404);
        die('File not found on server');
    }
    
    // Verify file is within allowed directory (prevent directory traversal)
    $realPath = realpath($filePath);
    $allowedPath = realpath(PDF_PATH);
    
    if (strpos($realPath, $allowedPath) !== 0) {
        logSecurityEvent('DIRECTORY_TRAVERSAL_ATTEMPT', 'critical', 'Path: ' . $filePath);
        http_response_code(403);
        die('Access denied');
    }
    
    // Log download
    logAudit('pdf_download', $_SESSION['user_id'], 'File: ' . $fileName . ' Type: ' . $fileType, 'success');
    
    // Get file info
    $fileSize = filesize($filePath);
    
    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    
    // Output file
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    logSecurityEvent('PDF_DOWNLOAD_ERROR', 'error', 'Error: ' . $e->getMessage());
    http_response_code(500);
    die('An error occurred while downloading the file');
}
?>
