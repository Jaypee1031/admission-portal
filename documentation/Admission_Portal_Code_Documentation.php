<?php
/**
 * Admission Portal Code Documentation Generator
 * Generates a comprehensive HTML documentation that can be saved as Word document
 */

// Include all important files
require_once '../config/config.php';
require_once '../config/grading_config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';

// Get all important code files
$files = [
    'Configuration Files' => [
        '../config/config.php' => 'Main Configuration',
        '../config/grading_config.php' => 'Grading System Configuration',
        '../config/database.php' => 'Database Configuration'
    ],
    'Core Authentication' => [
        '../includes/auth.php' => 'Authentication System',
        '../includes/session.php' => 'Session Management'
    ],
    'Test Results System' => [
        '../includes/test_results.php' => 'Test Results Management',
        '../student/test_results.php' => 'Student Test Results View',
        '../admin/test_results_management.php' => 'Admin Test Results Management'
    ],
    'Student Management' => [
        '../admin/manage_students.php' => 'Student Management Interface',
        '../admin/add_student.php' => 'Add New Student',
        '../student/dashboard.php' => 'Student Dashboard'
    ],
    'Main Interface' => [
        '../index.php' => 'Main Landing Page',
        '../admin/dashboard.php' => 'Admin Dashboard'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Portal - Code Documentation</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-before: always;
        }
        .header {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            color: #0066cc;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 5px;
        }
        .file-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #333;
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-left: 4px solid #0066cc;
        }
        .code-block {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 10px;
            overflow-x: auto;
        }
        .comment {
            color: #008000;
        }
        .string {
            color: #800000;
        }
        .keyword {
            color: #0000ff;
            font-weight: bold;
        }
        .function {
            color: #770000;
            font-weight: bold;
        }
        .variable {
            color: #660066;
        }
        .toc {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 30px;
        }
        .toc ul {
            list-style-type: none;
            padding-left: 20px;
        }
        .toc li {
            margin: 5px 0;
        }
        .toc a {
            text-decoration: none;
            color: #0066cc;
        }
        .toc a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        University Admission Portal - Code Documentation
    </div>

    <div class="section-title">Table of Contents</div>
    <div class="toc">
        <?php
        $pageCount = 1;
        foreach ($files as $section => $sectionFiles) {
            echo "<ul>";
            echo "<li><strong>$section</strong></li>";
            foreach ($sectionFiles as $filePath => $description) {
                echo "<li><a href='#section-" . md5($filePath) . "'>$description</a> (Page $pageCount)</li>";
                $pageCount++;
            }
            echo "</ul>";
        }
        ?>
    </div>

    <?php
    $currentPage = 1;
    foreach ($files as $section => $sectionFiles) {
        echo "<div class='page-break'></div>";
        echo "<div class='section-title'>$section</div>";
        
        foreach ($sectionFiles as $filePath => $description) {
            echo "<div id='section-" . md5($filePath) . "'></div>";
            echo "<div class='file-title'>$description - " . basename($filePath) . "</div>";
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                
                // Extract important parts (functions, classes, configurations)
                $importantCode = extractImportantCode($content, $filePath);
                
                echo "<div class='code-block'>";
                echo highlightCode($importantCode);
                echo "</div>";
                
                // Add page counter
                echo "<div style='text-align: right; font-size: 9px; color: #666; margin-top: 20px;'>Page $currentPage</div>";
                $currentPage++;
                
                // Add page break after each file if needed
                if ($currentPage % 3 == 0) {
                    echo "<div class='page-break'></div>";
                }
            } else {
                echo "<div class='code-block'>File not found: $filePath</div>";
            }
        }
    }
    ?>

    <div class="page-break"></div>
    <div class="section-title">System Architecture Overview</div>
    <div class="code-block">
The University Admission Portal is a comprehensive web-based system designed to manage student applications, test results, and administrative tasks.

ARCHITECTURE COMPONENTS:

1. FRONTEND LAYER
   - Bootstrap 5 for responsive UI
   - Font Awesome for icons
   - Custom CSS for styling

2. BACKEND LAYER
   - PHP 7.4+ for server-side logic
   - PDO for database operations
   - Session-based authentication

3. DATABASE LAYER
   - MySQL/MariaDB database
   - Structured tables for students, test results, admins

4. SECURITY FEATURES
   - Password hashing with bcrypt
   - SQL injection prevention
   - XSS protection
   - CSRF protection

5. KEY MODULES
   - Authentication & Authorization
   - Student Management
   - Test Results Processing
   - Dashboard Analytics
   - Report Generation

GRADING SYSTEM:
- Exam Rating: 0-100 scale
- Passing Threshold: 75
- Rating Categories: Excellent (90+), Very Good (85+), Passed (75+), Conditional (70+), Failed (<70)
- Subject Scores: General Info (30), Filipino (50), English (60), Science (60), Math (50)
    </div>

    <div class="page-break"></div>
    <div class="section-title">Database Schema</div>
    <div class="code-block">
KEY TABLES:

1. students
   - id (Primary)
   - first_name, last_name, middle_name
   - email, password
   - type (Freshman/Transferee)
   - status (Pending/Approved/Rejected/Enrolled)
   - created_at, updated_at

2. test_results
   - id (Primary)
   - student_id (Foreign Key)
   - permit_number
   - exam_date, exam_time
   - Subject scores (gen_info_raw, filipino_raw, etc.)
   - Transmuted scores
   - exam_rating, exam_percentage
   - overall_rating
   - gwa_score, interview_score
   - total_rating
   - recommendation

3. test_permits
   - id (Primary)
   - student_id (Foreign Key)
   - permit_number
   - exam_date, exam_time, exam_room
   - status (Pending/Approved/Rejected)

4. requirements
   - id (Primary)
   - student_id (Foreign Key)
   - requirement_type
   - file_path
   - status (Pending/Approved/Rejected)

5. admins
   - id (Primary)
   - username, password
   - full_name
   - email
   - created_at
    </div>

    <div class="page-break"></div>
    <div class="section-title">API Endpoints</div>
    <div class="code-block">
AUTHENTICATION ENDPOINTS:
- POST /login.php - User login
- POST /logout.php - User logout
- GET /auth/check.php - Check authentication status

STUDENT ENDPOINTS:
- GET /student/dashboard.php - Student dashboard
- GET /student/test_results.php - View test results
- POST /student/upload_requirements.php - Upload requirements

ADMIN ENDPOINTS:
- GET /admin/dashboard.php - Admin dashboard
- GET /admin/manage_students.php - Manage students
- POST /admin/add_student.php - Add new student
- GET /admin/test_results_management.php - Manage test results
- POST /admin/test_results_management.php - Process test results

SEARCH ENDPOINTS:
- POST /admin/test_results_management.php (action=fetch_student)
- GET /admin/manage_students.php (search parameter)
    </div>

    <div class="page-break"></div>
    <div class="section-title">Security Implementation</div>
    <div class="code-block">
SECURITY MEASURES:

1. PASSWORD SECURITY
   - Bcrypt hashing with salt
   - Minimum password length validation
   - Password strength requirements

2. SESSION SECURITY
   - Secure session configuration
   - Session timeout management
   - Regenerate session ID on login

3. INPUT VALIDATION
   - Sanitize all user inputs
   - Validate email formats
   - Check file upload types

4. SQL INJECTION PREVENTION
   - Prepared statements with PDO
   - Parameter binding
   - Input escaping

5. XSS PREVENTION
   - Output encoding with htmlspecialchars()
   - Content Security Policy headers
   - Input sanitization

6. CSRF PROTECTION
   - Token-based validation
   - SameSite cookie attributes
   - Referrer checking
    </div>

    <div class="page-break"></div>
    <div class="section-title">Deployment Instructions</div>
    <div class="code-block">
DEPLOYMENT STEPS:

1. SERVER REQUIREMENTS
   - PHP 7.4 or higher
   - MySQL 5.7 or MariaDB 10.2
   - Apache or Nginx web server
   - SSL certificate for HTTPS

2. CONFIGURATION
   - Update config/database.php with database credentials
   - Set appropriate file permissions
   - Configure email settings

3. DATABASE SETUP
   - Create database and user
   - Import schema from SQL file
   - Run initial data migration

4. SECURITY CONFIGURATION
   - Generate secure encryption keys
   - Set up SSL certificates
   - Configure firewall rules
   - Enable HTTPS redirect

5. PERFORMANCE OPTIMIZATION
   - Enable PHP OPcache
   - Configure database indexes
   - Set up caching headers
   - Optimize image compression
    </div>

    <div class="page-break"></div>
    <div class="section-title">Troubleshooting Guide</div>
    <div class="code-block">
COMMON ISSUES:

1. DATABASE CONNECTION ERRORS
   - Check database credentials in config
   - Verify database server is running
   - Ensure user has proper permissions

2. LOGIN ISSUES
   - Verify password hashing compatibility
   - Check session configuration
   - Ensure cookies are enabled

3. FILE UPLOAD PROBLEMS
   - Check upload directory permissions
   - Verify file size limits
   - Ensure proper MIME types

4. EMAIL NOT SENDING
   - Verify SMTP configuration
   - Check firewall settings
   - Test email credentials

5. PERFORMANCE ISSUES
   - Enable database query logging
   - Check for slow queries
   - Optimize database indexes
    </div>

</body>
</html>

<?php
/**
 * Extract important code sections from files
 */
function extractImportantCode($content, $filePath) {
    $lines = explode("\n", $content);
    $importantLines = [];
    $inImportantSection = false;
    $braceCount = 0;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip comments and empty lines at the beginning
        if (empty($trimmed) || (strpos($trimmed, '//') === 0 && !$inImportantSection)) {
            continue;
        }
        
        // Mark important sections
        if (strpos($trimmed, 'class ') !== false || 
            strpos($trimmed, 'function ') !== false ||
            strpos($trimmed, 'define(') !== false ||
            strpos($trimmed, 'require_once') !== false ||
            strpos($trimmed, 'public function') !== false ||
            strpos($trimmed, 'private function') !== false ||
            strpos($trimmed, 'protected function') !== false) {
            $inImportantSection = true;
            $importantLines[] = $line;
            
            // Count opening braces
            $braceCount += substr_count($line, '{') - substr_count($line, '}');
            continue;
        }
        
        if ($inImportantSection) {
            $importantLines[] = $line;
            $braceCount += substr_count($line, '{') - substr_count($line, '}');
            
            // End section when all braces are closed
            if ($braceCount <= 0 && strpos($trimmed, '}') !== false) {
                $inImportantSection = false;
                $importantLines[] = "\n// --- End of Section ---\n";
            }
        }
        
        // Limit to reasonable amount
        if (count($importantLines) > 100) {
            break;
        }
    }
    
    return implode("\n", $importantLines);
}

/**
 * Simple syntax highlighting for code
 */
function highlightCode($code) {
    // Basic syntax highlighting
    $code = htmlspecialchars($code);
    
    // Highlight keywords
    $keywords = ['class', 'function', 'if', 'else', 'foreach', 'while', 'return', 'public', 'private', 'protected', 'new', 'try', 'catch'];
    foreach ($keywords as $keyword) {
        $code = preg_replace("/\b$keyword\b/", '<span class="keyword">$0</span>', $code);
    }
    
    // Highlight comments
    $code = preg_replace('/(\/\/.*$)/m', '<span class="comment">$1</span>', $code);
    $code = preg_replace('/(\/\*.*?\*\/)/s', '<span class="comment">$1</span>', $code);
    
    // Highlight strings
    $code = preg_replace('/(["\'])([^"\']*)\1/', '<span class="string">$1$2$1</span>', $code);
    
    // Highlight variables
    $code = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*)/', '<span class="variable">$1</span>', $code);
    
    return $code;
}
?>
