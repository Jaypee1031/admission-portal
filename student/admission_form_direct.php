<?php
// Direct access to admission form - bypasses all checks
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/requirements.php';
require_once '../includes/admission_form.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$studentId = $user['id'];

// Check if requirements are uploaded and approved
$progress = $auth->getStudentProgress($studentId);
if (!$progress['requirements']) {
    showAlert('Please upload and get approval for all required documents first', 'error');
    redirect('requirements.php');
}

// Clear any existing alerts
clearAlert();

try {
    $formData = $admissionForm->getAdmissionForm($studentId);
    $courses = $admissionForm->getAvailableCourses();
    $strands = $admissionForm->getAvailableStrands();
    
    // Ensure formData is an array
    if (!$formData) {
        $formData = [];
    }
} catch (Exception $e) {
    error_log("Admission form error: " . $e->getMessage());
    $formData = [];
    $courses = [];
    $strands = [];
}

$alert = getAlert();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'last_name' => sanitizeInput($_POST['last_name']),
        'first_name' => sanitizeInput($_POST['first_name']),
        'middle_name' => sanitizeInput($_POST['middle_name']),
        'name_extension' => sanitizeInput($_POST['name_extension']),
        'sex' => sanitizeInput($_POST['sex']),
        'gender' => sanitizeInput($_POST['gender']),
        'civil_status' => sanitizeInput($_POST['civil_status']),
        'spouse_name' => sanitizeInput($_POST['spouse_name']),
        'age' => sanitizeInput($_POST['age']),
        'birth_date' => sanitizeInput($_POST['birth_date']),
        'birth_place' => sanitizeInput($_POST['birth_place']),
        'pwd' => isset($_POST['pwd']) ? 1 : 0,
        'disability' => sanitizeInput($_POST['disability']),
        'ethnic_affiliation' => sanitizeInput($_POST['ethnic_affiliation']),
        'home_address' => sanitizeInput($_POST['home_address']),
        'mobile_number' => sanitizeInput($_POST['mobile_number']),
        'email_address' => sanitizeInput($_POST['email_address']),
        'father_name' => sanitizeInput($_POST['father_name']),
        'father_occupation' => sanitizeInput($_POST['father_occupation']),
        'father_contact' => sanitizeInput($_POST['father_contact']),
        'mother_name' => sanitizeInput($_POST['mother_name']),
        'mother_occupation' => sanitizeInput($_POST['mother_occupation']),
        'mother_contact' => sanitizeInput($_POST['mother_contact']),
        'guardian_name' => sanitizeInput($_POST['guardian_name']),
        'guardian_occupation' => sanitizeInput($_POST['guardian_occupation']),
        'guardian_contact' => sanitizeInput($_POST['guardian_contact']),
        'last_school' => sanitizeInput($_POST['last_school']),
        'school_address' => sanitizeInput($_POST['school_address']),
        'year_last_attended' => sanitizeInput($_POST['year_last_attended']),
        'strand_taken' => sanitizeInput($_POST['strand_taken']),
        'year_graduated' => sanitizeInput($_POST['year_graduated']),
        'course_first' => sanitizeInput($_POST['course_first']),
        'course_second' => sanitizeInput($_POST['course_second']),
        'course_third' => sanitizeInput($_POST['course_third'])
    ];
    
    $result = $admissionForm->saveAdmissionForm($studentId, $formData);
    
    if ($result['success']) {
        showAlert($result['message'], 'success');
        // Don't redirect immediately, let user see the success message
        // redirect('dashboard.php');
    } else {
        showAlert($result['message'], 'error');
    }
    
    $alert = getAlert();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Admission Form - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .age-input {
            font-weight: bold;
            color: #2c5530;
        }
        .age-calculated {
            background-color: #d4edda !important;
            border-color: #c3e6cb !important;
            animation: highlight 0.5s ease-in-out;
        }
        @keyframes highlight {
            0% { background-color: #d4edda; }
            50% { background-color: #c3e6cb; }
            100% { background-color: #d4edda; }
        }
    </style>
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
                <span class="navbar-text me-3">
                    <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>
                </span>
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
                        <i class="fas fa-file-alt me-2 text-primary"></i>
                        Pre-Admission Form (F1-A)
                    </h2>
                    <p class="text-muted mb-0">Please fill out all required information accurately.</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($alert): ?>
            <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $alert['type'] === 'error' ? 'exclamation-triangle' : ($alert['type'] === 'success' ? 'check-circle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($alert['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Success Message (hidden by default) -->
        <div id="successMessage" class="alert alert-success mt-3" style="display: none;">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Form saved successfully!</strong> Your admission form has been saved.
            <div class="mt-3">
                <a href="test_permit.php" class="btn btn-success">
                    <i class="fas fa-arrow-right me-2"></i>Request Test Permit
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
            </div>
        </div>

        <!-- Admission Form -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <form id="admissionForm" method="POST" action="">
                        <!-- Personal Information -->
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control auto-caps" id="last_name" name="last_name" 
                                       value="<?php echo $formData['last_name'] ?? $_SESSION['last_name'] ?? ''; ?>" 
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control auto-caps" id="first_name" name="first_name" 
                                       value="<?php echo $formData['first_name'] ?? $_SESSION['first_name'] ?? ''; ?>" 
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control auto-caps" id="middle_name" name="middle_name" 
                                       value="<?php echo $formData['middle_name'] ?? $_SESSION['middle_name'] ?? ''; ?>"
                                       style="text-transform: uppercase;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="name_extension" class="form-label">Name Extension</label>
                                <select class="form-select" id="name_extension" name="name_extension">
                                    <option value="">Select</option>
                                    <option value="Jr." <?php echo ($formData['name_extension'] ?? '') === 'Jr.' ? 'selected' : ''; ?>>Jr.</option>
                                    <option value="Sr." <?php echo ($formData['name_extension'] ?? '') === 'Sr.' ? 'selected' : ''; ?>>Sr.</option>
                                    <option value="II" <?php echo ($formData['name_extension'] ?? '') === 'II' ? 'selected' : ''; ?>>II</option>
                                    <option value="III" <?php echo ($formData['name_extension'] ?? '') === 'III' ? 'selected' : ''; ?>>III</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="sex" class="form-label">Sex *</label>
                                <select class="form-select" id="sex" name="sex" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo ($formData['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($formData['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo ($formData['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($formData['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($formData['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="civil_status" class="form-label">Civil Status *</label>
                                <select class="form-select" id="civil_status" name="civil_status" required>
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo ($formData['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($formData['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo ($formData['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Separated" <?php echo ($formData['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="spouse_name" class="form-label">Spouse Name (if married)</label>
                                <input type="text" class="form-control" id="spouse_name" name="spouse_name" value="<?php echo $formData['spouse_name'] ?? ''; ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="age" class="form-label">Age *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control age-input" id="age" name="age" value="<?php echo $formData['age'] ?? ''; ?>" placeholder="Age" min="1" max="120" required>
                                    <button type="button" class="btn btn-outline-secondary" id="calculateAgeBtn" title="Calculate from birth date">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">Enter manually or calculate from birth date</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="birth_date" class="form-label">Birth Date *</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $formData['birth_date'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="birth_place" class="form-label">Birth Place *</label>
                                <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo $formData['birth_place'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ethnic_affiliation" class="form-label">Ethnic Affiliation</label>
                                <input type="text" class="form-control" id="ethnic_affiliation" name="ethnic_affiliation" value="<?php echo $formData['ethnic_affiliation'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="pwd" name="pwd" <?php echo ($formData['pwd'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pwd">
                                        Person with Disability (PWD)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="disability" class="form-label">Type of Disability (if PWD)</label>
                                <input type="text" class="form-control" id="disability" name="disability" value="<?php echo $formData['disability'] ?? ''; ?>">
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-address-book me-2"></i>Contact Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="home_address" class="form-label">Home Address *</label>
                                <textarea class="form-control" id="home_address" name="home_address" rows="3" required><?php echo $formData['home_address'] ?? ''; ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number *</label>
                                <input type="tel" class="form-control" id="mobile_number" name="mobile_number" value="<?php echo $formData['mobile_number'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email_address" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email_address" name="email_address" value="<?php echo $formData['email_address'] ?? $user['email'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <!-- Family Information -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-users me-2"></i>Family Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="father_name" class="form-label">Father's Name</label>
                                <input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo $formData['father_name'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="father_occupation" class="form-label">Father's Occupation</label>
                                <input type="text" class="form-control" id="father_occupation" name="father_occupation" value="<?php echo $formData['father_occupation'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="father_contact" class="form-label">Father's Contact</label>
                                <input type="tel" class="form-control" id="father_contact" name="father_contact" value="<?php echo $formData['father_contact'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="mother_name" class="form-label">Mother's Name</label>
                                <input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo $formData['mother_name'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mother_occupation" class="form-label">Mother's Occupation</label>
                                <input type="text" class="form-control" id="mother_occupation" name="mother_occupation" value="<?php echo $formData['mother_occupation'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mother_contact" class="form-label">Mother's Contact</label>
                                <input type="tel" class="form-control" id="mother_contact" name="mother_contact" value="<?php echo $formData['mother_contact'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="guardian_name" class="form-label">Guardian's Name</label>
                                <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="<?php echo $formData['guardian_name'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="guardian_occupation" class="form-label">Guardian's Occupation</label>
                                <input type="text" class="form-control" id="guardian_occupation" name="guardian_occupation" value="<?php echo $formData['guardian_occupation'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="guardian_contact" class="form-label">Guardian's Contact</label>
                                <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" value="<?php echo $formData['guardian_contact'] ?? ''; ?>">
                            </div>
                        </div>

                        <!-- Educational Background -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-graduation-cap me-2"></i>Educational Background
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="last_school" class="form-label">Last School Attended *</label>
                                <input type="text" class="form-control" id="last_school" name="last_school" value="<?php echo $formData['last_school'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="school_address" class="form-label">School Address *</label>
                                <input type="text" class="form-control" id="school_address" name="school_address" value="<?php echo $formData['school_address'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="year_last_attended" class="form-label">Year Last Attended *</label>
                                <input type="number" class="form-control" id="year_last_attended" name="year_last_attended" value="<?php echo $formData['year_last_attended'] ?? ''; ?>" min="1900" max="2030" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="strand_taken" class="form-label">Strand Taken</label>
                                <select class="form-select" id="strand_taken" name="strand_taken">
                                    <option value="">Select Strand</option>
                                    <?php foreach ($strands as $strand): ?>
                                        <option value="<?php echo htmlspecialchars($strand); ?>" <?php echo ($formData['strand_taken'] ?? '') === $strand ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($strand); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="year_graduated" class="form-label">Year Graduated</label>
                                <input type="number" class="form-control" id="year_graduated" name="year_graduated" value="<?php echo $formData['year_graduated'] ?? ''; ?>" min="1900" max="2030">
                            </div>
                        </div>

                        <!-- Course Preferences -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary">
                            <i class="fas fa-list me-2"></i>Course Preferences
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="course_first" class="form-label">First Choice *</label>
                                <select class="form-select" id="course_first" name="course_first" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($formData['course_first'] ?? '') === $course ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_second" class="form-label">Second Choice</label>
                                <select class="form-select" id="course_second" name="course_second">
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($formData['course_second'] ?? '') === $course ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_third" class="form-label">Third Choice</label>
                                <select class="form-select" id="course_third" name="course_third">
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($formData['course_third'] ?? '') === $course ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Save Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('admissionForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            } else {
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
            }
        });

        // Check if form was submitted successfully (PHP success message)
        document.addEventListener('DOMContentLoaded', function() {
            const alertElement = document.querySelector('.alert-success');
            if (alertElement && alertElement.textContent.includes('successfully')) {
                // Show success message and hide form
                const successMessage = document.getElementById('successMessage');
                const admissionForm = document.getElementById('admissionForm');
                
                if (successMessage && admissionForm) {
                    successMessage.style.display = 'block';
                    admissionForm.style.display = 'none';
                }
            }
        });

        // Function to calculate age from birth date
        function calculateAge(birthDate) {
            if (!birthDate) return '';
            
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }

        // Auto-calculate age when birth date changes (only if age field is empty)
        document.getElementById('birth_date').addEventListener('change', function() {
            const ageField = document.getElementById('age');
            if (!ageField.value || ageField.value === '') {
                const age = calculateAge(this.value);
                ageField.value = age;
                
                // Visual feedback
                ageField.classList.add('age-calculated');
                setTimeout(() => {
                    ageField.classList.remove('age-calculated');
                }, 500);
            }
        });

        // Manual age calculation button
        document.getElementById('calculateAgeBtn').addEventListener('click', function() {
            const birthDateField = document.getElementById('birth_date');
            const ageField = document.getElementById('age');
            
            if (birthDateField.value) {
                const age = calculateAge(birthDateField.value);
                ageField.value = age;
                
                // Visual feedback
                ageField.classList.add('age-calculated');
                setTimeout(() => {
                    ageField.classList.remove('age-calculated');
                }, 500);
            } else {
                alert('Please select a birth date first');
            }
        });

        // Auto-capitalization for name fields
        function autoCapitalize(input) {
            if (input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        }

        // Calculate age on page load if birth date is already present
        document.addEventListener('DOMContentLoaded', function() {
            const birthDateField = document.getElementById('birth_date');
            const ageField = document.getElementById('age');
            
            if (birthDateField.value && (!ageField.value || ageField.value === '')) {
                const age = calculateAge(birthDateField.value);
                ageField.value = age;
            }
            
            // Apply auto-capitalization to name fields
            autoCapitalize(document.getElementById('last_name'));
            autoCapitalize(document.getElementById('first_name'));
            autoCapitalize(document.getElementById('middle_name'));
        });
    </script>
</body>
</html>
