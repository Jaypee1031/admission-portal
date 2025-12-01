<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/f2_personal_data_form.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

$studentId = (int)($_GET['student_id'] ?? 0);

if (!$studentId) {
    echo '<div class="alert alert-danger">Invalid student ID.</div>';
    exit;
}

$f2Form = new F2PersonalDataForm();
$formData = $f2Form->getF2FormData($studentId);

if (!$formData) {
    echo '<div class="alert alert-warning">No Personal Data form data found for this student.</div>';
    exit;
}

$data = $formData;
?>
<div class="f2-form-view">
    <div class="row">
        <!-- Personal Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Full Name:</strong> <?php echo htmlspecialchars(($data['personal_info']['first_name'] ?? '') . ' ' . ($data['personal_info']['middle_name'] ?? '') . ' ' . ($data['personal_info']['last_name'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Contact Number:</strong> <?php echo htmlspecialchars($data['personal_info']['contact_number'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($data['personal_info']['email'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Birth Date:</strong> <?php echo htmlspecialchars($data['personal_info']['date_of_birth'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Birth Place:</strong> <?php echo htmlspecialchars($data['personal_info']['place_of_birth'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Gender:</strong> <?php echo htmlspecialchars($data['personal_info']['sex'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Civil Status:</strong> <?php echo htmlspecialchars($data['personal_info']['civil_status'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Ethnicity:</strong> <?php echo htmlspecialchars($data['personal_info']['ethnicity'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($data['personal_info']['address'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Religion:</strong> <?php echo htmlspecialchars($data['personal_info']['religion'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Age:</strong> <?php echo htmlspecialchars($data['personal_info']['age'] ?? ''); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>Family Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>Father's Name:</strong> <?php echo htmlspecialchars($data['family_info']['father_name'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Father's Occupation:</strong> <?php echo htmlspecialchars($data['family_info']['father_occupation'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Father's Ethnicity:</strong> <?php echo htmlspecialchars($data['family_info']['father_ethnicity'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Mother's Name:</strong> <?php echo htmlspecialchars($data['family_info']['mother_name'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Mother's Occupation:</strong> <?php echo htmlspecialchars($data['family_info']['mother_occupation'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Mother's Ethnicity:</strong> <?php echo htmlspecialchars($data['family_info']['mother_ethnicity'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Guardian's Name:</strong> <?php echo htmlspecialchars($data['family_info']['guardian_name'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Guardian's Relationship:</strong> <?php echo htmlspecialchars($data['family_info']['guardian_relationship'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Guardian's Contact:</strong> <?php echo htmlspecialchars($data['family_info']['guardian_contact_number'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Siblings Information:</strong> <?php echo nl2br(htmlspecialchars($data['family_info']['siblings_info'] ?? '')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Educational Background -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>Educational Background
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Last School:</strong> <?php echo htmlspecialchars($data['education']['school_university_last_attended'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Year Graduated:</strong> <?php echo htmlspecialchars($data['education']['year_graduated'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>School Address:</strong> <?php echo nl2br(htmlspecialchars($data['education']['school_address'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Strand Taken:</strong> <?php echo htmlspecialchars($data['education']['strand_taken'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>GPA/GWA:</strong> <?php echo htmlspecialchars($data['education']['general_average'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Honors/Awards:</strong> <?php echo nl2br(htmlspecialchars($data['skills']['awards'] ?? '')); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Extracurricular Activities:</strong> <?php echo nl2br(htmlspecialchars($data['skills']['talents'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Nature of Schooling (Continuous?):</strong> 
                            <?php 
                            $natureContinuous = $data['education']['nature_of_schooling_continuous'] ?? '';
                            if ($natureContinuous === 'Yes') {
                                echo '<span class="badge bg-success">Yes</span>';
                            } elseif ($natureContinuous === 'No') {
                                echo '<span class="badge bg-warning">No</span>';
                            } else {
                                echo '<span class="text-muted">Not specified</span>';
                            }
                            ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Reason if interrupted:</strong> <?php echo nl2br(htmlspecialchars($data['education']['reason_if_interrupted'] ?? '')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Preferences -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-book me-2"></i>Course Preferences
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>First Choice:</strong> <?php echo htmlspecialchars($data['education']['course_first_choice'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Second Choice:</strong> <?php echo htmlspecialchars($data['education']['course_second_choice'] ?? ''); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Third Choice:</strong> <?php echo htmlspecialchars($data['education']['course_third_choice'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Reason for Choice:</strong> <?php echo nl2br(htmlspecialchars($data['education']['reason_for_choice'] ?? '')); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Career Plans:</strong> <?php echo nl2br(htmlspecialchars($data['education']['career_plans'] ?? '')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Additional Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Special Skills:</strong> <?php echo nl2br(htmlspecialchars($data['additional_info']['special_skills'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Hobbies/Interests:</strong> <?php echo nl2br(htmlspecialchars($data['additional_info']['hobbies'] ?? '')); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Languages Spoken:</strong> <?php echo htmlspecialchars($data['additional_info']['languages_spoken'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Health Conditions:</strong> <?php echo htmlspecialchars($data['additional_info']['health_conditions'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Current Medications:</strong> <?php echo htmlspecialchars($data['additional_info']['medications'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Emergency Contact:</strong> <?php echo htmlspecialchars($data['additional_info']['emergency_contact'] ?? ''); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Emergency Contact Number:</strong> <?php echo htmlspecialchars($data['additional_info']['emergency_contact_number'] ?? ''); ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Additional Notes:</strong> <?php echo nl2br(htmlspecialchars($data['additional_info']['additional_notes'] ?? '')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Form submitted on: <?php echo date('M d, Y H:i', strtotime($formData['submitted_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
