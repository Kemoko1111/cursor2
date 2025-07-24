<?php
// Simple authentication middleware
// This file can be included to ensure user is authenticated

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function checkAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    return true;
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!checkAuth()) {
        header('Location: /auth/login.php');
        exit();
    }
}

// This middleware will be included in protected pages
// No automatic redirect here, let the individual pages handle it
?>