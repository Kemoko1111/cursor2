<?php
session_start();

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

echo "<h2>Message Debugging</h2>";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check session
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? null;
    
    echo "<p>User ID: " . ($userId ?? 'not set') . "</p>";
    echo "<p>User Role: " . ($userRole ?? 'not set') . "</p>";
    
    if (!$userId) {
        echo "<p style='color: red;'>✗ User not logged in</p>";
        exit;
    }
    
    // Check if user exists
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green;'>✓ User exists: " . $user['first_name'] . " " . $user['last_name'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ User not found in database</p>";
    }
    
    // Check mentorships for this user
    $mentorshipStmt = $pdo->prepare("SELECT * FROM mentorships WHERE mentor_id = ? OR mentee_id = ?");
    $mentorshipStmt->execute([$userId, $userId]);
    $mentorships = $mentorshipStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Active mentorships: " . count($mentorships) . "</p>";
    
    if (empty($mentorships)) {
        echo "<p style='color: orange;'>⚠ No active mentorships found</p>";
        echo "<p>You need an active mentorship to send messages.</p>";
    } else {
        echo "<h3>Your Mentorships:</h3>";
        foreach ($mentorships as $mentorship) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Mentorship ID:</strong> " . $mentorship['id'] . "</p>";
            echo "<p><strong>Status:</strong> " . $mentorship['status'] . "</p>";
            echo "<p><strong>Mentor ID:</strong> " . $mentorship['mentor_id'] . "</p>";
            echo "<p><strong>Mentee ID:</strong> " . $mentorship['mentee_id'] . "</p>";
            echo "<p><strong>Start Date:</strong> " . $mentorship['start_date'] . "</p>";
            echo "</div>";
        }
    }
    
    // Check messages table structure
    $tableStmt = $pdo->query("DESCRIBE messages");
    $columns = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Messages Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test message insertion
    if (!empty($mentorships)) {
        $testMentorship = $mentorships[0];
        echo "<h3>Testing Message Insertion:</h3>";
        
        try {
            $testStmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, mentorship_id, content, message_type, created_at) VALUES (?, ?, ?, ?, 'text', NOW())");
            $result = $testStmt->execute([$userId, $userId, $testMentorship['id'], 'Test message']);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Test message inserted successfully</p>";
                
                // Clean up test message
                $deleteStmt = $pdo->prepare("DELETE FROM messages WHERE content = 'Test message' AND sender_id = ?");
                $deleteStmt->execute([$userId]);
                echo "<p style='color: blue;'>✓ Test message cleaned up</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to insert test message</p>";
                echo "<p>Error: " . implode(", ", $testStmt->errorInfo()) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Exception during test: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Next Steps:</h3>";
    if (empty($mentorships)) {
        echo "<p style='color: orange;'>You need to:</p>";
        echo "<ol>";
        echo "<li>Have a mentee send you a mentorship request</li>";
        echo "<li>Accept the request to create an active mentorship</li>";
        echo "<li>Then you can send messages</li>";
        echo "</ol>";
        echo "<p><a href='/mentor-requests.php'>Check for pending requests</a></p>";
    } else {
        echo "<p style='color: green;'>✓ You have active mentorships and can send messages</p>";
        echo "<p><a href='/messages.php'>Go to Messages</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>