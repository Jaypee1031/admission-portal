<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isStudent()) {
    redirect('/student/dashboard');
}

redirect('../index.php');
