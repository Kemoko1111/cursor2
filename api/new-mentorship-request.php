<?php
session_start();
header('Content-Type: application/json');
require_once '../config/app.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$mentee_id = $_SESSION['user_id'];
$mentor_id = $_POST['mentor_id'] ?? null;
$message = trim($_POST['message'] ?? '');
$goals = trim($_POST['goals'] ?? '');
$preferred_meeting_type = $_POST['preferred_meeting_type'] ?? '';
$duration_weeks = intval($_POST['duration_weeks'] ?? 12);

if (!$mentor_id || !$message || !$goals || !$preferred_meeting_type || !$duration_weeks) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check for existing pending request
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mentorship_requests WHERE mentee_id = ? AND mentor_id = ? AND status = 'pending'");
    $stmt->execute([$mentee_id, $mentor_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request with this mentor.']);
        exit;
    }

    // Insert new request
    $stmt = $conn->prepare("INSERT INTO mentorship_requests (mentee_id, mentor_id, message, goals, preferred_meeting_type, duration_weeks) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$mentee_id, $mentor_id, $message, $goals, $preferred_meeting_type, $duration_weeks]);

    echo json_encode(['success' => true, 'message' => 'Mentorship request sent!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
exit;