<?php
// Start session
session_start();

// Set headers
header('Content-Type: application/json');

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Test database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'session_user_id' => $_SESSION['user_id'] ?? 'not set',
        'session_user_role' => $_SESSION['user_role'] ?? 'not set'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>