<?php
// University Admission Portal Configuration - SECURE VERSION

// Error reporting - log errors, don't display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');

// Ensure logs directory exists
if (!is_dir(dirname(__DIR__) . '/logs')) {
    mkdir(dirname(__DIR__) . '/logs', 0755, true);
}

// Secure session configuration BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();

// Regenerate session ID on login (call this in login functions)
if (!isset($_SESSION['_session_init'])) {
    session_regenerate_id(true);
    $_SESSION['_session_init'] = true;
}

// Site Configuration
define('SITE_NAME', 'University Admission Portal');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/Admission Portal');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@university.edu');

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf' => 'pdf',
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png' => 'png',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
]);

// PDF Configuration
define('PDF_DIR', 'generated_pdfs/');
define('FPDF_FONTPATH', 'libs/fpdf/font/');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// Application Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOAD_PATH', ROOT_PATH . '/' . UPLOAD_DIR);
define('PDF_PATH', ROOT_PATH . '/' . PDF_DIR);
define('CACHE_DIR', ROOT_PATH . '/cache');

define('ENABLE_CACHE', false);
define('CACHE_TTL_DASHBOARD', 60);
define('CACHE_TTL_RECENT_APPLICANTS', 60);

require_once ROOT_PATH . '/core/Autoloader.php';
Autoloader::register();

require_once INCLUDES_PATH . '/loader.php';

// Create necessary directories if they don't exist
$directories = [UPLOAD_PATH, PDF_PATH, CACHE_DIR, UPLOAD_PATH . 'requirements/', PDF_PATH . 'admission_forms/', PDF_PATH . 'test_permits/'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Include database connection
require_once ROOT_PATH . '/config/database.php';

// Helper Functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function buildRoutePath($path = '') {
    $path = trim($path);

    if ($path === '' || $path === '/') {
        return '/';
    }

    $path = ltrim($path, '/');
    return '/' . $path;
}

function buildUrl($path = '') {
    $route = buildRoutePath($path);
    $base = rtrim(SITE_URL, '/');

    if ($route === '/') {
        return $base . '/';
    }

    return $base . $route;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

function redirect($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $target = $url;
    } else {
        $target = buildUrl($url);
    }

    header("Location: $target");
    exit();
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function clearAlert() {
    unset($_SESSION['alert']);
}

// CSRF Token Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Password Validation Function
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }
    
    return $errors;
}

// Rate Limiting Function
function checkRateLimit($identifier, $maxAttempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_ATTEMPT_TIMEOUT) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];
    
    // Reset if timeout has passed
    if ($elapsed > $timeout) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    // Check if max attempts exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}

function incrementRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $_SESSION[$key]['attempts']++;
}

function resetRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

// Access Control Function
function requireLogin() {
    if (!isLoggedIn()) {
        showAlert('Please log in first', 'error');
        redirect('/student/login');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        showAlert('Access denied. Admin privileges required', 'error');
        redirect('/');
    }
}

function requireStudent() {
    if (!isStudent()) {
        showAlert('Access denied. Student account required', 'error');
        redirect('/');
    }
}

// Verify User Ownership (IDOR Protection)
function verifyUserOwnership($userId, $resourceUserId) {
    if ((int)$userId !== (int)$resourceUserId && !isAdmin()) {
        return false;
    }
    return true;
}

// Security Headers Function
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; img-src \'self\' data:; font-src \'self\' cdn.jsdelivr.net');
}

// Call security headers on every page
setSecurityHeaders();
?>
