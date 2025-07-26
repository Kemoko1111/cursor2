<?php
require_once '../config/app.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Mentorship.php';

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
    $mentorshipModel = new Mentorship();

    // Verify mentor exists and is actually a mentor
    $mentor = $userModel->getUserById($mentorId);
    if (!$mentor || $mentor['role'] !== 'mentor') {
        echo json_encode(['success' => false, 'message' => 'Invalid mentor']);
        exit;
    }

    // Check if mentee already has an active mentor
    if ($mentorshipModel->hasActiveMentor($userId)) {
        echo json_encode(['success' => false, 'message' => 'You already have an active mentorship. Please complete or cancel your current mentorship before requesting a new one.']);
        exit;
    }

    // Check if mentor has reached maximum capacity
    if ($mentorshipModel->mentorAtCapacity($mentorId)) {
        echo json_encode(['success' => false, 'message' => 'This mentor has reached their maximum capacity of mentees.']);
        exit;
    }

    // Check if there's already a pending request
    if ($mentorshipModel->hasPendingRequest($userId, $mentorId)) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request with this mentor.']);
        exit;
    }

    // Send the request
    $requestData = [
        'message' => $message,
        'meeting_type' => $meetingType,
        'goals' => $goals,
        'duration_weeks' => $durationWeeks
    ];

    $result = $mentorshipModel->sendRequest($userId, $mentorId, $requestData);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Mentorship request sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }

} catch (Exception $e) {
    error_log('Mentorship request error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>