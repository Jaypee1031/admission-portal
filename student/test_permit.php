<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/admission_form.php';
require_once '../includes/test_permit.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();
$studentId = $user['id'];

// Check workflow sequence
$progress = $auth->getStudentProgress($studentId);
if (!$progress['requirements']) {
    showAlert('Please upload and get approval for all required documents first', 'error');
    redirect('requirements.php');
}

if (!$progress['admission_form']) {
    showAlert('Please complete your admission form first before requesting a test permit.', 'error');
    redirect('admission_form.php');
}

// Initialize classes
$admissionForm = new AdmissionForm();
$testPermit = new TestPermit();

// Get existing test permit data
$formData = $admissionForm->getAdmissionForm($studentId);

// Get existing test permit data
$permitData = $testPermit->getTestPermit($studentId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'exam_date' => sanitizeInput($_POST['exam_date']),
        'exam_time' => sanitizeInput($_POST['exam_time']),
        'exam_room' => sanitizeInput($_POST['exam_room']),
        'remarks' => sanitizeInput($_POST['remarks'])
    ];
    
    // Validate required fields
    $errors = [];
    if (empty($formData['exam_date'])) $errors[] = 'Exam date is required';
    if (empty($formData['exam_time'])) $errors[] = 'Exam time is required';
    if (empty($formData['exam_room'])) $errors[] = 'Exam room is required';
    
    if (empty($errors)) {
        $result = $testPermit->saveTestPermit($studentId, $formData);
        if ($result['success']) {
            showAlert('Test permit requested successfully! Your request has been submitted and is pending admin approval.', 'success');
            // Use an absolute route so buildUrl() generates the correct URL under the Admission Portal
            // This avoids redirecting to /Admission Portal/test_permit.php (which 404s) and instead
            // sends the user back to /Admission Portal/student/test_permit.php
            redirect('/student/test_permit.php');
        } else {
            showAlert($result['message'], 'error');
        }
    } else {
        showAlert('Please fill in all required fields: ' . implode(', ', $errors), 'error');
    }
}

// Get alert message
$alert = getAlert();

// Available exam dates (weekdays only with 2-day intervals)
$examDates = [];
$startDate = date('Y-m-d', strtotime('+3 days')); // Start from 3 days from today (skip 2 days)
$currentDate = strtotime($startDate);

// Generate dates for the next 30 days with 2-day intervals, weekdays only
$dateCount = 0;
while ($dateCount < 15) { // Limit to 15 available dates
    $date = date('Y-m-d', $currentDate);
    $dayOfWeek = date('N', $currentDate); // 1 = Monday, 7 = Sunday
    
    // Only include weekdays (Monday to Friday)
    if ($dayOfWeek <= 5) {
        $examDates[] = $date;
        $dateCount++;
        
        // Skip 2 days after each weekday selection
        $currentDate = strtotime('+3 days', $currentDate);
    } else {
        // If it's weekend, move to next weekday
        $currentDate = strtotime('+1 day', $currentDate);
    }
}

// Available exam times
$examTimes = [
    '08:30' => '8:30 AM - 11:00 AM',
    '13:00' => '1:00 PM - 3:30 PM'
];

// Available exam rooms
$examRooms = [
    'qsu_student_center' => 'QSU Student Center - Testing room'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Test Permit - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-id-card me-2 text-primary"></i>
                        Request Test Permit (F4)
                    </h2>
                    <p class="text-muted mb-0">Request to schedule your entrance examination.</p>
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

        <!-- Test Permit Status -->
        <div class="alert alert-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1">
                        <i class="fas fa-info-circle me-2"></i>
                        Test Permit Status
                    </h5>
                    <?php if ($permitData): ?>
                        <p class="mb-0">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $permitData['status'] === 'Approved' ? 'success' : ($permitData['status'] === 'Pending' ? 'warning' : 'secondary'); ?>">
                                <?php echo $permitData['status']; ?>
                            </span>
                            <?php if ($permitData['status'] === 'Approved'): ?>
                                <br><small>Exam Date: <?php echo date('M d, Y', strtotime($permitData['exam_date'])); ?> at <?php echo $permitData['exam_time']; ?></small>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-0">No test permit request submitted yet.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (!$permitData || $permitData['status'] === 'Rejected'): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testPermitModal">
                            <i class="fas fa-plus me-2"></i>Request Test Permit
                        </button>
                    <?php elseif ($permitData['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-warning" disabled>
                            <i class="fas fa-clock me-2"></i>Request Pending
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-success" disabled>
                            <i class="fas fa-check me-2"></i>Permit Approved
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Test Permit Request Modal -->
        <div class="modal fade" id="testPermitModal" tabindex="-1" aria-labelledby="testPermitModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="testPermitModalLabel">
                            <i class="fas fa-file-alt me-2"></i>Request Test Permit
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="" id="testPermitForm">
                        <div class="modal-body">
                            <!-- Student Information Display -->
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Student Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <strong>Name:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($formData['first_name'] . ' ' . $formData['last_name']); ?></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <strong>Email:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <strong>1st Course Choice:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($formData['course_first'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong>2nd Course Choice:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($formData['course_second'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong>3rd Course Choice:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($formData['course_third'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Exam Details -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i>Examination Details
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="modal_exam_date" class="form-label">Exam Date *</label>
                                            <select class="form-select" id="modal_exam_date" name="exam_date" required>
                                                <option value="">Select Date</option>
                                                <?php foreach ($examDates as $date): ?>
                                                <option value="<?php echo $date; ?>">
                                                    <?php echo date('M d, Y (l)', strtotime($date)); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="modal_exam_time" class="form-label">Exam Time *</label>
                                            <select class="form-select" id="modal_exam_time" name="exam_time" required>
                                                <option value="">Select Time</option>
                                                <?php foreach ($examTimes as $value => $label): ?>
                                                <option value="<?php echo $value; ?>">
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="modal_exam_room" class="form-label">Exam Room *</label>
                                            <select class="form-select" id="modal_exam_room" name="exam_room" required>
                                                <?php foreach ($examRooms as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" selected>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Capacity Information -->
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div id="capacityInfo" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Additional Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="modal_remarks" class="form-label">Remarks (Optional)</label>
                                        <textarea class="form-control" id="modal_remarks" name="remarks" rows="3" placeholder="Any additional information or special requirements..."></textarea>
                                    </div>
                                    
                                    <!-- Important Notice -->
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Important Notice
                                        </h6>
                                        <p class="mb-2">Please ensure all information is correct before submitting. Once approved, this will be your official test permit.</p>
                                        <small class="text-muted">
                                            <strong>Exam Schedule:</strong><br>
                                            • Morning Session: 8:30 AM - 11:00 AM (2.5 hours)<br>
                                            • Afternoon Session: 1:00 PM - 3:30 PM (2.5 hours)<br>
                                            • Lunch Break: 12:00 PM - 1:00 PM<br><br>
                                            <strong>What to bring on exam day:</strong><br>
                                            • Pen<br>
                                            • Test permit (printed)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitPermitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <a href="documents.php" class="btn btn-outline-primary">
                        <i class="fas fa-file-alt me-2"></i>View Documents
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Capacity checking functionality
        let capacityInfo = {};
        
        // Check capacity for selected slot
        function checkCapacity() {
            const examDate = document.getElementById('modal_exam_date').value;
            const examTime = document.getElementById('modal_exam_time').value;
            const examRoom = document.getElementById('modal_exam_room').value;
            
            if (examDate && examTime && examRoom) {
                // Show loading
                const capacityDiv = document.getElementById('capacityInfo');
                capacityDiv.innerHTML = '<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Checking availability...</small>';
                
                // Make AJAX request to check capacity
                fetch('check_capacity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `exam_date=${examDate}&exam_time=${examTime}&exam_room=${examRoom}`
                })
                .then(response => response.json())
                .then(data => {
                    capacityInfo = data;
                    updateCapacityDisplay(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    capacityDiv.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Unable to check availability</small>';
                });
            } else {
                document.getElementById('capacityInfo').innerHTML = '';
            }
        }
        
        // Update capacity display
        function updateCapacityDisplay(data) {
            const capacityDiv = document.getElementById('capacityInfo');
            const submitBtn = document.getElementById('submitPermitBtn');
            
            if (data.available) {
                const remaining = data.remaining_slots;
                const total = data.max_capacity;
                const current = data.current_bookings;
                
                if (remaining <= 5) {
                    capacityDiv.innerHTML = `<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Only ${remaining} spots remaining (${current}/${total})</small>`;
                } else {
                    capacityDiv.innerHTML = `<small class="text-success"><i class="fas fa-check-circle"></i> ${remaining} spots available (${current}/${total})</small>`;
                }
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-primary');
            } else {
                capacityDiv.innerHTML = `<small class="text-danger"><i class="fas fa-times-circle"></i> ${data.message}</small>`;
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-secondary');
            }
        }
        
        // Event listeners for capacity checking
        document.getElementById('modal_exam_date').addEventListener('change', checkCapacity);
        document.getElementById('modal_exam_time').addEventListener('change', checkCapacity);
        document.getElementById('modal_exam_room').addEventListener('change', checkCapacity);
        
        // Form validation
        document.getElementById('testPermitForm').addEventListener('submit', function(e) {
            const examDate = document.getElementById('modal_exam_date').value;
            const examTime = document.getElementById('modal_exam_time').value;
            const examRoom = document.getElementById('modal_exam_room').value;
            
            if (!examDate || !examTime || !examRoom) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Check if slot is still available
            if (capacityInfo && !capacityInfo.available) {
                e.preventDefault();
                alert(capacityInfo.message);
                return false;
            }
        });

        // Auto-open modal if there's an error
        <?php if ($alert && $alert['type'] === 'error'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('testPermitModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
