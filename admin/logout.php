<?php
require_once '../config/config.php';
includeFile('includes/auth');

if (!isset($auth) || !($auth instanceof Auth)) {
    $auth = new Auth();
}

// Logout user
$auth->logout();

// Redirect to home page (clean URL)
redirect('/');
?>
