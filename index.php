<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Try to initialize database
try {
    initializeDatabase();
} catch(Exception $e) {
    // If database fails, redirect to install
    header('Location: install.php');
    exit();
}

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>