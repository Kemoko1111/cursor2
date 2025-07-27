<?php
require_once '../config/app.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Only mentees can send requests
if ($userRole !== 'mentee') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only mentees can send mentorship requests']);
    exit;
}

try {
    $mentorId = (int)($_POST['mentor_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $goals = trim($_POST['goals'] ?? '');
    $meetingType = $_POST['meeting_type'] ?? 'online';
    $durationWeeks = (int)($_POST['duration_weeks'] ?? 12);

    if (!$mentorId) {
        echo json_encode(['success' => false, 'message' => 'Mentor ID is required']);
        exit;
    }

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    if (empty($goals)) {
        echo json_encode(['success' => false, 'message' => 'Goals are required']);
        exit;
    }

    $userModel = new User();
    $database = new Database();
    $conn = $database->getConnection();

    // Verify mentor exists and is actually a mentor
    $mentor = $userModel->getUserById($mentorId);
    if (!$mentor || $mentor['role'] !== 'mentor') {
        echo json_encode(['success' => false, 'message' => 'Invalid mentor']);
        exit;
    }

    // Check if mentee already has an active mentor
    $activeMentorQuery = "SELECT COUNT(*) FROM mentorships WHERE mentee_id = :mentee_id AND status = 'active'";
    $activeStmt = $conn->prepare($activeMentorQuery);
    $activeStmt->bindParam(':mentee_id', $userId);
    $activeStmt->execute();
    if ($activeStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have an active mentorship. Please complete or cancel your current mentorship before requesting a new one.']);
        exit;
    }

    // Check if mentor has reached maximum capacity (3 mentees)
    $capacityQuery = "SELECT COUNT(*) FROM mentorships WHERE mentor_id = :mentor_id AND status = 'active'";
    $capacityStmt = $conn->prepare($capacityQuery);
    $capacityStmt->bindParam(':mentor_id', $mentorId);
    $capacityStmt->execute();
    if ($capacityStmt->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'This mentor has reached their maximum capacity of mentees.']);
        exit;
    }

    // Check if there's already a pending request
    $pendingQuery = "SELECT COUNT(*) FROM mentorship_requests WHERE mentee_id = :mentee_id AND mentor_id = :mentor_id AND status = 'pending'";
    $pendingStmt = $conn->prepare($pendingQuery);
    $pendingStmt->bindParam(':mentee_id', $userId);
    $pendingStmt->bindParam(':mentor_id', $mentorId);
    $pendingStmt->execute();
    if ($pendingStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request with this mentor.']);
        exit;
    }

    // Insert the request directly
    $insertQuery = "INSERT INTO mentorship_requests (mentee_id, mentor_id, message, preferred_meeting_type, goals, duration_weeks, status) 
                    VALUES (:mentee_id, :mentor_id, :message, :meeting_type, :goals, :duration, 'pending')";
    
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(':mentee_id', $userId);
    $insertStmt->bindParam(':mentor_id', $mentorId);
    $insertStmt->bindParam(':message', $message);
    $insertStmt->bindParam(':meeting_type', $meetingType);
    $insertStmt->bindParam(':goals', $goals);
    $insertStmt->bindParam(':duration', $durationWeeks);

    if ($insertStmt->execute()) {
        $requestId = $conn->lastInsertId();
        
        // Create notification
        $notificationQuery = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                             VALUES (:user_id, 'mentorship_request', 'New Mentorship Request', 'You have received a new mentorship request.', :related_id)";
        $notificationStmt = $conn->prepare($notificationQuery);
        $notificationStmt->bindParam(':user_id', $mentorId);
        $notificationStmt->bindParam(':related_id', $requestId);
        $notificationStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Mentorship request sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request. Please try again.']);
    }

} catch (Exception $e) {
    error_log('Mentorship request error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>