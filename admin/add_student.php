<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$db = getDB();

// Handle email availability check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_email'])) {
    header('Content-Type: application/json');
    
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['available' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch();
        
        echo json_encode(['available' => !$exists]);
    } catch (PDOException $e) {
        echo json_encode(['available' => false, 'message' => 'Database error']);
    }
    exit;
}

// Handle AJAX form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $type = sanitizeInput($_POST['type'] ?? '');
    
    // Quick validation
    $errors = [];
    
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
    if (!in_array($type, ['Freshman', 'Transferee'])) $errors[] = "Invalid student type";
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            
            // Add student immediately
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO students (first_name, last_name, middle_name, email, password, type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())
            ");
            $stmt->execute([
                $firstName, 
                $lastName, 
                sanitizeInput($_POST['middle_name'] ?? ''), 
                $email, 
                $hashedPassword, 
                $type
            ]);
            
            $studentId = $db->lastInsertId();
            
            // Create admission form record with all the additional data
            $stmt = $db->prepare("
                INSERT INTO admission_forms (
                    student_id, first_name, last_name, middle_name, birth_date, sex, 
                    home_address, mobile_number, father_name, father_occupation, 
                    mother_name, mother_occupation, last_school, school_address, 
                    overall_gwa, course_first, course_second, course_third, 
                    guardian_name, guardian_contact, birth_place, ethnic_affiliation, 
                    civil_status, disability, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $studentId,
                $firstName,
                $lastName,
                sanitizeInput($_POST['middle_name'] ?? ''),
                sanitizeInput($_POST['birth_date'] ?? ''),
                sanitizeInput($_POST['sex'] ?? ''),
                sanitizeInput($_POST['home_address'] ?? ''),
                sanitizeInput($_POST['mobile_number'] ?? ''),
                sanitizeInput($_POST['father_name'] ?? ''),
                sanitizeInput($_POST['father_occupation'] ?? ''),
                sanitizeInput($_POST['mother_name'] ?? ''),
                sanitizeInput($_POST['mother_occupation'] ?? ''),
                sanitizeInput($_POST['last_school'] ?? ''),
                sanitizeInput($_POST['school_address'] ?? ''),
                sanitizeInput($_POST['overall_gwa'] ?? ''),
                sanitizeInput($_POST['course_first'] ?? ''),
                sanitizeInput($_POST['course_second'] ?? ''),
                sanitizeInput($_POST['course_third'] ?? ''),
                sanitizeInput($_POST['guardian_name'] ?? ''),
                sanitizeInput($_POST['guardian_contact'] ?? ''),
                sanitizeInput($_POST['birth_place'] ?? ''),
                sanitizeInput($_POST['ethnic_affiliation'] ?? ''),
                sanitizeInput($_POST['civil_status'] ?? ''),
                sanitizeInput($_POST['disability'] ?? '')
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Student added successfully!',
                'student_id' => $studentId,
                'student_name' => $firstName . ' ' . $lastName
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    exit;
}

// Handle regular form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $type = sanitizeInput($_POST['type']);
    
    // Validation
    $errors = [];
    
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
    if (!in_array($type, ['Freshman', 'Transferee'])) $errors[] = "Invalid student type";
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO students (first_name, last_name, middle_name, email, password, type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())
            ");
            $stmt->execute([
                $firstName, 
                $lastName, 
                sanitizeInput($_POST['middle_name'] ?? ''), 
                $email, 
                $hashedPassword, 
                $type
            ]);
            
            $studentId = $db->lastInsertId();
            
            // Create admission form record with all the additional data
            $stmt = $db->prepare("
                INSERT INTO admission_forms (
                    student_id, first_name, last_name, middle_name, birth_date, sex, 
                    home_address, mobile_number, father_name, father_occupation, 
                    mother_name, mother_occupation, last_school, school_address, 
                    overall_gwa, course_first, course_second, course_third, 
                    guardian_name, guardian_contact, birth_place, ethnic_affiliation, 
                    civil_status, disability, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $studentId,
                $firstName,
                $lastName,
                sanitizeInput($_POST['middle_name'] ?? ''),
                sanitizeInput($_POST['birth_date'] ?? ''),
                sanitizeInput($_POST['sex'] ?? ''),
                sanitizeInput($_POST['home_address'] ?? ''),
                sanitizeInput($_POST['mobile_number'] ?? ''),
                sanitizeInput($_POST['father_name'] ?? ''),
                sanitizeInput($_POST['father_occupation'] ?? ''),
                sanitizeInput($_POST['mother_name'] ?? ''),
                sanitizeInput($_POST['mother_occupation'] ?? ''),
                sanitizeInput($_POST['last_school'] ?? ''),
                sanitizeInput($_POST['school_address'] ?? ''),
                sanitizeInput($_POST['overall_gwa'] ?? ''),
                sanitizeInput($_POST['course_first'] ?? ''),
                sanitizeInput($_POST['course_second'] ?? ''),
                sanitizeInput($_POST['course_third'] ?? ''),
                sanitizeInput($_POST['guardian_name'] ?? ''),
                sanitizeInput($_POST['guardian_contact'] ?? ''),
                sanitizeInput($_POST['birth_place'] ?? ''),
                sanitizeInput($_POST['ethnic_affiliation'] ?? ''),
                sanitizeInput($_POST['civil_status'] ?? ''),
                sanitizeInput($_POST['disability'] ?? '')
            ]);
            
            showAlert('Student and admission form added successfully!', 'success');
            redirect('add_student.php');
        } catch (PDOException $e) {
            showAlert('Error adding student: ' . $e->getMessage(), 'error');
        }
    } else {
        $errorMessage = implode('<br>', $errors);
        showAlert($errorMessage, 'error');
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
           <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="applicantsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i>Applicants
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="applicantsDropdown">
                        <li><a class="dropdown-item" href="applicants.php">
                            <i class="fas fa-list me-2"></i>All Applicants
                        </a></li>
                        <li><a class="dropdown-item" href="test_permits.php">
                            <i class="fas fa-id-card me-2"></i>Test Permits
                        </a></li>
                        <li><a class="dropdown-item" href="admission_forms.php">
                            <i class="fas fa-file-alt me-2"></i>Admission Forms
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="f2_forms_management.php">
                            <i class="fas fa-file-alt me-2"></i>Personal Data Forms
                        </a></li>
                        <li><a class="dropdown-item" href="test_results_management.php">
                            <i class="fas fa-chart-line me-2"></i>Test Results
                        </a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="manageDropdown">
                        <li><a class="dropdown-item" href="manage_students.php">
                            <i class="fas fa-user-cog me-2"></i>Manage Students
                        </a></li>
                        <li><a class="dropdown-item active" href="add_student.php">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </a></li>
                        <li><a class="dropdown-item" href="add_admin.php">
                            <i class="fas fa-user-shield me-2"></i>Add Admin
                        </a></li>
                    </ul>
                </div>
                <a class="nav-link" href="test_permit_stats.php">
                    <i class="fas fa-chart-bar me-1"></i>Statistics
                </a>
                <a class="nav-link" href="test_permit_settings.php">
                    <i class="fas fa-cog me-1"></i>Settings
                </a>
                <div class="navbar-text me-3 d-flex align-items-center">
                    <i class="fas fa-user-shield me-2 text-warning"></i>
                    <span class="fw-bold">System Administrator</span>
                </div>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h2 class="mb-2">
                        <i class="fas fa-user-plus me-2 text-primary"></i>
                        Add New Student
                    </h2>
                    <p class="text-muted mb-0">Create a new student account in the system.</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Add Student Form -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="dashboard-card">
                    <!-- Success/Error Messages -->
                    <div id="messageContainer" class="d-none">
                        <div id="messageAlert" class="alert" role="alert">
                            <span id="messageText"></span>
                            <button type="button" class="btn-close" onclick="hideMessage()"></button>
                        </div>
                    </div>
                    
                    <form id="addStudentForm" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid first name.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid last name.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                            <div id="emailStatus" class="form-text"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password_icon"></i>
                                </button>
                            </div>
                            <div class="form-text">Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.</div>
                            <div class="invalid-feedback">
                                Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.
                            </div>
                            <div id="passwordStrength" class="form-text"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Student Type *</label>
                            <select class="form-select" id="type" name="type" required onchange="toggleAdditionalFields()">
                                <option value="">Select student type...</option>
                                <option value="Freshman" <?php echo ($_POST['type'] ?? '') === 'Freshman' ? 'selected' : ''; ?>>Freshman</option>
                                <option value="Transferee" <?php echo ($_POST['type'] ?? '') === 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a student type.
                            </div>
                        </div>
                        
                        <!-- Additional Personal Information -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </h5>
                        </div>
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control auto-caps" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="birth_date" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="sex" class="form-label">Sex</label>
                                <select class="form-select" id="sex" name="sex">
                                    <option value="">Select sex...</option>
                                    <option value="Male" <?php echo ($_POST['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($_POST['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                       value="<?php echo htmlspecialchars($_POST['mobile_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 additional-field" style="display: none;">
                            <label for="home_address" class="form-label">Home Address</label>
                            <textarea class="form-control" id="home_address" name="home_address" rows="2"><?php echo htmlspecialchars($_POST['home_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Family Information -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-users me-2"></i>Family Information
                            </h5>
                        </div>
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="father_name" class="form-label">Father's Name</label>
                                <input type="text" class="form-control auto-caps" id="father_name" name="father_name" 
                                       value="<?php echo htmlspecialchars($_POST['father_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="father_occupation" class="form-label">Father's Occupation</label>
                                <input type="text" class="form-control auto-caps" id="father_occupation" name="father_occupation" 
                                       value="<?php echo htmlspecialchars($_POST['father_occupation'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                        </div>
                        
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="mother_name" class="form-label">Mother's Name</label>
                                <input type="text" class="form-control auto-caps" id="mother_name" name="mother_name" 
                                       value="<?php echo htmlspecialchars($_POST['mother_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mother_occupation" class="form-label">Mother's Occupation</label>
                                <input type="text" class="form-control auto-caps" id="mother_occupation" name="mother_occupation" 
                                       value="<?php echo htmlspecialchars($_POST['mother_occupation'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                        </div>
                        
                        <!-- Educational Information -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-graduation-cap me-2"></i>Educational Information
                            </h5>
                        </div>
                        <div class="mb-3 additional-field" style="display: none;">
                            <label for="last_school" class="form-label">Last School Attended</label>
                            <input type="text" class="form-control auto-caps" id="last_school" name="last_school" 
                                   value="<?php echo htmlspecialchars($_POST['last_school'] ?? ''); ?>" 
                                   style="text-transform: uppercase;">
                        </div>
                        
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="school_address" class="form-label">School Address</label>
                                <textarea class="form-control" id="school_address" name="school_address" rows="2"><?php echo htmlspecialchars($_POST['school_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="overall_gwa" class="form-label">Overall GWA</label>
                                <input type="number" class="form-control" id="overall_gwa" name="overall_gwa" 
                                       value="<?php echo htmlspecialchars($_POST['overall_gwa'] ?? ''); ?>" 
                                       step="0.01" min="0" max="100" placeholder="e.g., 85.50">
                            </div>
                        </div>
                        
                        <!-- Course Preferences -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-book me-2"></i>Course Preferences
                            </h5>
                        </div>
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-4 mb-3">
                                <label for="course_first" class="form-label">1st Choice Course</label>
                                <input type="text" class="form-control" id="course_first" name="course_first" 
                                       value="<?php echo htmlspecialchars($_POST['course_first'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_second" class="form-label">2nd Choice Course</label>
                                <input type="text" class="form-control" id="course_second" name="course_second" 
                                       value="<?php echo htmlspecialchars($_POST['course_second'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_third" class="form-label">3rd Choice Course</label>
                                <input type="text" class="form-control" id="course_third" name="course_third" 
                                       value="<?php echo htmlspecialchars($_POST['course_third'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <!-- Guardian Information -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-user-shield me-2"></i>Guardian Information
                            </h5>
                        </div>
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="guardian_name" class="form-label">Guardian's Name</label>
                                <input type="text" class="form-control auto-caps" id="guardian_name" name="guardian_name" 
                                       value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guardian_contact" class="form-label">Guardian's Contact</label>
                                <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" 
                                       value="<?php echo htmlspecialchars($_POST['guardian_contact'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="additional-field" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary">
                                <i class="fas fa-info-circle me-2"></i>Additional Information
                            </h5>
                        </div>
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="birth_place" class="form-label">Place of Birth</label>
                                <input type="text" class="form-control auto-caps" id="birth_place" name="birth_place" 
                                       value="<?php echo htmlspecialchars($_POST['birth_place'] ?? ''); ?>" 
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ethnic_affiliation" class="form-label">Ethnic Affiliation</label>
                                <select class="form-select" id="ethnic_affiliation" name="ethnic_affiliation">
                                    <option value="">Select ethnicity...</option>
                                    <option value="Ilocano" <?php echo ($_POST['ethnic_affiliation'] ?? '') === 'Ilocano' ? 'selected' : ''; ?>>Ilocano</option>
                                    <option value="Tagalog" <?php echo ($_POST['ethnic_affiliation'] ?? '') === 'Tagalog' ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="Ifugao" <?php echo ($_POST['ethnic_affiliation'] ?? '') === 'Ifugao' ? 'selected' : ''; ?>>Ifugao</option>
                                    <option value="Others" <?php echo ($_POST['ethnic_affiliation'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row additional-field" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="civil_status" class="form-label">Civil Status</label>
                                <select class="form-select" id="civil_status" name="civil_status">
                                    <option value="">Select civil status...</option>
                                    <option value="Single" <?php echo ($_POST['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($_POST['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo ($_POST['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Divorced" <?php echo ($_POST['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Separated" <?php echo ($_POST['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="disability" class="form-label">Disability (if any)</label>
                                <input type="text" class="form-control" id="disability" name="disability" 
                                       value="<?php echo htmlspecialchars($_POST['disability'] ?? ''); ?>" 
                                       placeholder="None or specify">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="applicants.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-user-plus me-1"></i>Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Toggle additional fields based on student type
        function toggleAdditionalFields() {
            const studentType = document.getElementById('type').value;
            const additionalFields = document.querySelectorAll('.additional-field');
            
            if (studentType) {
                additionalFields.forEach(field => {
                    field.style.display = 'block';
                });
            } else {
                additionalFields.forEach(field => {
                    field.style.display = 'none';
                });
            }
        }

        // Show message
        function showMessage(message, type = 'success') {
            const container = document.getElementById('messageContainer');
            const alert = document.getElementById('messageAlert');
            const text = document.getElementById('messageText');
            
            alert.className = `alert alert-${type === 'error' ? 'danger' : type}`;
            text.innerHTML = message;
            container.classList.remove('d-none');
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    hideMessage();
                }, 5000);
            }
        }

        // Hide message
        function hideMessage() {
            document.getElementById('messageContainer').classList.add('d-none');
        }

        // Check password strength
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            if (!password) {
                strengthDiv.innerHTML = '';
                return;
            }

            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('Use at least 8 characters');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Add lowercase letters');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Add uppercase letters');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Add numbers');

            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('Add special characters');

            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];
            
            if (strength > 0) {
                strengthDiv.innerHTML = `<i class="fas fa-shield-alt me-1 text-${strengthColors[strength-1]}"></i>Password strength: ${strengthText[strength-1]}`;
                strengthDiv.className = `form-text text-${strengthColors[strength-1]}`;
            } else {
                strengthDiv.innerHTML = '';
            }
        }

        // Real-time email validation
        let emailTimeout;
        function validateEmail(email) {
            const statusDiv = document.getElementById('emailStatus');
            
            if (!email) {
                statusDiv.innerHTML = '';
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                statusDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Invalid email format';
                statusDiv.className = 'form-text text-danger';
                return;
            }

            // Clear previous timeout
            clearTimeout(emailTimeout);
            
            // Show checking status
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-info me-1"></i>Checking availability...';
            statusDiv.className = 'form-text text-info';

            // Debounce email check
            emailTimeout = setTimeout(() => {
                fetch('add_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `check_email=1&email=${encodeURIComponent(email)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        statusDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Email is available';
                        statusDiv.className = 'form-text text-success';
                    } else {
                        statusDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Email already exists';
                        statusDiv.className = 'form-text text-danger';
                    }
                })
                .catch(() => {
                    statusDiv.innerHTML = '';
                });
            }, 500);
        }

        // Form validation and AJAX submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addStudentForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordField = document.getElementById('password');
            const emailField = document.getElementById('email');

            // Real-time password strength checking
            passwordField.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Real-time email validation
            emailField.addEventListener('input', function() {
                validateEmail(this.value);
            });

            // Form submission with AJAX
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!form.checkValidity()) {
                    e.stopPropagation();
                        form.classList.add('was-validated');
                    return;
                }

                // Disable submit button and show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding Student...';

                // Collect form data
                const formData = new FormData(form);

                // Submit via AJAX
                fetch('add_student.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        form.reset();
                        form.classList.remove('was-validated');
                        
                        // Clear status indicators
                        document.getElementById('emailStatus').innerHTML = '';
                        document.getElementById('passwordStrength').innerHTML = '';
                        
                        // Optional: Redirect to students list or show success with student ID
                        setTimeout(() => {
                            if (confirm(`Student "${data.student_name}" added successfully! Would you like to add another student?`)) {
                                // Stay on page for another entry
                            } else {
                                window.location.href = 'applicants.php';
                            }
                        }, 1000);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('An error occurred while adding the student. Please try again.', 'error');
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Add Student';
                });
            });

            // Auto-capitalize name fields
            const nameFields = ['first_name', 'last_name', 'middle_name', 'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'guardian_name', 'last_school', 'birth_place'];
            nameFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                }
            });
        });
    </script>
</body>
</html>
