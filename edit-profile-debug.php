<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug: Edit Profile</h2>";

// Test 1: Check if config loads
echo "<h3>1. Testing Config Load</h3>";
try {
    require_once 'config/app.php';
    echo "✅ Config loaded successfully<br>";
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
    $userId = $_SESSION['user_id'];
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
    
    $currentUser = $userModel->getUserById($userId);
    if ($currentUser) {
        echo "✅ Current user loaded: " . $currentUser['first_name'] . " " . $currentUser['last_name'] . "<br>";
        echo "📋 User data available:<br>";
        foreach ($currentUser as $key => $value) {
            if ($key !== 'password_hash') {
                echo "- $key: " . ($value ?? 'NULL') . "<br>";
            }
        }
    } else {
        echo "❌ Could not load current user<br>";
    }
} catch (Exception $e) {
    echo "❌ User model error: " . $e->getMessage() . "<br>";
}

// Test 5: Test update profile method
echo "<h3>5. Testing Update Profile Method</h3>";
try {
    if (method_exists($userModel, 'updateProfile')) {
        echo "✅ updateProfile method exists<br>";
    } else {
        echo "❌ updateProfile method does not exist<br>";
    }
    
    if (method_exists($userModel, 'updateProfileImage')) {
        echo "✅ updateProfileImage method exists<br>";
    } else {
        echo "❌ updateProfileImage method does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Method check error: " . $e->getMessage() . "<br>";
}

// Test 6: Check users table structure
echo "<h3>6. Testing Users Table Structure</h3>";
try {
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "✅ Users table columns:<br>";
    $requiredFields = ['first_name', 'last_name', 'bio', 'department', 'year_of_study', 'skills', 'interests', 'linkedin_url', 'github_url'];
    
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        $isRequired = in_array($fieldName, $requiredFields);
        $status = $isRequired ? "✅" : "ℹ️";
        echo "$status $fieldName (" . $column['Type'] . ")" . ($isRequired ? " [REQUIRED FOR EDIT]" : "") . "<br>";
    }
    
    echo "<br>📋 Missing fields check:<br>";
    foreach ($requiredFields as $field) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $field) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            echo "❌ Missing: $field<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database structure error: " . $e->getMessage() . "<br>";
}

// Test 7: Test form submission (if POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>7. Testing Form Submission</h3>";
    echo "✅ POST request received<br>";
    echo "📋 POST data:<br>";
    foreach ($_POST as $key => $value) {
        echo "- $key: " . htmlspecialchars($value) . "<br>";
    }
    
    // Test update
    try {
        $updateData = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'bio' => $_POST['bio'] ?? '',
            'department' => $_POST['department'] ?? '',
            'year_of_study' => $_POST['year_of_study'] ?? '',
            'skills' => $_POST['skills'] ?? '',
            'interests' => $_POST['interests'] ?? '',
            'linkedin_url' => $_POST['linkedin_url'] ?? '',
            'github_url' => $_POST['github_url'] ?? ''
        ];
        
        echo "📋 Update data prepared<br>";
        
        $result = $userModel->updateProfile($userId, $updateData);
        if ($result) {
            echo "✅ Profile update successful<br>";
        } else {
            echo "❌ Profile update failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Update error: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Debug Complete</h3>";
echo "<a href='/edit-profile.php'>Try Edit Profile</a> | ";
echo "<a href='/profile.php'>View Profile</a> | ";
echo "<a href='/dashboard.php'>Back to Dashboard</a>";

// Simple test form
echo "<h3>Test Form</h3>";
echo '<form method="POST">';
echo '<input type="text" name="first_name" placeholder="First Name" value="' . ($currentUser['first_name'] ?? '') . '"><br><br>';
echo '<input type="text" name="last_name" placeholder="Last Name" value="' . ($currentUser['last_name'] ?? '') . '"><br><br>';
echo '<textarea name="bio" placeholder="Bio">' . ($currentUser['bio'] ?? '') . '</textarea><br><br>';
echo '<input type="text" name="department" placeholder="Department" value="' . ($currentUser['department'] ?? '') . '"><br><br>';
echo '<select name="year_of_study">';
echo '<option value="1st"' . (($currentUser['year_of_study'] ?? '') === '1st' ? ' selected' : '') . '>1st Year</option>';
echo '<option value="2nd"' . (($currentUser['year_of_study'] ?? '') === '2nd' ? ' selected' : '') . '>2nd Year</option>';
echo '<option value="3rd"' . (($currentUser['year_of_study'] ?? '') === '3rd' ? ' selected' : '') . '>3rd Year</option>';
echo '<option value="4th"' . (($currentUser['year_of_study'] ?? '') === '4th' ? ' selected' : '') . '>4th Year</option>';
echo '<option value="graduate"' . (($currentUser['year_of_study'] ?? '') === 'graduate' ? ' selected' : '') . '>Graduate</option>';
echo '</select><br><br>';
echo '<input type="text" name="skills" placeholder="Skills" value="' . ($currentUser['skills'] ?? '') . '"><br><br>';
echo '<input type="text" name="interests" placeholder="Interests" value="' . ($currentUser['interests'] ?? '') . '"><br><br>';
echo '<input type="url" name="linkedin_url" placeholder="LinkedIn URL" value="' . ($currentUser['linkedin_url'] ?? '') . '"><br><br>';
echo '<input type="url" name="github_url" placeholder="GitHub URL" value="' . ($currentUser['github_url'] ?? '') . '"><br><br>';
echo '<button type="submit">Test Update</button>';
echo '</form>';
?>