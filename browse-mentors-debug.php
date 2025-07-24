<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Browse Mentors</h2>";

// Test 1: Check if config loads
echo "<h3>1. Testing Config Load</h3>";
try {
    require_once 'config/app.php';
    echo "✅ Config loaded successfully<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 2: Check session
echo "<h3>2. Testing Session</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: ID = " . $_SESSION['user_id'] . ", Role = " . ($_SESSION['user_role'] ?? 'undefined') . "<br>";
} else {
    echo "❌ User not logged in<br>";
    echo "<a href='/auth/login.php'>Login first</a><br>";
    exit;
}

// Test 3: Check database connection
echo "<h3>3. Testing Database Connection</h3>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Check if User model works
echo "<h3>4. Testing User Model</h3>";
try {
    $userModel = new User();
    echo "✅ User model created<br>";
    
    $currentUser = $userModel->getUserById($_SESSION['user_id']);
    if ($currentUser) {
        echo "✅ Current user loaded: " . $currentUser['first_name'] . " " . $currentUser['last_name'] . "<br>";
    } else {
        echo "❌ Could not load current user<br>";
    }
} catch (Exception $e) {
    echo "❌ User model error: " . $e->getMessage() . "<br>";
}

// Test 5: Check if we can get mentors
echo "<h3>5. Testing Mentor Query</h3>";
try {
    $query = "SELECT * FROM users WHERE role = 'mentor' LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $mentors = $stmt->fetchAll();
    
    echo "✅ Found " . count($mentors) . " mentors<br>";
    
    if (count($mentors) > 0) {
        echo "<h4>Sample mentors:</h4>";
        foreach ($mentors as $mentor) {
            echo "- " . ($mentor['first_name'] ?? 'N/A') . " " . ($mentor['last_name'] ?? 'N/A') . 
                 " (" . ($mentor['department'] ?? 'N/A') . ")<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Mentor query error: " . $e->getMessage() . "<br>";
}

// Test 6: Check database structure
echo "<h3>6. Testing Database Structure</h3>";
try {
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "✅ Users table columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Database structure error: " . $e->getMessage() . "<br>";
}

echo "<h3>Debug Complete</h3>";
echo "<a href='/browse-mentors.php'>Try Browse Mentors</a> | ";
echo "<a href='/dashboard.php'>Back to Dashboard</a>";
?>