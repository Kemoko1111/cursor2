<?php
require_once '../models/User.php';
require_once '../models/Mentorship.php';
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

    // Send the mentorship request using the model's sendRequest method
    $result = $mentorshipModel->sendRequest($userId, $mentorId, [
        'message' => $message,
        'meeting_type' => 'online', // or get from POST if you want
        'goals' => $goals,
        'duration_weeks' => 12 // or get from POST if you want
    ]);
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    exit;

} catch (Exception $e) {
    error_log('Mentorship request error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>