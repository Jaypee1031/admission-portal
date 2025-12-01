<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$user = $auth->getCurrentUser();

// Get admin profile information
$profile = $auth->getAdminProfile($user['id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    
    if (empty($fullName)) {
        showAlert('Full name is required', 'error');
    } else {
        $result = $auth->updateAdminProfile($user['id'], $fullName);
        if ($result['success']) {
            showAlert($result['message'], 'success');
            // Refresh user data
            $user = $auth->getCurrentUser();
            $profile = $auth->getAdminProfile($user['id']);
        } else {
            showAlert($result['message'], 'error');
        }
    }
    
    redirect('profile_settings.php');
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo SITE_NAME; ?></title>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-user-edit me-2 text-primary"></i>
                                Profile Settings
                            </h2>
                            <p class="text-muted mb-0">Manage your account profile information.</p>
                        </div>
                        <div class="text-end">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </div>
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

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="dashboard-card">
                    <h5 class="mb-4">
                        <i class="fas fa-user me-2 text-primary"></i>
                        Profile Information
                    </h5>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>" 
                                       readonly>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Username cannot be changed for security reasons.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-shield me-1"></i>Role
                                </label>
                                <input type="text" class="form-control" id="role" 
                                       value="<?php echo htmlspecialchars($profile['role'] ?? ''); ?>" 
                                       readonly>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Role is assigned by system administrator.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" 
                                   required>
                            <div class="invalid-feedback">
                                Please enter your full name.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="created_at" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Account Created
                            </label>
                            <input type="text" class="form-control" id="created_at" 
                                   value="<?php echo $profile['created_at'] ? date('F d, Y', strtotime($profile['created_at'])) : 'N/A'; ?>" 
                                   readonly>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Security -->
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h5 class="mb-4">
                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                        Account Security
                    </h5>
                    
                    <div class="d-grid gap-2">
                        <a href="change_password.php" class="btn btn-outline-primary">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Security Tips
                        </h6>
                        <ul class="mb-0 small">
                            <li>Use a strong, unique password</li>
                            <li>Change your password regularly</li>
                            <li>Never share your login credentials</li>
                            <li>Log out when using shared computers</li>
                            <li>Report any suspicious activity</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Account Statistics -->
                <div class="dashboard-card mt-4">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Account Statistics
                    </h5>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-1"><?php echo $profile['role'] ?? 'N/A'; ?></h4>
                                <small class="text-muted">Role</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-1">Active</h4>
                            <small class="text-muted">Status</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
    </script>
</body>
</html>
