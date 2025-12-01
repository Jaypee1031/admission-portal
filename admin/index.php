<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isAdmin()) {
    redirect('/admin/dashboard');
}

redirect('../index.php');
