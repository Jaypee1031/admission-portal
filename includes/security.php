<?php
/**
 * Security Middleware and Utilities
 * Provides centralized security functions for the application
 */

// Audit Logging Function
function logAudit($action, $userId, $details = '', $status = 'success') {
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/audit.log';
    $timestamp = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 100);
    
    $logEntry = sprintf(
        "[%s] Action: %s | User: %s | IP: %s | Status: %s | Details: %s | UA: %s\n",
        $timestamp,
        $action,
        $userId,
        $ipAddress,
        $status,
        $details,
        $userAgent
    );
    
    error_log($logEntry, 3, $logFile);
}

// Validate and Sanitize Email
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

// Validate and Sanitize URL
function validateURL($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return false;
}

// Validate Integer
function validateInteger($value) {
    if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
        return (int)$value;
    }
    return false;
}

// Secure File Download
function secureFileDownload($filePath, $fileName = null) {
    // Verify file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Prevent directory traversal
    $realPath = realpath($filePath);
    $uploadPath = realpath(UPLOAD_PATH);
    $pdfPath = realpath(PDF_PATH);
    
    if (strpos($realPath, $uploadPath) !== 0 && strpos($realPath, $pdfPath) !== 0) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Get file info
    $fileSize = filesize($filePath);
    $fileName = $fileName ?: basename($filePath);
    $mimeType = mime_content_type($filePath);
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit;
}

// Validate File Path (IDOR Protection)
function validateFilePath($filePath, $studentId) {
    // Ensure path contains student ID
    if (strpos($filePath, '/' . $studentId . '/') === false) {
        return false;
    }
    
    // Prevent directory traversal
    if (strpos($filePath, '..') !== false) {
        return false;
    }
    
    return true;
}

// Generate Secure Random String
function generateSecureString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Validate CSRF Token and Regenerate
function validateAndRegenerateCSRF($token) {
    if (!verifyCSRFToken($token)) {
        return false;
    }
    
    // Regenerate token after verification
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

// Check Session Timeout
function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_destroy();
            showAlert('Session expired. Please log in again.', 'error');
            redirect('/student/login.php');
        }
    }
}

// Sanitize Output for HTML
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Validate Input Length
function validateInputLength($input, $maxLength) {
    return strlen($input) <= $maxLength;
}

// Check for SQL Injection Patterns
function detectSQLInjection($input) {
    $patterns = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bor\b.*=.*)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bexec\b.*\()/i',
        '/(\bscript\b.*\>)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

// Validate Phone Number
function validatePhoneNumber($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid length (10-15 digits)
    if (strlen($phone) >= 10 && strlen($phone) <= 15) {
        return $phone;
    }
    
    return false;
}

// Validate Date Format
function validateDateFormat($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Get Client IP Address
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
    
    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return 'UNKNOWN';
}

// Log Security Event
function logSecurityEvent($eventType, $severity = 'info', $details = '') {
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'ANONYMOUS';
    $ipAddress = getClientIP();
    
    $logEntry = sprintf(
        "[%s] [%s] Event: %s | User: %s | IP: %s | Details: %s\n",
        $timestamp,
        strtoupper($severity),
        $eventType,
        $userId,
        $ipAddress,
        $details
    );
    
    error_log($logEntry, 3, $logFile);
}

// Verify Admin Action
function verifyAdminAction($adminId) {
    if (!isAdmin()) {
        logSecurityEvent('UNAUTHORIZED_ADMIN_ACTION', 'warning', 'Non-admin attempted admin action');
        return false;
    }
    
    if ((int)$_SESSION['user_id'] !== (int)$adminId && !isAdmin()) {
        logSecurityEvent('UNAUTHORIZED_ADMIN_ACTION', 'warning', 'Admin attempted action on different admin account');
        return false;
    }
    
    return true;
}

// Verify Student Action
function verifyStudentAction($studentId) {
    if (!isStudent()) {
        logSecurityEvent('UNAUTHORIZED_STUDENT_ACTION', 'warning', 'Non-student attempted student action');
        return false;
    }
    
    if ((int)$_SESSION['user_id'] !== (int)$studentId && !isAdmin()) {
        logSecurityEvent('UNAUTHORIZED_STUDENT_ACTION', 'warning', 'Student attempted action on different student account');
        return false;
    }
    
    return true;
}

// Sanitize File Name
function sanitizeFileName($fileName) {
    // Remove special characters
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    
    // Remove multiple dots
    $fileName = preg_replace('/\.+/', '.', $fileName);
    
    // Ensure it doesn't start with a dot
    $fileName = ltrim($fileName, '.');
    
    return $fileName;
}

// Validate File Extension
function validateFileExtension($fileName, $allowedExtensions) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

// Create Secure Directory
function createSecureDirectory($path, $permissions = 0755) {
    if (!is_dir($path)) {
        mkdir($path, $permissions, true);
        
        // Create .htaccess to prevent direct access
        $htaccess = $path . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "deny from all\n";
            file_put_contents($htaccess, $content);
        }
    }
}

// Verify Request Method
function verifyRequestMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        die('Method not allowed');
    }
}

// Validate JSON Input
function validateJSONInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        die('Invalid JSON');
    }
    
    return $data;
}

// Rate Limit by IP
function checkIPRateLimit($action, $maxAttempts = 10, $timeWindow = 3600) {
    $ip = getClientIP();
    $key = 'ip_rate_limit_' . md5($action . $ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];
    
    // Reset if time window has passed
    if ($elapsed > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    // Check if max attempts exceeded
    if ($data['attempts'] >= $maxAttempts) {
        logSecurityEvent('RATE_LIMIT_EXCEEDED', 'warning', 'Action: ' . $action . ' | IP: ' . $ip);
        return false;
    }
    
    $_SESSION[$key]['attempts']++;
    return true;
}

?>
