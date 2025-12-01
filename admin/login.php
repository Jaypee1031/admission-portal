<?php
require_once '../config/config.php';
includeFile('includes/auth');
includeFile('includes/favicon');

if (!isset($auth) || !($auth instanceof Auth)) {
    $auth = new Auth();
}

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStudent()) {
        redirect('/student/dashboard');
    } elseif (isAdmin()) {
        redirect('/admin/dashboard');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = $auth->loginAdmin($username, $password);
        if ($result['success']) {
            redirect('/admin/dashboard');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../">
                <img src="../assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../">Home</a>
                <a class="nav-link" href="../student/login">Student Login</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>
                            Admin Login
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="admin-login">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       style="text-transform: none !important; -webkit-text-transform: none !important; -moz-text-transform: none !important; -ms-text-transform: none !important;" 
                                       autocapitalize="none" autocorrect="off" spellcheck="false" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       style="text-transform: none !important; -webkit-text-transform: none !important; -moz-text-transform: none !important; -ms-text-transform: none !important;" 
                                       autocapitalize="none" autocorrect="off" spellcheck="false" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Login
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p class="mb-0">Student? 
                                <a href="../student/login" class="text-decoration-none">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent auto-capitalization on login fields
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            // Force lowercase for username (if needed)
            if (usernameField) {
                usernameField.addEventListener('input', function() {
                    // Remove any auto-capitalization
                    this.style.textTransform = 'none';
                    this.style.webkitTextTransform = 'none';
                    this.style.mozTextTransform = 'none';
                    this.style.msTextTransform = 'none';
                });
                
                // Set attributes to prevent mobile auto-capitalization
                usernameField.setAttribute('autocapitalize', 'none');
                usernameField.setAttribute('autocorrect', 'off');
                usernameField.setAttribute('spellcheck', 'false');
            }
            
            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    // Remove any auto-capitalization
                    this.style.textTransform = 'none';
                    this.style.webkitTextTransform = 'none';
                    this.style.mozTextTransform = 'none';
                    this.style.msTextTransform = 'none';
                });
                
                // Set attributes to prevent mobile auto-capitalization
                passwordField.setAttribute('autocapitalize', 'none');
                passwordField.setAttribute('autocorrect', 'off');
                passwordField.setAttribute('spellcheck', 'false');
            }
        });
    </script>
</body>
</html>
