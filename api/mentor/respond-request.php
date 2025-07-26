<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Mentorship.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mentor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['request_id']) || !isset($input['action'])) {
        throw new Exception("Missing required fields");
    }
    $requestId = (int)$input['request_id'];
    $action = $input['action'];

    if (!in_array($action, ['accepted', 'rejected'])) {
        throw new Exception("Invalid action");
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Get the request
    $stmt = $conn->prepare("SELECT * FROM mentorship_requests WHERE id = :id AND mentor_id = :mentor_id AND status = 'pending'");
    $stmt->bindParam(':id', $requestId);
    $stmt->bindParam(':mentor_id', $userId);
    $stmt->execute();
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception("Request not found or already processed");
    }

    if ($action === 'accepted') {
        // Check if mentor has reached capacity (max 3 mentees)
        $capacityStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = :mentor_id AND status = 'active'");
        $capacityStmt->bindParam(':mentor_id', $userId);
        $capacityStmt->execute();
        if ($capacityStmt->fetchColumn() >= 3) {
            throw new Exception("You have reached your maximum capacity of mentees");
        }

        // Check if mentee already has an active mentorship
        $menteeActiveStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentee_id = :mentee_id AND status = 'active'");
        $menteeActiveStmt->bindParam(':mentee_id', $request['mentee_id']);
        $menteeActiveStmt->execute();
        if ($menteeActiveStmt->fetchColumn() > 0) {
            throw new Exception("This mentee already has an active mentorship");
        }

        // Accept: update request, create mentorship
        $conn->beginTransaction();
        
        try {
            // Update request status
            $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'accepted', responded_at = NOW() WHERE id = :id");
            $updateStmt->bindParam(':id', $requestId);
            $updateStmt->execute();

            // Create mentorship
            $mentorshipStmt = $conn->prepare("INSERT INTO mentorships (request_id, mentee_id, mentor_id, start_date, status, meeting_frequency) VALUES (:request_id, :mentee_id, :mentor_id, CURDATE(), 'active', 'weekly')");
            $mentorshipStmt->bindParam(':request_id', $requestId);
            $mentorshipStmt->bindParam(':mentee_id', $request['mentee_id']);
            $mentorshipStmt->bindParam(':mentor_id', $userId);
            $mentorshipStmt->execute();

            // Cancel other pending requests from this mentee
            $cancelStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'cancelled' WHERE mentee_id = :mentee_id AND id != :request_id AND status = 'pending'");
            $cancelStmt->bindParam(':mentee_id', $request['mentee_id']);
            $cancelStmt->bindParam(':request_id', $requestId);
            $cancelStmt->execute();

            // Create notification for mentee
            $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (:user_id, 'request_accepted', 'Mentorship Request Accepted', 'Your mentorship request has been accepted! You can now start communicating with your mentor.', :related_id)");
            $notificationStmt->bindParam(':user_id', $request['mentee_id']);
            $notificationStmt->bindParam(':related_id', $requestId);
            $notificationStmt->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request accepted and mentorship started.']);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } elseif ($action === 'rejected') {
        // Reject: update request
        $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = :id");
        $updateStmt->bindParam(':id', $requestId);
        $updateStmt->execute();

        // Create notification for mentee
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (:user_id, 'request_rejected', 'Mentorship Request Rejected', 'Your mentorship request has been declined.', :related_id)");
        $notificationStmt->bindParam(':user_id', $request['mentee_id']);
        $notificationStmt->bindParam(':related_id', $requestId);
        $notificationStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    }
} catch (Exception $e) {
    error_log('Mentor respond request error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>