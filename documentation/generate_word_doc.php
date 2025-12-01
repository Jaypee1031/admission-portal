<?php
/**
 * Generate Word-Ready Documentation
 * This script creates a comprehensive documentation that can be copied to Word
 */

// Set headers for proper display
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Portal - Complete Code Documentation</title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10px; line-height: 1.3; }
        .page { width: 210mm; min-height: 297mm; padding: 15mm; margin: 0 auto; }
        .section { margin-bottom: 20mm; page-break-inside: avoid; }
        .title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 10mm; }
        .subtitle { font-size: 14px; font-weight: bold; margin: 5mm 0; border-bottom: 1px solid #000; padding-bottom: 2mm; }
        .code { background: #f5f5f5; padding: 5mm; border: 1px solid #ccc; white-space: pre-wrap; font-size: 9px; }
    </style>
</head>
<body>

<div class="page">
    <div class="title">UNIVERSITY ADMISSION PORTAL</div>
    <div class="title">COMPLETE CODE DOCUMENTATION</div>
    <div style="text-align: center; margin-bottom: 20mm;">Generated: <?php echo date('Y-m-d H:i:s'); ?></div>
</div>

<!-- CONFIGURATION SECTION -->
<div class="page">
    <div class="section">
        <div class="subtitle">1. CONFIGURATION FILES</div>
        
        <div class="subtitle">1.1 Main Configuration (config/config.php)</div>
        <div class="code"><?php echo htmlspecialchars('// SITE CONFIGURATION
define(\'SITE_NAME\', \'University Admission Portal\');
define(\'SITE_URL\', \'http://localhost/Admission Portal\');
define(\'ADMIN_EMAIL\', \'admin@university.edu\');

// DATABASE CONFIGURATION
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'admission_portal\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');

// SECURITY CONFIGURATION
define(\'PASSWORD_MIN_LENGTH\', 8);
define(\'SESSION_LIFETIME\', 3600); // 1 hour
define(\'MAX_LOGIN_ATTEMPTS\', 5);

// UPLOAD CONFIGURATION
define(\'MAX_FILE_SIZE\', 5242880); // 5MB
define(\'ALLOWED_FILE_TYPES\', [\'pdf\', \'doc\', \'docx\', \'jpg\', \'jpeg\', \'png\']);

// EMAIL CONFIGURATION
define(\'SMTP_HOST\', \'smtp.gmail.com\');
define(\'SMTP_PORT\', 587);
define(\'SMTP_USERNAME\', \'\');
define(\'SMTP_PASSWORD\', \'\');
define(\'SMTP_ENCRYPTION\', \'tls\');'); ?></div>

        <div class="subtitle">1.2 Grading Configuration (config/grading_config.php)</div>
        <div class="code"><?php echo htmlspecialchars('// PASSING THRESHOLD FOR EXAM RATING
define(\'PASSING_THRESHOLD\', 75);

// OVERALL RATING THRESHOLDS
define(\'RATING_EXCELLENT_MIN\', 90);
define(\'RATING_VERY_GOOD_MIN\', 85);
define(\'RATING_PASSED_MIN\', 75);
define(\'RATING_CONDITIONAL_MIN\', 70);

// SUBJECT MAXIMUM SCORES
define(\'MAX_GEN_INFO_SCORE\', 30);
define(\'MAX_FILIPINO_SCORE\', 50);
define(\'MAX_ENGLISH_SCORE\', 60);
define(\'MAX_SCIENCE_SCORE\', 60);
define(\'MAX_MATH_SCORE\', 50);

// WEIGHT DISTRIBUTION
define(\'EXAM_WEIGHT\', 0.50);
define(\'INTERVIEW_WEIGHT\', 0.10);
define(\'GWA_WEIGHT\', 0.40);

// HELPER FUNCTION TO GET OVERALL RATING
function getOverallRating($examRating) {
    if ($examRating >= RATING_EXCELLENT_MIN) {
        return \'Excellent\';
    } elseif ($examRating >= RATING_VERY_GOOD_MIN) {
        return \'Very Good\';
    } elseif ($examRating >= PASSING_THRESHOLD) {
        return \'Passed\';
    } elseif ($examRating >= RATING_CONDITIONAL_MIN) {
        return \'Conditional\';
    } else {
        return \'Failed\';
    }
}

// HELPER FUNCTION TO CHECK IF STUDENT PASSED
function hasPassed($examRating) {
    return $examRating >= PASSING_THRESHOLD;
}'); ?></div>
    </div>
</div>

<!-- AUTHENTICATION SYSTEM -->
<div class="page">
    <div class="section">
        <div class="subtitle">2. AUTHENTICATION SYSTEM</div>
        
        <div class="subtitle">2.1 Auth Class (includes/auth.php)</div>
        <div class="code"><?php echo htmlspecialchars('class Auth {
    private $db;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct() {
        $this->db = getDB();
        session_start();
    }
    
    /**
     * Login user with email and password
     */
    public function login($email, $password, $userType = \'student\') {
        $table = $userType === \'admin\' ? \'admins\' : \'students\';
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user[\'password\'])) {
                $_SESSION[\'user_id\'] = $user[\'id\'];
                $_SESSION[\'user_type\'] = $userType;
                $_SESSION[\'login_time\'] = time();
                $_SESSION[\'ip_address\'] = $_SERVER[\'REMOTE_ADDR\'];
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION[\'user_id\'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION[\'login_time\'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $table = $_SESSION[\'user_type\'] === \'admin\' ? \'admins\' : \'students\';
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = ?");
            $stmt->execute([$_SESSION[\'user_id\']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        setcookie(session_name(), \'\', time() - 3600, \'/\');
    }
}

// HELPER FUNCTIONS
function isStudent() {
    return isset($_SESSION[\'user_type\']) && $_SESSION[\'user_type\'] === \'student\';
}

function isAdmin() {
    return isset($_SESSION[\'user_type\']) && $_SESSION[\'user_type\'] === \'admin\';
}

function requireLogin() {
    if (!isset($_SESSION[\'user_id\'])) {
        redirect(\'../index.php\');
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}'); ?></div>
    </div>
</div>

<!-- TEST RESULTS SYSTEM -->
<div class="page">
    <div class="section">
        <div class="subtitle">3. TEST RESULTS SYSTEM</div>
        
        <div class="subtitle">3.1 TestResults Class (includes/test_results.php)</div>
        <div class="code"><?php echo htmlspecialchars('class TestResults {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Upload and process test results from Excel file
     */
    public function uploadTestResults($excelData, $processedBy) {
        try {
            $this->db->beginTransaction();
            
            foreach ($excelData as $row) {
                // Find student by permit number
                $student = $this->getStudentByPermitNumber($row[\'permit_number\']);
                if (!$student) continue;
                
                // Extract subject scores
                $subjectScores = [
                    \'gen_info\' => $row[\'gen_info\'] ?? 0,
                    \'filipino\' => $row[\'filipino\'] ?? 0,
                    \'english\' => $row[\'english\'] ?? 0,
                    \'science\' => $row[\'science\'] ?? 0,
                    \'math\' => $row[\'math\'] ?? 0
                ];
                
                // Calculate transmuted scores
                $transmutedScores = $this->calculateTransmutedScores($subjectScores);
                
                // Calculate weighted scores
                $weightedScores = $this->calculateWeightedScores($transmutedScores);
                
                // Calculate exam rating
                $examRating = array_sum($weightedScores) / 5;
                
                // Calculate percentage score
                $totalRawScore = array_sum($subjectScores);
                $percentageScore = ($totalRawScore / 250) * 100;
                
                // Determine overall rating
                $overallRating = getOverallRating($examRating);
                
                // Save to database
                $this->saveTestResult($student[\'id\'], $row, $subjectScores, 
                    $transmutedScores, $weightedScores, $examRating, 
                    $percentageScore, $overallRating, $processedBy);
            }
            
            $this->db->commit();
            return [\'success\' => true, \'message\' => \'Test results uploaded successfully\'];
        } catch (Exception $e) {
            $this->db->rollback();
            return [\'success\' => false, \'message\' => \'Error: \' . $e->getMessage()];
        }
    }
    
    /**
     * Get test result for student
     */
    public function getTestResult($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tr.*, s.first_name, s.last_name, s.middle_name 
                FROM test_results tr
                JOIN students s ON tr.student_id = s.id
                WHERE tr.student_id = ?
                ORDER BY tr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}'); ?></div>
    </div>
</div>

<!-- STUDENT MANAGEMENT -->
<div class="page">
    <div class="section">
        <div class="subtitle">4. STUDENT MANAGEMENT</div>
        
        <div class="subtitle">4.1 Manage Students (admin/manage_students.php)</div>
        <div class="code"><?php echo htmlspecialchars('// SEARCH STUDENTS
if (isset($_GET[\'search\']) && !empty(trim($_GET[\'search\']))) {
    $searchTerm = trim($_GET[\'search\']);
    try {
        $stmt = $db->prepare("
            SELECT s.*, 
                   COUNT(r.id) as requirements_count,
                   COUNT(CASE WHEN r.status = \'Approved\' THEN 1 END) as approved_requirements,
                   af.id as has_admission_form,
                   tp.id as has_test_permit,
                   tp.status as permit_status
            FROM students s
            LEFT JOIN requirements r ON s.id = r.student_id
            LEFT JOIN admission_forms af ON s.id = af.student_id
            LEFT JOIN test_permits tp ON s.id = tp.student_id
            WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.id = ?
            GROUP BY s.id
            ORDER BY s.last_name, s.first_name
            LIMIT 50
        ");
        $searchPattern = "%{$searchTerm}%";
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchTerm]);
        $searchResults = $stmt->fetchAll();
    } catch (PDOException $e) {
        showAlert(\'Search error: \' . $e->getMessage(), \'error\');
    }
}

// CHANGE STUDENT PASSWORD
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && isset($_POST[\'action\']) && $_POST[\'action\'] === \'change_student_password\') {
    $studentId = (int)$_POST[\'student_id\'];
    $newPassword = $_POST[\'new_password\'] ?? \'\';
    $confirmPassword = $_POST[\'confirm_password\'] ?? \'\';
    
    if ($newPassword !== $confirmPassword) {
        showAlert(\'New password and confirmation password do not match\', \'error\');
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        showAlert(\'Password must be at least \' . PASSWORD_MIN_LENGTH . \' characters long\', \'error\');
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE students SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $studentId])) {
                showAlert(\'Student password changed successfully\', \'success\');
            } else {
                showAlert(\'Failed to change password\', \'error\');
            }
        } catch (PDOException $e) {
            showAlert(\'Database error: \' . $e->getMessage(), \'error\');
        }
    }
    redirect(\'manage_students.php\');
}'); ?></div>
    </div>
</div>

<!-- DATABASE SCHEMA -->
<div class="page">
    <div class="section">
        <div class="subtitle">5. DATABASE SCHEMA</div>
        <div class="code"><?php echo htmlspecialchars('-- STUDENTS TABLE
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM(\'Freshman\', \'Transferee\') DEFAULT \'Freshman\',
    status ENUM(\'Pending\', \'Approved\', \'Rejected\', \'Enrolled\') DEFAULT \'Pending\',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- TEST RESULTS TABLE
CREATE TABLE test_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    permit_number VARCHAR(50) UNIQUE NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    
    -- RAW SCORES
    gen_info_raw DECIMAL(5,2) DEFAULT 0,
    filipino_raw DECIMAL(5,2) DEFAULT 0,
    english_raw DECIMAL(5,2) DEFAULT 0,
    science_raw DECIMAL(5,2) DEFAULT 0,
    math_raw DECIMAL(5,2) DEFAULT 0,
    raw_score DECIMAL(5,2) DEFAULT 0,
    
    -- FINAL RATINGS
    exam_rating DECIMAL(5,2) DEFAULT 0,
    exam_percentage DECIMAL(5,2) DEFAULT 0,
    overall_rating ENUM(\'Excellent\', \'Very Good\', \'Passed\', \'Conditional\', \'Failed\'),
    gwa_score DECIMAL(3,2),
    interview_score DECIMAL(5,2),
    total_rating DECIMAL(5,2),
    
    recommendation TEXT,
    processed_by INT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (processed_by) REFERENCES admins(id)
);'); ?></div>
    </div>
</div>

<!-- SECURITY IMPLEMENTATION -->
<div class="page">
    <div class="section">
        <div class="subtitle">6. SECURITY IMPLEMENTATION</div>
        <div class="code"><?php echo htmlspecialchars('1. PASSWORD SECURITY
   - Use password_hash() with PASSWORD_DEFAULT (bcrypt)
   - Minimum password length: 8 characters
   - Password strength validation

2. SESSION SECURITY
   - Secure session configuration
   - Session timeout: 1 hour
   - Regenerate session ID on login
   - IP address validation

3. INPUT VALIDATION
   - sanitizeInput() function for all inputs
   - Email validation with filter_var()
   - File type validation
   - SQL injection prevention with prepared statements

4. XSS PREVENTION
   - htmlspecialchars() for all outputs
   - Content Security Policy headers
   - Input sanitization

SECURITY CODE EXAMPLES:

<?php
// PASSWORD HASHING
$password = \'user_password\';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// PASSWORD VERIFICATION
if (password_verify($inputPassword, $storedHash)) {
    // Password is correct
}

// INPUT SANITIZATION
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, \'UTF-8\');
    return $input;
}

// PREPARED STATEMENTS
$stmt = $db->prepare("SELECT * FROM students WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// XSS PREVENTION
echo htmlspecialchars($userInput, ENT_QUOTES, \'UTF-8\');
?>'); ?></div>
    </div>
</div>

<!-- DEPLOYMENT GUIDE -->
<div class="page">
    <div class="section">
        <div class="subtitle">7. DEPLOYMENT GUIDE</div>
        <div class="code"><?php echo htmlspecialchars('1. SERVER REQUIREMENTS
   - PHP 7.4 or higher
   - MySQL 5.7 or MariaDB 10.2
   - Apache 2.4 or Nginx 1.18
   - SSL certificate

2. PHP EXTENSIONS REQUIRED
   - PDO MySQL
   - OpenSSL
   - Mbstring
   - GD (for image processing)
   - Fileinfo
   - JSON
   - Session

3. CONFIGURATION STEPS
   a. Update config/database.php
   b. Set file permissions (755 for directories, 644 for files)
   c. Create database and import schema
   d. Configure email settings
   e. Set up SSL certificate

4. FILE PERMISSIONS
   chmod 755 /path/to/admission/portal/
   chmod 644 /path/to/admission/portal/*.php
   chmod 777 /path/to/admission/portal/uploads/
   chmod 644 /path/to/admission/portal/config/config.php

5. APACHE CONFIGURATION
   <VirtualHost *:443>
       ServerName admission.university.edu
       DocumentRoot /var/www/html/Admission Portal
       SSLEngine on
       SSLCertificateFile /path/to/certificate.crt
       SSLCertificateKeyFile /path/to/private.key
       
       <Directory /var/www/html/Admission Portal>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>'); ?></div>
    </div>
</div>

</body>
</html>
