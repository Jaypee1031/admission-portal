<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/favicon.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStudent()) {
        redirect('/student/dashboard');
    } elseif (isAdmin()) {
        redirect('../admin/dashboard.php');
    }
}

$error = '';
$success = '';

$registrationOpen = 1;
$registrationClosed = false;

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS test_permit_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("SELECT setting_value FROM test_permit_settings WHERE setting_key = 'registration_open'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    if ($value !== false) {
        $registrationOpen = (int)$value;
    }
} catch (Exception $e) {
    $registrationOpen = 1;
}

if (!$registrationOpen) {
    $registrationClosed = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($registrationClosed) {
        $error = 'QSU admission is currently closed. Online registration is not available at this time.';
    } else {
        $lastName = sanitizeInput($_POST['last_name']);
        $firstName = sanitizeInput($_POST['first_name']);
        $middleName = sanitizeInput($_POST['middle_name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $type = sanitizeInput($_POST['type']);
        
        // Validation
        if (empty($lastName) || empty($firstName) || empty($email) || empty($password) || empty($type)) {
            $error = 'Last Name, First Name, Email, Password, and Admission Type are required';
        } elseif (!preg_match('/^[A-Za-zÑñ\s.\-]+$/', $lastName)) {
            $error = 'Last Name may only contain letters and spaces';
        } elseif (!preg_match('/^[A-Za-zÑñ\s.\-]+$/', $firstName)) {
            $error = 'First Name may only contain letters and spaces';
        } elseif (!empty($middleName) && !preg_match('/^[A-Za-zÑñ\s.\-]+$/', $middleName)) {
            $error = 'Middle Name may only contain letters and spaces';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain both letters and numbers';
        } else {
            $result = $auth->registerStudent($lastName, $firstName, $middleName, $email, $password, $type);
            if ($result['success']) {
                $success = $result['message'];
                // Auto login after successful registration
                $loginResult = $auth->loginStudent($email, $password);
                if ($loginResult['success']) {
                    redirect('/student/dashboard');
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - <?php echo SITE_NAME; ?></title>
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
                <a class="nav-link" href="../index.php">Home</a>
                <a class="nav-link" href="login.php">Login</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Student Registration
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($registrationClosed): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            QSU admission is currently closed. Online registration is not available at this time. Please contact the admission office for more information. FB Paged(Office of the Student Affairs and Services - Dffin, Quirino)
                        </div>
                        <?php else: ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control auto-caps" id="last_name" name="last_name" 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                           style="text-transform: uppercase;" pattern="[A-Za-zÑñ\s.\-]+" title="Letters only; numbers are not allowed." required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control auto-caps" id="first_name" name="first_name" 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                           style="text-transform: uppercase;" pattern="[A-Za-zÑñ\s.\-]+" title="Letters only; numbers are not allowed." required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control auto-caps" id="middle_name" name="middle_name" 
                                           value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>"
                                           style="text-transform: uppercase;" pattern="[A-Za-zÑñ\s.\-]+" title="Letters only; numbers are not allowed.">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Admission Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="Freshman" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Freshman') ? 'selected' : ''; ?>>
                                        Incoming Freshman
                                    </option>
                                    <option value="Transferee" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Transferee') ? 'selected' : ''; ?>>
                                        Transferee
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                <div class="form-text">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters, must include both letters and numbers.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Register
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none">Login here</a>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-capitalization for name fields
        function autoCapitalize(input) {
            if (!input) return;
            input.addEventListener('input', function() {
                // Keep only letters (including Ñ/ñ), spaces, dot, and hyphen; convert to uppercase
                this.value = this.value.replace(/[^A-Za-zÑñ\s.\-]/g, '').toUpperCase();
            });
        }

        // Apply auto-capitalization to name fields
        autoCapitalize(document.getElementById('last_name'));
        autoCapitalize(document.getElementById('first_name'));
        autoCapitalize(document.getElementById('middle_name'));
    </script>
</body>
</html>
