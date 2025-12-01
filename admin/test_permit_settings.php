<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';
require_once '../includes/courses.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$coursesManager = new Courses();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        showAlert('Security check failed. Please try again.', 'error');
        redirect('test_permit_settings.php');
    }

    $formType = $_POST['form_type'] ?? 'settings';

    if ($formType === 'settings') {
        $settings = [
            'exam_duration' => (int)$_POST['exam_duration'],
            'morning_start_time' => sanitizeInput($_POST['morning_start_time']),
            'morning_end_time' => sanitizeInput($_POST['morning_end_time']),
            'afternoon_start_time' => sanitizeInput($_POST['afternoon_start_time']),
            'afternoon_end_time' => sanitizeInput($_POST['afternoon_end_time']),
            'lunch_start_time' => sanitizeInput($_POST['lunch_start_time']),
            'lunch_end_time' => sanitizeInput($_POST['lunch_end_time']),
            'advance_booking_days' => (int)$_POST['advance_booking_days'],
            'max_permits_per_day' => (int)$_POST['max_permits_per_day'],
            'auto_approve' => isset($_POST['auto_approve']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'registration_open' => isset($_POST['registration_open']) ? 1 : 0,
            'exam_room' => sanitizeInput($_POST['exam_room']),
            'exam_instructions' => sanitizeInput($_POST['exam_instructions'])
        ];
        
        // Save settings to database (you can create a settings table)
        try {
            $db = getDB();
            
            // Create settings table if it doesn't exist
            $db->exec("
                CREATE TABLE IF NOT EXISTS test_permit_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    setting_key VARCHAR(100) UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            // Save each setting
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO test_permit_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }
            
            showAlert('Test permit settings updated successfully!', 'success');
        } catch (Exception $e) {
            showAlert('Failed to update settings: ' . $e->getMessage(), 'error');
        }
    } elseif ($formType === 'add_course') {
        $courseName = sanitizeInput($_POST['course_name'] ?? '');
        $courseCategory = sanitizeInput($_POST['course_category'] ?? '');

        $result = $coursesManager->addCourse($courseName, $courseCategory ?: null);
        showAlert($result['message'], $result['success'] ? 'success' : 'error');
    } elseif ($formType === 'delete_course') {
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $result = $coursesManager->deleteCourse($courseId);
        showAlert($result['message'], $result['success'] ? 'success' : 'error');
    }
}

// Load current settings (ensure table exists first)
$db = getDB();
// Create settings table if it doesn't exist (for first-time load / fresh DB)
$db->exec("CREATE TABLE IF NOT EXISTS test_permit_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$stmt = $db->prepare("SELECT setting_key, setting_value FROM test_permit_settings");
$stmt->execute();
$currentSettings = [];
while ($row = $stmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Load courses for admin management section
$allCourses = $coursesManager->getAllCourses();

// Default values
$defaults = [
    'exam_duration' => 150, // 2.5 hours in minutes
    'morning_start_time' => '08:30',
    'morning_end_time' => '11:00',
    'afternoon_start_time' => '13:00',
    'afternoon_end_time' => '15:30',
    'lunch_start_time' => '12:00',
    'lunch_end_time' => '13:00',
    'advance_booking_days' => 3,
    'max_permits_per_day' => 50,
    'auto_approve' => 0,
    'email_notifications' => 1,
    'registration_open' => 1,
    'exam_room' => 'QSU Student Center - Testing room',
    'exam_instructions' => 'Bring valid ID, pen, pencil. No electronic devices allowed.'
];

$settings = array_merge($defaults, $currentSettings);
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Permit Settings - <?php echo SITE_NAME; ?></title>
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
                        <li><a class="dropdown-item" href="add_student.php">
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
                <a class="nav-link active" href="test_permit_settings.php">
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
                        <i class="fas fa-cog me-2 text-primary"></i>
                        Test Permit Settings
                    </h2>
                    <p class="text-muted mb-0">Configure test permit system settings and preferences.</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="form_type" value="settings">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
            <div class="row">
                <!-- Exam Schedule Settings -->
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-clock me-2"></i>Exam Schedule Settings
                        </h5>
                        
                        <div class="mb-3">
                            <label for="exam_duration" class="form-label">Exam Duration (minutes)</label>
                            <input type="number" class="form-control" id="exam_duration" name="exam_duration" 
                                   value="<?php echo $settings['exam_duration']; ?>" min="60" max="300">
                            <div class="form-text">Duration of each exam session in minutes (default: 150 = 2.5 hours)</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="morning_start_time" class="form-label">Morning Start Time</label>
                                    <input type="time" class="form-control" id="morning_start_time" name="morning_start_time" 
                                           value="<?php echo $settings['morning_start_time']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="morning_end_time" class="form-label">Morning End Time</label>
                                    <input type="time" class="form-control" id="morning_end_time" name="morning_end_time" 
                                           value="<?php echo $settings['morning_end_time']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="afternoon_start_time" class="form-label">Afternoon Start Time</label>
                                    <input type="time" class="form-control" id="afternoon_start_time" name="afternoon_start_time" 
                                           value="<?php echo $settings['afternoon_start_time']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="afternoon_end_time" class="form-label">Afternoon End Time</label>
                                    <input type="time" class="form-control" id="afternoon_end_time" name="afternoon_end_time" 
                                           value="<?php echo $settings['afternoon_end_time']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lunch_start_time" class="form-label">Lunch Start Time</label>
                                    <input type="time" class="form-control" id="lunch_start_time" name="lunch_start_time" 
                                           value="<?php echo $settings['lunch_start_time']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lunch_end_time" class="form-label">Lunch End Time</label>
                                    <input type="time" class="form-control" id="lunch_end_time" name="lunch_end_time" 
                                           value="<?php echo $settings['lunch_end_time']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-cogs me-2"></i>System Settings
                        </h5>
                        
                        <div class="mb-3">
                            <label for="advance_booking_days" class="form-label">Advance Booking Days</label>
                            <input type="number" class="form-control" id="advance_booking_days" name="advance_booking_days" 
                                   value="<?php echo $settings['advance_booking_days']; ?>" min="1" max="30">
                            <div class="form-text">How many days in advance students can book exams</div>
                        </div>

                        <div class="mb-3">
                            <label for="max_permits_per_day" class="form-label">Max Permits Per Day</label>
                            <input type="number" class="form-control" id="max_permits_per_day" name="max_permits_per_day" 
                                   value="<?php echo $settings['max_permits_per_day']; ?>" min="1" max="200">
                            <div class="form-text">Maximum number of test permits that can be issued per day</div>
                        </div>

                        <div class="mb-3">
                            <label for="exam_room" class="form-label">Default Exam Room</label>
                            <input type="text" class="form-control" id="exam_room" name="exam_room" 
                                   value="<?php echo htmlspecialchars($settings['exam_room']); ?>">
                            <div class="form-text">Default room for all test permits</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_approve" name="auto_approve" 
                                       <?php echo $settings['auto_approve'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_approve">
                                    Auto-approve test permits
                                </label>
                                <div class="form-text">Automatically approve test permit requests without manual review</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                       <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Enable email notifications
                                </label>
                                <div class="form-text">Send email notifications for status changes</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" 
                                       <?php echo !empty($settings['registration_open']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="registration_open">
                                    Allow new student registration
                                </label>
                                <div class="form-text">When turned off, students will see a message that QSU admission is closed and cannot create new accounts.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exam Instructions -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-list-alt me-2"></i>Exam Instructions
                        </h5>
                        
                        <div class="mb-3">
                            <label for="exam_instructions" class="form-label">Default Exam Instructions</label>
                            <textarea class="form-control" id="exam_instructions" name="exam_instructions" rows="6"><?php echo htmlspecialchars($settings['exam_instructions']); ?></textarea>
                            <div class="form-text">Default instructions that will appear on all test permits</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="resetToDefaults()">
                                <i class="fas fa-undo me-2"></i>Reset to Defaults
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Course Management -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>Course Management
                        </h5>
                        <span class="text-muted small">Manage the list of courses that students can choose on their forms.</span>
                    </div>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <form method="POST" class="border rounded p-3 bg-light">
                                <input type="hidden" name="form_type" value="add_course">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                                <div class="mb-3">
                                    <label for="course_name" class="form-label">Course Name</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" required>
                                    <div class="form-text">Example: Bachelor of Science in Information Technology (BSIT)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="course_category" class="form-label">Category (optional)</label>
                                    <select class="form-select" id="course_category" name="course_category">
                                        <option value="">Select category...</option>
                                        <option value="Board">Board Course</option>
                                        <option value="Non-board">Non-board Course</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Course
                                </button>
                            </form>
                        </div>

                        <div class="col-md-7 mb-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Course Name</th>
                                            <th style="width: 120px;">Category</th>
                                            <th style="width: 110px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($allCourses)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <i class="fas fa-info-circle me-1"></i>No courses found. Add a course using the form on the left.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($allCourses as $index => $course): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($course['name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['category'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <form method="POST" onsubmit="return confirm('Remove this course from the list?');" class="d-inline">
                                                    <input type="hidden" name="form_type" value="delete_course">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash me-1"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetToDefaults() {
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                document.getElementById('exam_duration').value = 150;
                document.getElementById('morning_start_time').value = '08:30';
                document.getElementById('morning_end_time').value = '11:00';
                document.getElementById('afternoon_start_time').value = '13:00';
                document.getElementById('afternoon_end_time').value = '15:30';
                document.getElementById('lunch_start_time').value = '12:00';
                document.getElementById('lunch_end_time').value = '13:00';
                document.getElementById('advance_booking_days').value = 3;
                document.getElementById('max_permits_per_day').value = 50;
                document.getElementById('exam_room').value = 'QSU Student Center - Testing room';
                document.getElementById('auto_approve').checked = false;
                document.getElementById('email_notifications').checked = true;
                document.getElementById('exam_instructions').value = 'Bring valid ID, pen, pencil. No electronic devices allowed.';
            }
        }

        // Auto-calculate end times based on duration
        document.getElementById('exam_duration').addEventListener('change', function() {
            const duration = parseInt(this.value);
            const morningStart = document.getElementById('morning_start_time').value;
            const afternoonStart = document.getElementById('afternoon_start_time').value;
            
            if (morningStart) {
                const startTime = new Date('2000-01-01 ' + morningStart);
                const endTime = new Date(startTime.getTime() + duration * 60000);
                document.getElementById('morning_end_time').value = endTime.toTimeString().slice(0, 5);
            }
            
            if (afternoonStart) {
                const startTime = new Date('2000-01-01 ' + afternoonStart);
                const endTime = new Date(startTime.getTime() + duration * 60000);
                document.getElementById('afternoon_end_time').value = endTime.toTimeString().slice(0, 5);
            }
        });
    </script>
</body>
</html>
