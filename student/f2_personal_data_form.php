<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';
require_once '../includes/favicon.php';

// Delegate to MVC controller (new implementation)
$controller = new F2PersonalDataController();
$controller->index();
exit;

// Legacy implementation (kept for rollback; unreachable after exit above)
// Redirect if not logged in as student
if (!isStudent()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();

// Check if Personal Data form is enabled for this student
$progress = $auth->getStudentProgress($user['id']);
if (!$progress['f2_form_enabled']) {
    showAlert('Personal Data Form is not available for your account. Please contact the administrator.', 'error');
    redirect('dashboard.php');
}

$f2Form = new F2PersonalDataForm();

// Get student's full name and GWA from database
$db = getDB();
$stmt = $db->prepare("SELECT first_name, last_name, overall_gwa FROM students WHERE id = ?");
$stmt->execute([$user['id']]);
$studentData = $stmt->fetch();
$studentFullName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
$studentGWA = $studentData['overall_gwa'] ?? '';

// Get GWA from F2 personal data form if available
$stmt = $db->prepare("SELECT general_average FROM f2_personal_data_forms WHERE student_id = ?");
$stmt->execute([$user['id']]);
$f2Data = $stmt->fetch();
$f2GWA = $f2Data['general_average'] ?? '';

// Personal Data form access is already checked above using progress data

// Get existing form data
$existingData = $f2Form->getF2FormData($user['id']);
$isCompleted = $f2Form->isF2FormCompleted($user['id']);

// Get admission form data to pre-fill Personal Data form
$db = getDB();
$stmt = $db->prepare("SELECT * FROM admission_forms WHERE student_id = ?");
$stmt->execute([$user['id']]);
$admissionFormData = $stmt->fetch();

$alert = getAlert();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Calculate age from birth date
    $age = null;
    if (!empty($_POST['date_of_birth'])) {
        $birthDate = new DateTime($_POST['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    }
    
    $formData = [
        'personal_info' => [
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
            'civil_status' => sanitizeInput($_POST['civil_status'] ?? ''),
            'spouse_name' => sanitizeInput($_POST['spouse_name'] ?? ''),
            'course_year_level' => sanitizeInput($_POST['course_year_level'] ?? ''),
            'sex' => sanitizeInput($_POST['sex'] ?? ''),
            'ethnicity' => sanitizeInput($_POST['ethnicity'] ?? ''),
            'ethnicity_others_specify' => sanitizeInput($_POST['ethnicity_others_specify'] ?? ''),
            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
            'age' => $age,
            'place_of_birth' => sanitizeInput($_POST['place_of_birth'] ?? ''),
            'religion' => sanitizeInput($_POST['religion'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'contact_number' => sanitizeInput($_POST['contact_number'] ?? '')
        ],
        'family_info' => (new FamilyInfoHandler())->buildFamilyInfo($_POST),
        'education' => [
            'elementary_school' => sanitizeInput($_POST['elementary_school'] ?? ''),
            'secondary_school' => sanitizeInput($_POST['secondary_school'] ?? ''),
            'school_university_last_attended' => sanitizeInput($_POST['school_university_last_attended'] ?? ''),
            'school_name' => sanitizeInput($_POST['school_name'] ?? ''),
            'school_address' => sanitizeInput($_POST['school_address'] ?? ''),
            'general_average' => sanitizeInput($_POST['general_average'] ?? ''),
            'course_first_choice' => sanitizeInput($_POST['course_first_choice'] ?? ''),
            'course_second_choice' => sanitizeInput($_POST['course_second_choice'] ?? ''),
            'course_third_choice' => sanitizeInput($_POST['course_third_choice'] ?? ''),
            'parents_choice' => sanitizeInput($_POST['parents_choice'] ?? ''),
            'nature_of_schooling_continuous' => sanitizeInput($_POST['nature_of_schooling_continuous'] ?? ''),
            'reason_if_interrupted' => sanitizeInput($_POST['reason_if_interrupted'] ?? '')
        ],
        'skills' => [
            'talents' => sanitizeInput($_POST['talents'] ?? ''),
            'awards' => sanitizeInput($_POST['awards'] ?? ''),
            'hobbies' => sanitizeInput($_POST['hobbies'] ?? '')
        ],
        'health_record' => [
            'disability_specify' => sanitizeInput($_POST['disability_specify'] ?? ''),
            'confined_rehabilitated' => sanitizeInput($_POST['confined_rehabilitated'] ?? ''),
            'confined_when' => sanitizeInput($_POST['confined_when'] ?? ''),
            'treated_for_illness' => sanitizeInput($_POST['treated_for_illness'] ?? ''),
            'treated_when' => sanitizeInput($_POST['treated_when'] ?? '')
        ],
        'declaration' => [
            'signature_over_printed_name' => sanitizeInput($_POST['signature_over_printed_name'] ?? ''),
            'date_accomplished' => sanitizeInput($_POST['date_accomplished'] ?? date('Y-m-d'))
        ]
    ];
    
    $siblingManager = new SiblingManager($f2Form);
    $siblingsResult = $siblingManager->saveSiblingsFromPost($user['id'], $_POST['siblings'] ?? array());
    
    $result = $f2Form->saveF2FormData($user['id'], $formData);
    
    if ($result['success'] && $siblingsResult['success']) {
        showAlert('Personal Data form saved successfully!', 'success');
        redirect('f2_personal_data_form.php');
    } else {
        showAlert($result['message'], 'error');
    }
}

// Pre-fill form with existing data or admission form data
$autoFill = new AutoFillEngine();
$formData = $autoFill->buildInitialFormData(
    $existingData ?: array(),
    $admissionFormData ?: array(),
    $user,
    $studentFullName,
    $studentGWA,
    $f2GWA
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Data Form - <?php echo SITE_NAME; ?></title>
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
                    <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-file-alt me-2 text-primary"></i>
                                Personal Data Form
                            </h2>
                            <p class="text-muted mb-0">Please fill out all required information accurately. Use "N/A" for fields that are not applicable to you.</p>
                        </div>
                        <?php if ($isCompleted): ?>
                        <div class="text-end">
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check me-1"></i>Completed
                            </span>
                            <br>
                            <small class="text-muted">
                                Submitted: <?php echo date('M d, Y H:i', strtotime($existingData['submitted_at'])); ?>
                            </small>
                            <br>
                            <a href="view_f2_pdf.php" class="btn btn-outline-primary btn-sm mt-2" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i>View PDF
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($alert['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$existingData && $admissionFormData): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Form Pre-filled:</strong> This form has been automatically filled with data from your admission form. Please review and complete any missing information.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <!-- Personal Information -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-user me-2 text-primary"></i>
                            Personal Information
                        </h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['last_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['first_name'] ?? ''); ?>" 
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control auto-caps" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['middle_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="civil_status" class="form-label">Civil Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="civil_status" name="civil_status" required>
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php echo ($formData['personal_info']['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($formData['personal_info']['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo ($formData['personal_info']['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Divorced" <?php echo ($formData['personal_info']['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Separated" <?php echo ($formData['personal_info']['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="spouse_name" class="form-label">Name of Spouse (if married) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="spouse_name" name="spouse_name" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['spouse_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="course_year_level" class="form-label">Course / Year Level <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="course_year_level" name="course_year_level" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['course_year_level'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sex" class="form-label">Sex <span class="text-danger">*</span></label>
                                <select class="form-select" id="sex" name="sex" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php echo ($formData['personal_info']['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($formData['personal_info']['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ethnicity" class="form-label">Ethnicity <span class="text-danger">*</span></label>
                                <select class="form-select" id="ethnicity" name="ethnicity" required onchange="toggleEthnicityOther()">
                                    <option value="">Select Ethnicity</option>
                                    <option value="Ilocano" <?php echo ($formData['personal_info']['ethnicity'] ?? '') === 'Ilocano' ? 'selected' : ''; ?>>Ilocano</option>
                                    <option value="Tagalog" <?php echo ($formData['personal_info']['ethnicity'] ?? '') === 'Tagalog' ? 'selected' : ''; ?>>Tagalog</option>
                                    <option value="Ifugao" <?php echo ($formData['personal_info']['ethnicity'] ?? '') === 'Ifugao' ? 'selected' : ''; ?>>Ifugao</option>
                                    <option value="Others" <?php echo ($formData['personal_info']['ethnicity'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="ethnicity_others_div" style="display: none;">
                                <label for="ethnicity_others_specify" class="form-label">Others: specify</label>
                                <input type="text" class="form-control" id="ethnicity_others_specify" name="ethnicity_others_specify" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['ethnicity_others_specify'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['date_of_birth'] ?? ''); ?>" required onchange="calculateAge()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Age (auto-computed)</label>
                                <input type="number" class="form-control" id="age" name="age" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['age'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="place_of_birth" class="form-label">Place of Birth <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="place_of_birth" name="place_of_birth" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['place_of_birth'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="religion" class="form-label">Religion</label>
                                <input type="text" class="form-control" id="religion" name="religion" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['religion'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($formData['personal_info']['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label">Contact No. <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($formData['personal_info']['contact_number'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Family Information -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Family Information
                        </h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="father_name" class="form-label">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="father_name" name="father_name" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['father_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="father_occupation" class="form-label">Father's Occupation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="father_occupation" name="father_occupation" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['father_occupation'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="father_ethnicity" class="form-label">Father's Ethnicity <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="father_ethnicity" name="father_ethnicity" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['father_ethnicity'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mother_name" class="form-label">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="mother_name" name="mother_name" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['mother_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mother_occupation" class="form-label">Mother's Occupation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="mother_occupation" name="mother_occupation" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['mother_occupation'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mother_ethnicity" class="form-label">Mother's Ethnicity <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="mother_ethnicity" name="mother_ethnicity" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['mother_ethnicity'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="parents_living_together" class="form-label">Parents' Status: Living together <span class="text-danger">*</span></label>
                                <select class="form-select" id="parents_living_together" name="parents_living_together" required>
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo ($formData['family_info']['parents_living_together'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo ($formData['family_info']['parents_living_together'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="parents_separated" class="form-label">Separated</label>
                                <select class="form-select" id="parents_separated" name="parents_separated">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo ($formData['family_info']['parents_separated'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo ($formData['family_info']['parents_separated'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="separation_reason" class="form-label">Reason (Work / Conflict)</label>
                                <input type="text" class="form-control" id="separation_reason" name="separation_reason" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['separation_reason'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="living_with" class="form-label">If separated, living with?</label>
                                <select class="form-select" id="living_with" name="living_with">
                                    <option value="">Select</option>
                                    <option value="Mother" <?php echo ($formData['family_info']['living_with'] ?? '') === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                    <option value="Father" <?php echo ($formData['family_info']['living_with'] ?? '') === 'Father' ? 'selected' : ''; ?>>Father</option>
                                    <option value="Relatives" <?php echo ($formData['family_info']['living_with'] ?? '') === 'Relatives' ? 'selected' : ''; ?>>Relatives</option>
                                    <option value="Others" <?php echo ($formData['family_info']['living_with'] ?? '') === 'Others' ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age_when_separated" class="form-label">Age when parents separated</label>
                                <input type="number" class="form-control" id="age_when_separated" name="age_when_separated" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['age_when_separated'] ?? ''); ?>" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guardian_name" class="form-label">Guardian's Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control auto-caps" id="guardian_name" name="guardian_name" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['guardian_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guardian_relationship" class="form-label">Guardian's Relationship <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="guardian_relationship" name="guardian_relationship" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['guardian_relationship'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guardian_contact_number" class="form-label">Guardian's Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="guardian_contact_number" name="guardian_contact_number" 
                                       value="<?php echo htmlspecialchars($formData['family_info']['guardian_contact_number'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="guardian_address" class="form-label">Guardian's Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="guardian_address" name="guardian_address" rows="2" required><?php echo htmlspecialchars($formData['family_info']['guardian_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Name of Siblings (Mga kapatid, pangalan hanggang pinaka-bata) Birth Order (Pang ilan ka?) <span class="text-danger">*</span></label>
                                <small class="text-muted d-block mb-2">List siblings from oldest to youngest. Birth order will be automatically assigned.</small>
                                <div id="siblings-container">
                                    <?php 
                                    $siblings = $f2Form->getSiblings($user['id']);
                                    if (empty($siblings)) {
                                        $siblings = [['sibling_name' => '', 'birth_order' => 1]];
                                    } else {
                                        // Ensure birth orders are sequential starting from 1
                                        foreach ($siblings as $index => &$sibling) {
                                            $sibling['birth_order'] = $index + 1;
                                        }
                                    }
                                    foreach ($siblings as $index => $sibling): 
                                    ?>
                                    <div class="sibling-entry mb-2">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <input type="text" class="form-control auto-caps" name="siblings[<?php echo $index; ?>][name]" 
                                                       value="<?php echo htmlspecialchars($sibling['sibling_name']); ?>" 
                                                       placeholder="Pangalan ng kapatid" style="text-transform: uppercase;">
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="siblings[<?php echo $index; ?>][order]">
                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($sibling['birth_order'] ?? 1) == $i ? 'selected' : ''; ?>>
                                                        <?php echo $i . ($i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th'))); ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-sibling" <?php echo count($siblings) == 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-sibling">
                                    <i class="fas fa-plus me-1"></i>Add Sibling (Magdagdag ng Kapatid)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Educational / Vocational Information -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>
                            Educational / Vocational Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="elementary_school" class="form-label">Elementary (Name of School)</label>
                                <input type="text" class="form-control auto-caps" id="elementary_school" name="elementary_school" 
                                       value="<?php echo htmlspecialchars($formData['education']['elementary_school'] ?? ''); ?>"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="secondary_school" class="form-label">Secondary (Name of School)</label>
                                <input type="text" class="form-control auto-caps" id="secondary_school" name="secondary_school" 
                                       value="<?php echo htmlspecialchars($formData['education']['secondary_school'] ?? ''); ?>"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-12 mb-3">
                                <h6 class="text-primary">For Transferees:</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="school_university_last_attended" class="form-label">School/University Last Attended</label>
                                <input type="text" class="form-control auto-caps" id="school_university_last_attended" name="school_university_last_attended" 
                                       value="<?php echo htmlspecialchars($formData['education']['school_university_last_attended'] ?? ''); ?>"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="school_name" class="form-label">School's Name</label>
                                <input type="text" class="form-control auto-caps" id="school_name" name="school_name" 
                                       value="<?php echo htmlspecialchars($formData['education']['school_name'] ?? ''); ?>"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="school_address" class="form-label">School's Address</label>
                                <textarea class="form-control" id="school_address" name="school_address" rows="2"><?php echo htmlspecialchars($formData['education']['school_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="general_average" class="form-label">Overall GWA <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="general_average" name="general_average" 
                                       value="<?php echo htmlspecialchars($formData['education']['general_average'] ?? $f2GWA ?: $studentGWA); ?>" 
                                       placeholder="e.g., 85.50" step="0.01" min="0" max="100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nature_of_schooling_continuous" class="form-label">Nature of Schooling (Continuous?)</label>
                                <select class="form-select" id="nature_of_schooling_continuous" name="nature_of_schooling_continuous">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo ($formData['education']['nature_of_schooling_continuous'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo ($formData['education']['nature_of_schooling_continuous'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="reason_if_interrupted" class="form-label">Reason if interrupted</label>
                                <textarea class="form-control" id="reason_if_interrupted" name="reason_if_interrupted" rows="2"><?php echo htmlspecialchars($formData['education']['reason_if_interrupted'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_first_choice" class="form-label">Course Intended to Take (1st Choice)</label>
                                <input type="text" class="form-control" id="course_first_choice" name="course_first_choice" 
                                       value="<?php echo htmlspecialchars($formData['education']['course_first_choice'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_second_choice" class="form-label">Course Intended to Take (2nd Choice)</label>
                                <input type="text" class="form-control" id="course_second_choice" name="course_second_choice" 
                                       value="<?php echo htmlspecialchars($formData['education']['course_second_choice'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course_third_choice" class="form-label">Course Intended to Take (3rd Choice)</label>
                                <input type="text" class="form-control" id="course_third_choice" name="course_third_choice" 
                                       value="<?php echo htmlspecialchars($formData['education']['course_third_choice'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="parents_choice" class="form-label">Parent's Choice</label>
                                <input type="text" class="form-control" id="parents_choice" name="parents_choice" 
                                       value="<?php echo htmlspecialchars($formData['education']['parents_choice'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills Information -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-star me-2 text-primary"></i>
                            Skills Information
                        </h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="talents" class="form-label">Talent/s</label>
                                <textarea class="form-control" id="talents" name="talents" rows="3"><?php echo htmlspecialchars($formData['skills']['talents'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="awards" class="form-label">Awards</label>
                                <textarea class="form-control" id="awards" name="awards" rows="3"><?php echo htmlspecialchars($formData['skills']['awards'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="hobbies" class="form-label">Hobbies</label>
                                <textarea class="form-control" id="hobbies" name="hobbies" rows="3"><?php echo htmlspecialchars($formData['skills']['hobbies'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Record -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-heartbeat me-2 text-primary"></i>
                            Health Record
                        </h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="disability_specify" class="form-label">Disability (specify)</label>
                                <input type="text" class="form-control" id="disability_specify" name="disability_specify" 
                                       value="<?php echo htmlspecialchars($formData['health_record']['disability_specify'] ?? ''); ?>" 
                                       placeholder="None or specify">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confined_rehabilitated" class="form-label">Confined/Rehabilitated in the past 3 years</label>
                                <select class="form-select" id="confined_rehabilitated" name="confined_rehabilitated">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo ($formData['health_record']['confined_rehabilitated'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo ($formData['health_record']['confined_rehabilitated'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confined_when" class="form-label">When?</label>
                                <input type="text" class="form-control" id="confined_when" name="confined_when" 
                                       value="<?php echo htmlspecialchars($formData['health_record']['confined_when'] ?? ''); ?>" 
                                       placeholder="e.g., 2022, 2023">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="treated_for_illness" class="form-label">Treated for illness</label>
                                <select class="form-select" id="treated_for_illness" name="treated_for_illness">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo ($formData['health_record']['treated_for_illness'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo ($formData['health_record']['treated_for_illness'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="treated_when" class="form-label">When?</label>
                                <input type="text" class="form-control" id="treated_when" name="treated_when" 
                                       value="<?php echo htmlspecialchars($formData['health_record']['treated_when'] ?? ''); ?>" 
                                       placeholder="e.g., 2022, 2023">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Declaration -->
                <div class="col-12 mb-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">
                            <i class="fas fa-signature me-2 text-primary"></i>
                            Declaration
                        </h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="signature_over_printed_name" class="form-label">Signature over printed name (can be digital/typed)</label>
                                <input type="text" class="form-control" id="signature_over_printed_name" name="signature_over_printed_name" 
                                       value="<?php echo htmlspecialchars($studentFullName); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_accomplished" class="form-label">Date accomplished</label>
                                <input type="date" class="form-control" id="date_accomplished" name="date_accomplished" 
                                       value="<?php echo htmlspecialchars($formData['declaration']['date_accomplished'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Ready to Submit?</h6>
                                <small class="text-muted">Please review all information before submitting.</small>
                            </div>
                            <div>
                                <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                                </a>
                                <?php if ($isCompleted): ?>
                                <a href="view_f2_pdf.php" class="btn btn-outline-info me-2" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i>View PDF
                                </a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    <?php echo $isCompleted ? 'Update Form' : 'Submit Form'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Calculate age from birth date
        function calculateAge() {
            const birthDate = document.getElementById('date_of_birth').value;
            if (birthDate) {
                const today = new Date();
                const birth = new Date(birthDate);
                let age = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                document.getElementById('age').value = age;
            }
        }

        // Toggle ethnicity others field
        function toggleEthnicityOther() {
            const ethnicity = document.getElementById('ethnicity').value;
            const othersDiv = document.getElementById('ethnicity_others_div');
            if (ethnicity === 'Others') {
                othersDiv.style.display = 'block';
            } else {
                othersDiv.style.display = 'none';
                document.getElementById('ethnicity_others_specify').value = '';
            }
        }

        // Auto-capitalization for name fields
        function autoCapitalize(input) {
            if (input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        }

        // Auto-fill N/A for non-applicable fields
        function autoFillNA() {
            const civilStatus = document.getElementById('civil_status').value;
            const spouseNameField = document.getElementById('spouse_name');
            const parentsLivingTogether = document.getElementById('parents_living_together').value;
            
            // Auto-fill spouse name with N/A if not married
            if (civilStatus && civilStatus !== 'Married') {
                spouseNameField.value = 'N/A';
            }
            
            // Auto-fill separation fields with N/A if parents are living together
            if (parentsLivingTogether === 'Yes') {
                document.getElementById('parents_separated').value = 'No';
                document.getElementById('separation_reason').value = 'N/A';
                document.getElementById('living_with').value = 'N/A';
                document.getElementById('age_when_separated').value = '';
            }
        }
        
        // Auto-fill guardian fields with N/A if living with parents
        function autoFillGuardianNA() {
            const livingWith = document.getElementById('living_with').value;
            const parentsLivingTogether = document.getElementById('parents_living_together').value;
            
            if (parentsLivingTogether === 'Yes' || livingWith === 'Both Parents') {
                document.getElementById('guardian_name').value = 'N/A';
                document.getElementById('guardian_relationship').value = 'N/A';
                document.getElementById('guardian_contact_number').value = 'N/A';
                document.getElementById('guardian_address').value = 'N/A';
            }
        }
        
        // Siblings management functions
        let siblingIndex = <?php echo count($siblings ?? []); ?>;
        
        function addSibling() {
            const container = document.getElementById('siblings-container');
            const siblingEntry = document.createElement('div');
            siblingEntry.className = 'sibling-entry mb-2';
            
            // Get the next birth order number
            const existingEntries = container.querySelectorAll('.sibling-entry');
            const nextOrder = existingEntries.length + 1;
            
            siblingEntry.innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" class="form-control auto-caps" name="siblings[${siblingIndex}][name]" 
                               placeholder="Pangalan ng kapatid" style="text-transform: uppercase;">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="siblings[${siblingIndex}][order]">
                            ${Array.from({length: 10}, (_, i) => {
                                const num = i + 1;
                                const suffix = num === 1 ? 'st' : num === 2 ? 'nd' : num === 3 ? 'rd' : 'th';
                                const selected = num === nextOrder ? 'selected' : '';
                                return `<option value="${num}" ${selected}>${num}${suffix}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-sibling">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(siblingEntry);
            siblingIndex++;
            
            // Enable remove buttons if more than one sibling
            updateRemoveButtons();
            
            // Apply auto-capitalization to new field
            autoCapitalize(siblingEntry.querySelector('input[type="text"]'));
        }
        
        function removeSibling(button) {
            const siblingEntry = button.closest('.sibling-entry');
            siblingEntry.remove();
            updateRemoveButtons();
            renumberBirthOrders();
        }
        
        function renumberBirthOrders() {
            const container = document.getElementById('siblings-container');
            const siblings = container.querySelectorAll('.sibling-entry');
            
            siblings.forEach((sibling, index) => {
                const select = sibling.querySelector('select[name*="[order]"]');
                if (select) {
                    const newOrder = index + 1;
                    select.value = newOrder;
                }
            });
        }
        
        function updateRemoveButtons() {
            const siblings = document.querySelectorAll('.sibling-entry');
            const removeButtons = document.querySelectorAll('.remove-sibling');
            
            removeButtons.forEach(button => {
                button.disabled = siblings.length <= 1;
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleEthnicityOther();
            
            // Add event listeners for auto-fill
            document.getElementById('civil_status').addEventListener('change', autoFillNA);
            document.getElementById('parents_living_together').addEventListener('change', autoFillNA);
            document.getElementById('living_with').addEventListener('change', autoFillGuardianNA);
            
            // Add event listeners for siblings management
            document.getElementById('add-sibling').addEventListener('click', addSibling);
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-sibling')) {
                    removeSibling(e.target.closest('.remove-sibling'));
                }
            });
            
            // Apply auto-capitalization to all name fields
            const nameFields = [
                'last_name', 'first_name', 'middle_name', 'spouse_name',
                'father_name', 'father_occupation', 'father_ethnicity',
                'mother_name', 'mother_occupation', 'mother_ethnicity',
                'guardian_name', 'elementary_school', 'secondary_school',
                'school_university_last_attended', 'school_name'
            ];
            
            nameFields.forEach(fieldId => {
                autoCapitalize(document.getElementById(fieldId));
            });
        });
    </script>
</body>
</html>
