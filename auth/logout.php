<?php
require_once '../config/app.php';

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
    $userModel = new User();
    $userModel->logout($_SESSION['session_id']);
}

// Destroy session
session_destroy();

// Redirect to home page
redirect('/index.php?message=logged_out');
?>