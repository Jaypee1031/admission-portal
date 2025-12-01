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
                        Pre-Admission Form
                    </h2>
                    <p class="text-muted mb-0">Please fill out all required information accurately. Use "N/A" for fields that are not applicable to you.</p>
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
        <!-- Form -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">

                    <form method="POST" action="" id="admissionForm" enctype="multipart/form-data">
                        <!-- Personal Information -->
                        <div class="card mb-4">
                            <div class="card-header <?php 
                                $personalComplete = !empty($formData['last_name']) && !empty($formData['first_name']) && 
                                                   !empty($formData['birth_date']) && !empty($formData['birth_place']) && 
                                                   !empty($formData['home_address']) && !empty($formData['mobile_number']);
                                echo $personalComplete ? 'bg-success' : 'bg-warning'; 
                            ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                    <?php if ($personalComplete): ?>
                                        <span class="badge bg-light text-success ms-2">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning ms-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Incomplete
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control auto-caps" id="last_name" name="last_name" 
                                               value="<?php echo $formData['last_name'] ?? $_SESSION['last_name'] ?? ''; ?>" 
                                               style="text-transform: uppercase;" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control auto-caps" id="first_name" name="first_name" 
                                               value="<?php echo $formData['first_name'] ?? $_SESSION['first_name'] ?? ''; ?>" 
                                               style="text-transform: uppercase;" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control auto-caps" id="middle_name" name="middle_name" 
                                               value="<?php echo $formData['middle_name'] ?? $_SESSION['middle_name'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="name_extension" class="form-label">Name Extension</label>
                                        <input type="text" class="form-control" id="name_extension" name="name_extension" 
                                               value="<?php echo $formData['name_extension'] ?? ''; ?>" 
                                               placeholder="Jr., Sr., III, etc.">
                                    </div>
                                </div>

                                <!-- Profile Photo Upload -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="profile_photo" class="form-label">Profile Photo <?php echo empty($formData['profile_photo']) ? '*' : ''; ?></label>
                                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                                               accept="image/*" <?php echo empty($formData['profile_photo']) ? 'required' : ''; ?> >
                                        <div class="form-text">
                                            <?php if (!empty($formData['profile_photo'])): ?>
                                                <small class="text-success">
                                                    <i class="fas fa-check-circle"></i> Photo already uploaded. Upload a new one to replace.
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Upload a clear 2x2 ID picture with white background (JPG, PNG, max 8MB)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Photo Preview</label>
                                        <div class="border rounded p-4 text-center" style="min-height: 200px; background-color: #f8f9fa; position: relative;">
                                            <!-- New Photo Preview -->
                                            <div id="newPhotoPreview" style="display: none;">
                                                <img id="photoPreview" src="" alt="Photo Preview" 
                                                     class="img-thumbnail mb-3" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                                <div class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    <small>New photo selected</small>
                                                </div>
                                            </div>

                                            <!-- Placeholder -->
                                            <div id="photoPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                                <i class="fas fa-camera fa-3x mb-3 text-muted"></i>
                                                <small class="text-muted">Photo preview will appear here</small>
                                            </div>

                                            <!-- Existing Photo -->
                                            <?php if (!empty($formData['profile_photo'])): ?>
                                            <div id="existingPhoto" class="d-flex flex-column align-items-center justify-content-center h-100">
                                                <img src="../<?php echo htmlspecialchars($formData['profile_photo']); ?>" 
                                                     alt="Current Photo" class="img-thumbnail mb-3" style="max-width: 120px; max-height: 120px; object-fit: cover;">
                                                <div class="mb-3">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i> Current photo
                                                    </small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" id="removePhotoBtn">
                                                    <i class="fas fa-trash me-1"></i> Remove Photo
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label for="sex" class="form-label">Sex *</label>
                                        <select class="form-select" id="sex" name="sex" required>
                                            <option value="">Select</option>
                                            <option value="Male" <?php echo ($formData['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($formData['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Gender</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="gender" id="gender_masculine" value="Masculine" 
                                                       <?php echo ($formData['gender'] ?? '') === 'Masculine' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="gender_masculine">Masculine</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="gender" id="gender_feminine" value="Feminine" 
                                                       <?php echo ($formData['gender'] ?? '') === 'Feminine' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="gender_feminine">Feminine</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="gender" id="gender_lgbtq" value="LGBTQ+" 
                                                       <?php echo ($formData['gender'] ?? '') === 'LGBTQ+' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="gender_lgbtq">LGBTQ+</label>
                                            </div>
                                        </div>
                                        <div id="gender_specify_div" class="mt-2" style="display: none;">
                                            <label for="gender_specify" class="form-label">Specify:</label>
                                            <input type="text" class="form-control" id="gender_specify" name="gender_specify" 
                                                   value="<?php echo $formData['gender_specify'] ?? ''; ?>" placeholder="Please specify">
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="civil_status" class="form-label">Civil Status *</label>
                                        <select class="form-select" id="civil_status" name="civil_status" required>
                                            <option value="">Select</option>
                                            <option value="Single" <?php echo ($formData['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo ($formData['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Widowed" <?php echo ($formData['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                            <option value="Divorced" <?php echo ($formData['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Separated" <?php echo ($formData['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="spouse_name" class="form-label">Spouse Name</label>
                                        <input type="text" class="form-control auto-caps" id="spouse_name" name="spouse_name" 
                                               value="<?php echo $formData['spouse_name'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="birth_date" class="form-label">Birth Date *</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                               value="<?php echo $formData['birth_date'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="age" class="form-label">Age *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="age" name="age" 
                                                   value="<?php echo $formData['age'] ?? ''; ?>" 
                                                   placeholder="Age" min="1" max="120" required>
                                            <button type="button" class="btn btn-outline-secondary" id="calculateAgeBtn" title="Calculate from birth date">
                                                =
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">Enter manually or calculate from birth date</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="birth_place" class="form-label">Birth Place *</label>
                                        <input type="text" class="form-control auto-caps" id="birth_place" name="birth_place" 
                                               value="<?php echo $formData['birth_place'] ?? ''; ?>" 
                                               style="text-transform: uppercase;" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="pwd" name="pwd" 
                                                   <?php echo ($formData['pwd'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pwd">
                                                PWD
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="disability" class="form-label">Disability (if PWD)</label>
                                        <input type="text" class="form-control" id="disability" name="disability" 
                                               value="<?php echo $formData['disability'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Ethnic Affiliation</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ethnic_affiliation" id="ethnic_ilocano" value="Ilocano" 
                                                       <?php echo ($formData['ethnic_affiliation'] ?? '') === 'Ilocano' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ethnic_ilocano">Ilocano</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ethnic_affiliation" id="ethnic_igorot" value="Igorot" 
                                                       <?php echo ($formData['ethnic_affiliation'] ?? '') === 'Igorot' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ethnic_igorot">Igorot</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ethnic_affiliation" id="ethnic_ifugao" value="Ifugao" 
                                                       <?php echo ($formData['ethnic_affiliation'] ?? '') === 'Ifugao' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ethnic_ifugao">Ifugao</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ethnic_affiliation" id="ethnic_bisaya" value="Bisaya" 
                                                       <?php echo ($formData['ethnic_affiliation'] ?? '') === 'Bisaya' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ethnic_bisaya">Bisaya</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="ethnic_affiliation" id="ethnic_others" value="Others" 
                                                       <?php echo ($formData['ethnic_affiliation'] ?? '') === 'Others' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ethnic_others">Others</label>
                                            </div>
                                        </div>
                                        <div id="ethnic_specify_div" class="mt-2" style="display: none;">
                                            <label for="ethnic_others_specify" class="form-label">Specify:</label>
                                            <input type="text" class="form-control" id="ethnic_others_specify" name="ethnic_others_specify" 
                                                   value="<?php echo $formData['ethnic_others_specify'] ?? ''; ?>" placeholder="Please specify your ethnic affiliation">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card mb-4">
                            <div class="card-header <?php 
                                $contactComplete = !empty($formData['home_address']) && !empty($formData['mobile_number']) && !empty($formData['email_address']);
                                echo $contactComplete ? 'bg-success' : 'bg-warning'; 
                            ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-address-book me-2"></i>Contact Information
                                    <?php if ($contactComplete): ?>
                                        <span class="badge bg-light text-success ms-2">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning ms-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Incomplete
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="home_address" class="form-label">Home Address *</label>
                                        <textarea class="form-control" id="home_address" name="home_address" rows="3" required><?php echo $formData['home_address'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                                   value="<?php echo isset($formData['mobile_number']) && $formData['mobile_number'] ? (strpos($formData['mobile_number'], '+63') === 0 ? substr($formData['mobile_number'], 3) : $formData['mobile_number']) : ''; ?>" 
                                                   placeholder="9123456789" 
                                                   pattern="[0-9]{10}" 
                                                   maxlength="10"
                                                   required>
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">Format: +63 9XXXXXXXXX (10 digits after +63)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="email_address" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email_address" name="email_address" 
                                               value="<?php echo $formData['email_address'] ?? $user['email']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Family Information -->
                        <div class="card mb-4">
                            <div class="card-header <?php 
                                $familyComplete = !empty($formData['father_name']) && !empty($formData['mother_name']);
                                echo $familyComplete ? 'bg-success' : 'bg-warning'; 
                            ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>Family Information
                                    <?php if ($familyComplete): ?>
                                        <span class="badge bg-light text-success ms-2">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning ms-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Incomplete
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="father_name" class="form-label">Father's Name</label>
                                        <input type="text" class="form-control auto-caps" id="father_name" name="father_name" 
                                               value="<?php echo $formData['father_name'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="father_occupation" class="form-label">Father's Occupation</label>
                                        <input type="text" class="form-control auto-caps" id="father_occupation" name="father_occupation" 
                                               value="<?php echo $formData['father_occupation'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="father_contact" class="form-label">Father's Contact</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="father_contact" name="father_contact" 
                                                   value="<?php echo isset($formData['father_contact']) && $formData['father_contact'] ? (strpos($formData['father_contact'], '+63') === 0 ? substr($formData['father_contact'], 3) : $formData['father_contact']) : ''; ?>"
                                                   placeholder="9123456789" 
                                                   pattern="[0-9]{10}" 
                                                   maxlength="10">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="mother_name" class="form-label">Mother's Name</label>
                                        <input type="text" class="form-control auto-caps" id="mother_name" name="mother_name" 
                                               value="<?php echo $formData['mother_name'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="mother_occupation" class="form-label">Mother's Occupation</label>
                                        <input type="text" class="form-control auto-caps" id="mother_occupation" name="mother_occupation" 
                                               value="<?php echo $formData['mother_occupation'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="mother_contact" class="form-label">Mother's Contact</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="mother_contact" name="mother_contact" 
                                                   value="<?php echo isset($formData['mother_contact']) && $formData['mother_contact'] ? (strpos($formData['mother_contact'], '+63') === 0 ? substr($formData['mother_contact'], 3) : $formData['mother_contact']) : ''; ?>"
                                                   placeholder="9123456789" 
                                                   pattern="[0-9]{10}" 
                                                   maxlength="10">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="guardian_name" class="form-label">Guardian's Name</label>
                                        <input type="text" class="form-control auto-caps" id="guardian_name" name="guardian_name" 
                                               value="<?php echo $formData['guardian_name'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="guardian_occupation" class="form-label">Guardian's Occupation</label>
                                        <input type="text" class="form-control auto-caps" id="guardian_occupation" name="guardian_occupation" 
                                               value="<?php echo $formData['guardian_occupation'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="guardian_contact" class="form-label">Guardian's Contact</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" 
                                                   value="<?php echo isset($formData['guardian_contact']) && $formData['guardian_contact'] ? (strpos($formData['guardian_contact'], '+63') === 0 ? substr($formData['guardian_contact'], 3) : $formData['guardian_contact']) : ''; ?>"
                                                   placeholder="9123456789" 
                                                   pattern="[0-9]{10}" 
                                                   maxlength="10">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Educational Background -->
                        <div class="card mb-4">
                            <div class="card-header <?php 
                                $educationComplete = !empty($formData['last_school']) && !empty($formData['strand_taken']);
                                if ($user['student_type'] === 'Transferee') {
                                    $educationComplete = $educationComplete && !empty($formData['year_last_attended']);
                                } else {
                                    $educationComplete = $educationComplete && !empty($formData['year_graduated']);
                                }
                                echo $educationComplete ? 'bg-success' : 'bg-warning'; 
                            ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-graduation-cap me-2"></i>Educational Background
                                    <?php if ($educationComplete): ?>
                                        <span class="badge bg-light text-success ms-2">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning ms-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Incomplete
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="last_school" class="form-label">School/ University Last Attended</label>
                                        <input type="text" class="form-control auto-caps" id="last_school" name="last_school" 
                                               value="<?php echo $formData['last_school'] ?? ''; ?>"
                                               style="text-transform: uppercase;">
                                    </div>
                                    <?php if ($user['student_type'] === 'Transferee'): ?>
                                    <div class="col-md-3 mb-3">
                                        <label for="year_last_attended" class="form-label">Year Last Attended (for transferee):</label>
                                        <input type="number" class="form-control" id="year_last_attended" name="year_last_attended" 
                                               value="<?php echo $formData['year_last_attended'] ?? ''; ?>" 
                                               min="1900" max="<?php echo date('Y'); ?>"
                                               placeholder="Enter year last attended">
                                        <div class="form-text">
                                            <small class="text-muted">Required for transferee students</small>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="col-md-3 mb-3" style="display: none;">
                                        <input type="hidden" name="year_last_attended" value="">
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-3 mb-3">
                                        <label for="year_graduated" class="form-label">Year Graduated (for Incoming Freshman:)</label>
                                        <input type="number" class="form-control" id="year_graduated" name="year_graduated" 
                                               value="<?php echo $formData['year_graduated'] ?? ''; ?>" min="1900" max="<?php echo date('Y'); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="school_address" class="form-label">School Address</label>
                                        <textarea class="form-control" id="school_address" name="school_address" rows="2"><?php echo $formData['school_address'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="strand_taken" class="form-label">Strand Taken (SHS)</label>
                                        <select class="form-select" id="strand_taken" name="strand_taken">
                                            <option value="">Select Strand</option>
                                            <?php foreach ($strands as $strand): ?>
                                            <option value="<?php echo $strand; ?>" <?php echo ($formData['strand_taken'] ?? '') === $strand ? 'selected' : ''; ?>>
                                                <?php echo $strand; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="grade_12_gwa" class="form-label">Grade 12 GWA (General Weighted Average) *</label>
                                        <input type="number" class="form-control" id="grade_12_gwa" name="grade_12_gwa" 
                                               value="<?php echo $formData['grade_12_gwa'] ?? ''; ?>" 
                                               placeholder="e.g., 85.50" step="0.01" min="75" max="100" required>
                                        <div class="form-text">Enter your Grade 12 General Weighted Average from Senior High School</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Course Preferences -->
                        <div class="card mb-4">
                            <div class="card-header <?php 
                                $courseComplete = !empty($formData['course_first']);
                                echo $courseComplete ? 'bg-success' : 'bg-warning'; 
                            ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-book me-2"></i>Course Preferences
                                    <?php if ($courseComplete): ?>
                                        <span class="badge bg-light text-success ms-2">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning ms-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Incomplete
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="course_first" class="form-label">First Choice *</label>
                                        <select class="form-select" id="course_first" name="course_first" required>
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course; ?>" <?php echo ($formData['course_first'] ?? '') === $course ? 'selected' : ''; ?>>
                                                <?php echo $course; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="course_second" class="form-label">Second Choice</label>
                                        <select class="form-select" id="course_second" name="course_second">
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course; ?>" <?php echo ($formData['course_second'] ?? '') === $course ? 'selected' : ''; ?>>
                                                <?php echo $course; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="course_third" class="form-label">Third Choice</label>
                                        <select class="form-select" id="course_third" name="course_third">
                                            <option value="">Select Course</option>
                                            <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course; ?>" <?php echo ($formData['course_third'] ?? '') === $course ? 'selected' : ''; ?>>
                                                <?php echo $course; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Save Form
                            </button>
                        </div>

                        <!-- Success Message (hidden by default) -->
                        <div id="successMessage" class="alert alert-success mt-3" style="display: none;">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Form saved successfully!</strong> Your QSU F1-A admission form has been saved and PDF generated.
                            <div class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> You must request your test permit before you can view or download the PDF.
                            </div>
                            <div class="mt-3">
                                <a href="test_permit.php" class="btn btn-warning me-2">
                                    <i class="fas fa-file-alt me-2"></i>Request Test Permit First
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-primary ms-2">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Custom styling for age field */
        #age {
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            color: #006633;
            background-color: #f8f9fa;
        }

        #age:focus {
            background-color: #ffffff;
            border-color: #006633;
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 51, 0.25);
        }

        #age::-webkit-outer-spin-button,
        #age::-webkit-inner-spin-button {
            opacity: 1;
            height: 100%;
        }

        #admissionForm {
            display: block !important;
        }

        .debug-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
    <script>
        // Form validation and success handling
        document.getElementById('admissionForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
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
            } else {
                // Ensure form is visible if no success message
                const admissionForm = document.getElementById('admissionForm');
                if (admissionForm) {
                    admissionForm.style.display = 'block';
                }
            }
        });

        // Function to calculate age from birth date (returns null if invalid)
        function calculateAge(birthDate) {
            if (!birthDate) return null;

            const birth = new Date(birthDate);
            if (isNaN(birth.getTime())) {
                return null;
            }

            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }

            if (age < 0 || age > 120) {
                return null;
            }

            return age;
        }

        function highlightAgeField() {
            const ageField = document.getElementById('age');
            if (!ageField) return;

            const originalBg = ageField.style.backgroundColor || '';
            const originalBorder = ageField.style.borderColor || '';

            ageField.style.backgroundColor = '#d4edda';
            ageField.style.borderColor = '#28a745';
            setTimeout(() => {
                ageField.style.backgroundColor = originalBg;
                ageField.style.borderColor = originalBorder;
            }, 900);
        }

        // Wire up age calculation once the DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const birthDateField = document.getElementById('birth_date');
            const ageField = document.getElementById('age');
            const calcBtn = document.getElementById('calculateAgeBtn');

            if (!birthDateField || !ageField) {
                return;
            }

            function updateAgeFromBirth() {
                const age = calculateAge(birthDateField.value);
                if (!birthDateField.value) {
                    // If birth date cleared, clear age without showing an error
                    ageField.value = '';
                    return;
                }

                if (age !== null) {
                    ageField.value = age;
                    highlightAgeField();
                } else {
                    ageField.value = '';
                    alert('Please select a valid birth date.');
                }
            }

            birthDateField.addEventListener('change', updateAgeFromBirth);

            if (calcBtn) {
                calcBtn.addEventListener('click', function() {
                    if (!birthDateField.value) {
                        alert('Please select a birth date first');
                        return;
                    }
                    updateAgeFromBirth();
                });
            }

            // Calculate age on first load if birth date already has a value
            if (birthDateField.value && !ageField.value) {
                const initialAge = calculateAge(birthDateField.value);
                if (initialAge !== null) {
                    ageField.value = initialAge;
                }
            }
        });

        // Show/hide spouse name based on civil status
        document.getElementById('civil_status').addEventListener('change', function() {
            const spouseField = document.getElementById('spouse_name').closest('.col-md-3');
            if (this.value === 'Married') {
                spouseField.style.display = 'block';
            } else {
                spouseField.style.display = 'none';
                document.getElementById('spouse_name').value = '';
            }
        });

        // Show/hide disability field based on PWD checkbox
        document.getElementById('pwd').addEventListener('change', function() {
            const disabilityField = document.getElementById('disability').closest('.col-md-3');
            if (this.checked) {
                disabilityField.style.display = 'block';
            } else {
                disabilityField.style.display = 'none';
                document.getElementById('disability').value = '';
            }
        });

        // Show/hide gender specify field based on gender selection
        function handleGenderChange() {
            const genderLGBTQ = document.getElementById('gender_lgbtq');
            const genderSpecifyDiv = document.getElementById('gender_specify_div');
            const genderSpecifyInput = document.getElementById('gender_specify');

            if (genderLGBTQ.checked) {
                genderSpecifyDiv.style.display = 'block';
                genderSpecifyInput.required = true;
            } else {
                genderSpecifyDiv.style.display = 'none';
                genderSpecifyInput.required = false;
                genderSpecifyInput.value = '';
            }
        }

        // Show/hide ethnic specify field based on ethnic affiliation selection
        function handleEthnicChange() {
            const ethnicOthers = document.getElementById('ethnic_others');
            const ethnicSpecifyDiv = document.getElementById('ethnic_specify_div');
            const ethnicSpecifyInput = document.getElementById('ethnic_others_specify');

            if (ethnicOthers.checked) {
                ethnicSpecifyDiv.style.display = 'block';
                ethnicSpecifyInput.required = true;
            } else {
                ethnicSpecifyDiv.style.display = 'none';
                ethnicSpecifyInput.required = false;
                ethnicSpecifyInput.value = '';
            }
        }

        // Add event listeners to all gender radio buttons
        document.querySelectorAll('input[name="gender"]').forEach(function(radio) {
            radio.addEventListener('change', handleGenderChange);
        });

        // Add event listeners to all ethnic affiliation radio buttons
        document.querySelectorAll('input[name="ethnic_affiliation"]').forEach(function(radio) {
            radio.addEventListener('change', handleEthnicChange);
        });

        // Photo preview functionality
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const newPhotoPreview = document.getElementById('newPhotoPreview');
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            const existingPhoto = document.getElementById('existingPhoto');

            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file (JPG, PNG, etc.)');
                    this.value = '';
                    return;
                }

                // Validate file size (8MB max)
                if (file.size > 8 * 1024 * 1024) {
                    alert('File size must be less than 8MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    newPhotoPreview.style.display = 'block';
                    placeholder.style.display = 'none';
                    if (existingPhoto) {
                        existingPhoto.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            } else {
                newPhotoPreview.style.display = 'none';
                placeholder.style.display = 'flex';
                if (existingPhoto) {
                    existingPhoto.style.display = 'flex';
                }
            }
        });

        // Remove photo functionality
        document.getElementById('removePhotoBtn')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove your profile photo? You will need to upload a new one.')) {
                // Hide existing photo
                const existingPhoto = document.getElementById('existingPhoto');
                if (existingPhoto) {
                    existingPhoto.style.display = 'none';
                }

                // Show placeholder
                const placeholder = document.getElementById('photoPlaceholder');
                if (placeholder) {
                    placeholder.style.display = 'flex';
                }

                // Clear file input
                const fileInput = document.getElementById('profile_photo');
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.required = true; // Make it required again
                }

                // Add hidden input to indicate photo removal
                let removePhotoInput = document.getElementById('remove_photo');
                if (!removePhotoInput) {
                    removePhotoInput = document.createElement('input');
                    removePhotoInput.type = 'hidden';
                    removePhotoInput.name = 'remove_photo';
                    removePhotoInput.id = 'remove_photo';
                    removePhotoInput.value = '1';
                    document.getElementById('admissionForm').appendChild(removePhotoInput);
                }

                // Update label to show required
                const label = document.querySelector('label[for="profile_photo"]');
                if (label && !label.textContent.includes('*')) {
                    label.textContent = label.textContent.replace('Profile Photo', 'Profile Photo *');
                }

                // Update help text
                const helpText = document.querySelector('#profile_photo').nextElementSibling;
                if (helpText) {
                    helpText.innerHTML = '<small class="text-muted">Upload a clear 2x2 ID picture with white background (JPG, PNG, max 8MB)</small>';
                }
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

        // Initialize form state
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger civil status change
            document.getElementById('civil_status').dispatchEvent(new Event('change'));

            // Trigger PWD change
            document.getElementById('pwd').dispatchEvent(new Event('change'));

            // Trigger gender change to initialize specify field
            handleGenderChange();

            // Trigger ethnic change to initialize specify field
            handleEthnicChange();

            // Apply auto-capitalization to all name fields
            const nameFields = [
                'last_name', 'first_name', 'middle_name', 'spouse_name', 'birth_place',
                'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
                'guardian_name', 'guardian_occupation', 'last_school'
            ];

            nameFields.forEach(fieldId => {
                autoCapitalize(document.getElementById(fieldId));
            });

            // Philippine mobile number validation
            const mobileFields = ['mobile_number', 'father_contact', 'mother_contact', 'guardian_contact'];

            mobileFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    // Format existing values to remove +63 if present
                    if (field.value && field.value.startsWith('+63')) {
                        field.value = field.value.substring(3);
                    }

                    // Add input event listener for real-time validation
                    field.addEventListener('input', function() {
                        // Remove any non-numeric characters
                        let value = this.value.replace(/\D/g, '');

                        // Limit to 10 digits
                        if (value.length > 10) {
                            value = value.substring(0, 10);
                        }

                        this.value = value;

                        // Validate Philippine mobile number format
                        validatePhilippineMobile(this);
                    });

                    // Add blur event listener for final validation
                    field.addEventListener('blur', function() {
                        validatePhilippineMobile(this);
                    });
                }
            });

            // Function to validate Philippine mobile number
            function validatePhilippineMobile(field) {
                const value = field.value.trim();

                if (value === '') {
                    if (field.required) {
                        field.setCustomValidity('This field is required');
                    } else {
                        field.setCustomValidity('');
                    }
                    return;
                }

                // Check if it's exactly 10 digits
                if (value.length !== 10) {
                    field.setCustomValidity('Mobile number must be exactly 10 digits');
                    return;
                }

                // Check if it starts with 9 (Philippine mobile numbers start with 9)
                if (!value.startsWith('9')) {
                    field.setCustomValidity('Philippine mobile numbers must start with 9');
                    return;
                }

                // Check if it's a valid Philippine mobile number pattern
                const phMobilePattern = /^9[0-9]{9}$/;
                if (!phMobilePattern.test(value)) {
                    field.setCustomValidity('Invalid Philippine mobile number format');
                    return;
                }

                // Valid number
                field.setCustomValidity('');
            }

            // Form submission validation
            document.getElementById('admissionForm').addEventListener('submit', function(e) {
                let isValid = true;

                mobileFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field && field.value.trim() !== '') {
                        validatePhilippineMobile(field);
                        if (!field.checkValidity()) {
                            isValid = false;
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please correct the mobile number format. Philippine mobile numbers must be 10 digits starting with 9 (e.g., 9123456789).');
                }
            });
        });
    </script>
</body>
</html>

