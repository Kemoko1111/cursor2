<?php
// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup</h2>";
    
    // Check and create messages table
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p>Creating messages table...</p>";
        $createMessages = "CREATE TABLE messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            mentorship_id INT NOT NULL,
            content TEXT NOT NULL,
            message_type ENUM('text', 'resource') DEFAULT 'text',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createMessages);
        echo "<p style='color: green;'>✓ Messages table created successfully</p>";
    } else {
        echo "<p style='color: blue;'>✓ Messages table already exists</p>";
    }
    
    // Check and create mentorships table
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mentorships'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p>Creating mentorships table...</p>";
        $createMentorships = "CREATE TABLE mentorships (
            id INT PRIMARY KEY AUTO_INCREMENT,
            request_id INT NOT NULL,
            mentee_id INT NOT NULL,
            mentor_id INT NOT NULL,
            start_date DATE NOT NULL,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            meeting_frequency VARCHAR(50) DEFAULT 'weekly',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createMentorships);
        echo "<p style='color: green;'>✓ Mentorships table created successfully</p>";
    } else {
        echo "<p style='color: blue;'>✓ Mentorships table already exists</p>";
    }
    
    // Check and create mentorship_requests table
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mentorship_requests'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p>Creating mentorship_requests table...</p>";
        $createRequests = "CREATE TABLE mentorship_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            mentee_id INT NOT NULL,
            mentor_id INT NOT NULL,
            message TEXT,
            duration_weeks INT DEFAULT 12,
            preferred_meeting_type ENUM('video_call', 'voice_call', 'chat', 'in_person') DEFAULT 'video_call',
            status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL
        )";
        $pdo->exec($createRequests);
        echo "<p style='color: green;'>✓ Mentorship requests table created successfully</p>";
    } else {
        echo "<p style='color: blue;'>✓ Mentorship requests table already exists</p>";
    }
    
    // Check and create notifications table
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p>Creating notifications table...</p>";
        $createNotifications = "CREATE TABLE notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_id INT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createNotifications);
        echo "<p style='color: green;'>✓ Notifications table created successfully</p>";
    } else {
        echo "<p style='color: blue;'>✓ Notifications table already exists</p>";
    }
    
    echo "<h3 style='color: green;'>✓ Database setup completed successfully!</h3>";
    echo "<p><a href='/messages.php'>Go to Messages</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Database setup failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>