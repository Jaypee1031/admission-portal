<?php
require_once 'config/config.php';
includeFile('includes/favicon');

if (!empty($_SERVER['PATH_INFO'])) {
    // Redirect any extra path like index.php/admin back to the main index page
    redirect('/');
}

if (isLoggedIn()) {
    if (isStudent()) {
        redirect('/student/dashboard');
    } elseif (isAdmin()) {
        redirect('/admin/dashboard');
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <?php includeFavicon(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
   
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="." style="display: ;">
                <img src="assets/images/qsulogo.png" alt="QSU Logo" height="50" class="me-2">
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="student/register">Student Registration</a>
                    <a class="nav-link" href="student/login">Student Login</a>
                    <a class="nav-link" href="admin/login">Admin Login</a>
                </div>
            </div>
        </div>
    </nav>


    <?php if ($alert): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>


    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Welcome to University Admission Portal
                    </h1>
                    <p class="lead text-white mb-4">
                        Streamline your university admission process with our comprehensive online portal. 
                        Apply as a freshman or transferee, upload requirements, and track your application status.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="student/register" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Apply Now
                        </a>
                        <a href="student/login" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Student Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-university display-1 text-white opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Our Academic Programs</h2>
            
            <div class="row g-4">
                <!-- Board Courses -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="h5 mb-0"><i class="fas fa-graduation-cap me-2"></i>Board Courses</h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Agriculture</h6>
                                            <small class="text-muted">Major in Animal Science, Crop Science</small>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BSA</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Agricultural Biosystems Engineering</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BSABE</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Forestry</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BSF</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Nutrition and Dietetics</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BSND</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor in Elementary Education</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BEED</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Secondary Education</h6>
                                            <small class="text-muted">Major in Filipino, Science, Math, English</small>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BSED</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor in Technology and Livelihood Education</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BTLED</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Criminology</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e3f2fd; color: #0d47a1; font-weight: 600; padding: 0.4em 0.8em;">BS Crim</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-Board Courses -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h3 class="h5 mb-0"><i class="fas fa-laptop-code me-2"></i>Non-Board Courses</h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Information Technology</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e8f5e9; color: #1b5e20; font-weight: 600; padding: 0.4em 0.8em;">BSIT</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Office Administration</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e8f5e9; color: #1b5e20; font-weight: 600; padding: 0.4em 0.8em;">BSOA</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Hospitality Management</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e8f5e9; color: #1b5e20; font-weight: 600; padding: 0.4em 0.8em;">BSHM</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Bachelor of Science in Tourism Management</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e8f5e9; color: #1b5e20; font-weight: 600; padding: 0.4em 0.8em;">BSTM</span>
                                    </div>
                                </div>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">Caregiving Course</h6>
                                        </div>
                                        <span class="badge" style="background-color: #e8f5e9; color: #1b5e20; font-weight: 600; padding: 0.4em 0.8em;">CGC</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">Streamlining university admissions for a better future.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> University. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>