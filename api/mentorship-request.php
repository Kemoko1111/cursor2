<?php
require_once '../config/app.php';
require_once __DIR__ . '/../middleware/auth.php';

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

    if (!$mentorId) {
        echo json_encode(['success' => false, 'message' => 'Mentor ID is required']);
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

    // Check if request already exists
    $existingRequest = $mentorshipModel->getExistingRequest($userId, $mentorId);
    if ($existingRequest) {
        if ($existingRequest['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'You already have a pending request with this mentor']);
        } else {
            echo json_encode(['success' => false, 'message' => 'You already have a request with this mentor']);
        }
        exit;
    }

    // Check if mentorship already exists
    $existingMentorship = $mentorshipModel->getActiveMentorship($userId, $mentorId);
    if ($existingMentorship) {
        echo json_encode(['success' => false, 'message' => 'You already have an active mentorship with this mentor']);
        exit;
    }

    // Create the request
    $requestData = [
        'mentee_id' => $userId,
        'mentor_id' => $mentorId,
        'message' => $message,
        'goals' => $goals,
        'status' => 'pending'
    ];

    $requestId = $mentorshipModel->createRequest($requestData);
    
    if ($requestId) {
        echo json_encode(['success' => true, 'message' => 'Mentorship request sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request. Please try again.']);
    }

} catch (Exception $e) {
    error_log('Mentorship request error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>