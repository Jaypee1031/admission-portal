<?php
require_once __DIR__ . '/../repositories/StudentRepository.php';
require_once __DIR__ . '/../repositories/AdmissionFormRepository.php';
require_once __DIR__ . '/../repositories/RequirementsRepository.php';
require_once __DIR__ . '/../repositories/TestResultRepository.php';
// Authentication functions for University Admission Portal

class Auth {
    private $db;
    private $studentRepository;
    private $admissionFormRepository;
    private $requirementsRepository;
    private $testResultRepository;
    
    public function __construct() {
        $this->db = getDB();
        $this->studentRepository = new StudentRepository();
        $this->admissionFormRepository = new AdmissionFormRepository();
        $this->requirementsRepository = new RequirementsRepository();
        $this->testResultRepository = new TestResultRepository();
    }
    
    // Student Registration
    public function registerStudent($lastName, $firstName, $middleName, $email, $password, $type) {
        try {
            $existing = $this->studentRepository->findByEmail($email);
            
            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $studentId = $this->studentRepository->create([
                'last_name'   => $lastName,
                'first_name'  => $firstName,
                'middle_name' => $middleName,
                'email'       => $email,
                'password'    => $hashedPassword,
                'type'        => $type,
            ]);
            
            if ($studentId > 0) {
                return ['success' => true, 'message' => 'Registration successful', 'student_id' => $studentId];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Student Login with Rate Limiting
    public function loginStudent($email, $password) {
        try {
            // Sanitize email
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            
            // Check rate limiting
            if (!checkRateLimit($email)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            $student = $this->studentRepository->findByEmail($email);
            
            if ($student && password_verify($password, $student['password'])) {
                // Reset rate limit on successful login
                resetRateLimit($email);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['user_name'] = $student['name'];
                $_SESSION['user_email'] = $student['email'];
                $_SESSION['student_type'] = $student['type'];
                $_SESSION['student_status'] = $student['status'];
                $_SESSION['last_name'] = $student['last_name'];
                $_SESSION['first_name'] = $student['first_name'];
                $_SESSION['middle_name'] = $student['middle_name'];
                $_SESSION['f2_form_enabled'] = $student['f2_form_enabled'];
                $_SESSION['f2_form_completed'] = $student['f2_form_completed'];
                $_SESSION['test_result_available'] = $student['test_result_available'];
                $_SESSION['login_time'] = time();
                
                // Log successful login
                error_log("Student login successful: " . $email);
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                // Increment rate limit on failed login
                incrementRateLimit($email);
                
                // Log failed login attempt
                error_log("Failed login attempt for email: " . $email);
                
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again later.'];
        }
    }
    
    // Admin Login
    public function loginAdmin($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, password, full_name, role FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_name'] = $admin['full_name'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Logout
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Refresh student session data from database
     */
    public function refreshStudentSession($studentId) {
        try {
            $student = $this->studentRepository->find($studentId);
            
            if ($student) {
                // Update session variables with fresh data from database
                $_SESSION['student_status'] = $student['status'];
                $_SESSION['student_type'] = $student['type'];
                $_SESSION['f2_form_enabled'] = (bool)$student['f2_form_enabled'];
                $_SESSION['f2_form_completed'] = (bool)$student['f2_form_completed'];
                $_SESSION['test_result_available'] = (bool)$student['test_result_available'];
                $_SESSION['exam_completed'] = (bool)$student['exam_completed'];
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get current user info
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $user = [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'] === 'student' ? $_SESSION['student_type'] : $_SESSION['user_type'],
            'name' => $_SESSION['user_name']
        ];
        
        // If name is null, try to generate it from individual name fields
        if (empty($user['name']) && $_SESSION['user_type'] === 'student') {
            $lastName = $_SESSION['last_name'] ?? '';
            $firstName = $_SESSION['first_name'] ?? '';
            $middleName = $_SESSION['middle_name'] ?? '';
            
            if (!empty($lastName) && !empty($firstName)) {
                $user['name'] = $lastName . ', ' . $firstName;
                if (!empty($middleName)) {
                    $user['name'] .= ' ' . $middleName;
                }
            }
        }
        
        if ($_SESSION['user_type'] === 'student') {
            $user['email'] = $_SESSION['user_email'];
            $user['student_type'] = $_SESSION['student_type'];
            $user['status'] = $_SESSION['student_status'];
            $user['student_status'] = $_SESSION['student_status'];
            $user['f2_form_enabled'] = $_SESSION['f2_form_enabled'] ?? false;
            $user['f2_form_completed'] = $_SESSION['f2_form_completed'] ?? false;
            $user['test_result_available'] = $_SESSION['test_result_available'] ?? false;
        } elseif ($_SESSION['user_type'] === 'admin') {
            $user['username'] = $_SESSION['admin_username'];
            $user['role'] = $_SESSION['admin_role'];
        }
        
        return $user;
    }
    
    // Check if user has completed admission form
    public function hasCompletedAdmissionForm($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, last_name, first_name, birth_date, home_address, mobile_number, 
                       father_name, mother_name, last_school, course_first
                FROM admission_forms 
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $form = $stmt->fetch();
            
            if (!$form) {
                return false;
            }
            
            // Check if all required fields are filled
            $requiredFields = [
                'last_name', 'first_name', 'birth_date', 'home_address', 
                'mobile_number', 'father_name', 'mother_name', 'last_school', 'course_first'
            ];
            
            foreach ($requiredFields as $field) {
                if (empty($form[$field])) {
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Check if user has completed test permit
    public function hasCompletedTestPermit($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM test_permits WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get student progress
    public function getStudentProgress($studentId) {
        $progress = [
            'requirements' => false,
            'admission_form' => false,
            'test_permit' => false,
            'test_permit_approved' => false,
            'exam_completed' => false,
            'f2_form_enabled' => false,
            'f2_form_completed' => false,
            'test_results_available' => false,
            'completed' => false
        ];
        
        try {
            // Check requirements - must be ALL uploaded and approved
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                       COUNT(DISTINCT document_name) as unique_docs
                FROM requirements 
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $reqResult = $stmt->fetch();
            
            // Get student type to check required documents
            $studentStmt = $this->db->prepare("SELECT type FROM students WHERE id = ?");
            $studentStmt->execute([$studentId]);
            $student = $studentStmt->fetch();
            
            if ($student) {
                require_once __DIR__ . '/requirements.php';
                $requirements = new Requirements();
                $requiredDocs = $requirements->getRequirementsList($student['type'], $studentId);
                $requiredCount = count($requiredDocs);
                
                // Requirements are complete if all required docs are uploaded (approval happens with test permit)
                $progress['requirements'] = ($reqResult['total'] >= $requiredCount);
            }
            
            // Check admission form - only available after requirements are complete
            $progress['admission_form'] = $this->hasCompletedAdmissionForm($studentId);
            
            // Check test permit - only available after admission form is complete
            $progress['test_permit'] = $this->hasCompletedTestPermit($studentId);
            
            // Check test permit approval status
            if ($progress['test_permit']) {
                $stmt = $this->db->prepare("SELECT status FROM test_permits WHERE student_id = ? ORDER BY issued_at DESC LIMIT 1");
                $stmt->execute([$studentId]);
                $permitResult = $stmt->fetch();
                $progress['test_permit_approved'] = ($permitResult && $permitResult['status'] === 'Approved');
            }
            
            // Check if exam is completed (admin sets this or if test results are available)
            $stmt = $this->db->prepare("SELECT exam_completed, test_result_available FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $studentResult = $stmt->fetch();
            $progress['exam_completed'] = $studentResult ? ((bool)$studentResult['exam_completed'] || (bool)$studentResult['test_result_available']) : false;
            
            // Check Personal Data form status
            $stmt = $this->db->prepare("SELECT f2_form_enabled, f2_form_completed FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $f2Result = $stmt->fetch();
            $progress['f2_form_enabled'] = $f2Result ? (bool)$f2Result['f2_form_enabled'] : false;
            $progress['f2_form_completed'] = $f2Result ? (bool)$f2Result['f2_form_completed'] : false;
            
            
            // Check test results availability
            $stmt = $this->db->prepare("SELECT test_result_available FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $testResult = $stmt->fetch();
            $progress['test_results_available'] = $testResult ? (bool)$testResult['test_result_available'] : false;
            
            // Check if all steps are completed
            $progress['completed'] = $progress['requirements'] && 
                                   $progress['admission_form'] && 
                                   $progress['test_permit_approved'] && 
                                   $progress['exam_completed'] && 
                                   $progress['f2_form_completed'] && 
                                   $progress['test_results_available'];
            
            // Auto-update student status based on completion
            $this->updateStudentStatusBasedOnProgress($studentId, $progress);
            
        } catch (PDOException $e) {
            // Handle error silently
        }
        
        return $progress;
    }
    
    /**
     * Automatically update student status based on their progress
     */
    public function updateStudentStatusBasedOnProgress($studentId, $progress) {
        try {
            // Get current student status
            $stmt = $this->db->prepare("SELECT status FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $currentStatus = $stmt->fetchColumn();
            
            $newStatus = $currentStatus; // Default to current status
            
            // Determine new status based on progress
            if ($progress['completed']) {
                // All steps completed - set to APPROVED
                $newStatus = 'APPROVED';
            } elseif ($progress['test_permit_approved'] && $progress['exam_completed']) {
                // Test permit approved and exam completed - set to EXAM_COMPLETED
                $newStatus = 'EXAM_COMPLETED';
            } elseif ($progress['test_permit_approved']) {
                // Test permit approved - set to PERMIT_APPROVED
                $newStatus = 'PERMIT_APPROVED';
            } elseif ($progress['admission_form'] && $progress['test_permit']) {
                // Admission form and test permit submitted - set to UNDER_REVIEW
                $newStatus = 'UNDER_REVIEW';
            } elseif ($progress['admission_form']) {
                // Admission form completed - set to FORM_COMPLETED
                $newStatus = 'FORM_COMPLETED';
            } elseif ($progress['requirements']) {
                // Requirements uploaded - set to REQUIREMENTS_SUBMITTED
                $newStatus = 'REQUIREMENTS_SUBMITTED';
            } else {
                // Just registered - keep as PENDING
                $newStatus = 'PENDING';
            }
            
            // Update status if it has changed
            if ($newStatus !== $currentStatus) {
                $updateStmt = $this->db->prepare("UPDATE students SET status = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $studentId]);
                
                // Update session if this is the current user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $studentId) {
                    $_SESSION['student_status'] = $newStatus;
                }
                
                return ['success' => true, 'old_status' => $currentStatus, 'new_status' => $newStatus];
            }
            
            return ['success' => true, 'status' => $currentStatus, 'unchanged' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Manually update status for a specific student
     */
    public function updateStudentStatus($studentId, $newStatus) {
        try {
            $stmt = $this->db->prepare("UPDATE students SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $studentId]);
            
            // Update session if this is the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $studentId) {
                $_SESSION['student_status'] = $newStatus;
            }
            
            return ['success' => true, 'message' => 'Status updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update status for all students based on their current progress
     */
    public function updateAllStudentStatuses() {
        try {
            $stmt = $this->db->prepare("SELECT id FROM students");
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $updated = 0;
            $unchanged = 0;
            
            foreach ($students as $studentId) {
                $progress = $this->getStudentProgress($studentId);
                $result = $this->updateStudentStatusBasedOnProgress($studentId, $progress);
                
                if ($result['success'] && !isset($result['unchanged'])) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }
            
            return [
                'success' => true, 
                'message' => "Updated {$updated} students, {$unchanged} unchanged",
                'updated' => $updated,
                'unchanged' => $unchanged
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Change admin password
     */
    public function changeAdminPassword($adminId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->db->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($currentPassword, $admin['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $adminId])) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update admin profile information
     */
    public function updateAdminProfile($adminId, $fullName, $email = null) {
        try {
            $stmt = $this->db->prepare("UPDATE admins SET full_name = ? WHERE id = ?");
            if ($stmt->execute([$fullName, $adminId])) {
                // Update session if this is the current user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $adminId) {
                    $_SESSION['user_name'] = $fullName;
                }
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get admin profile information
     */
    public function getAdminProfile($adminId) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, full_name, role, created_at FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}

// Initialize Auth class
$auth = new Auth();
?>
