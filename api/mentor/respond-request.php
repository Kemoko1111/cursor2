<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Mentorship.php';

header('Content-Type: application/json');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $stmt = $conn->prepare("SELECT * FROM mentorship_requests WHERE id = ? AND mentor_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception("Request not found or already processed");
    }

    if ($action === 'accepted') {
        // Check if mentor has reached capacity (max 3 mentees)
        $capacityStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'active'");
        $capacityStmt->execute([$userId]);
        $currentMentees = $capacityStmt->fetchColumn();
        
        if ($currentMentees >= 3) {
            throw new Exception("You have reached your maximum capacity of mentees");
        }

        // Check if mentee already has an active mentorship
        $menteeActiveStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentee_id = ? AND status = 'active'");
        $menteeActiveStmt->execute([$request['mentee_id']]);
        $menteeActiveCount = $menteeActiveStmt->fetchColumn();
        
        if ($menteeActiveCount > 0) {
            throw new Exception("This mentee already has an active mentorship");
        }

        // Accept: update request, create mentorship
        $conn->beginTransaction();
        
        try {
            // Update request status
            $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?");
            $updateResult = $updateStmt->execute([$requestId]);
            
            if (!$updateResult) {
                throw new Exception("Failed to update request status");
            }

            // Create mentorship
            $mentorshipStmt = $conn->prepare("INSERT INTO mentorships (request_id, mentee_id, mentor_id, start_date, status, meeting_frequency) VALUES (?, ?, ?, CURDATE(), 'active', 'weekly')");
            $mentorshipResult = $mentorshipStmt->execute([$requestId, $request['mentee_id'], $userId]);
            
            if (!$mentorshipResult) {
                throw new Exception("Failed to create mentorship");
            }

            // Cancel other pending requests from this mentee
            $cancelStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'cancelled' WHERE mentee_id = ? AND id != ? AND status = 'pending'");
            $cancelStmt->execute([$request['mentee_id'], $requestId]);

            // Create notification for mentee
            $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_accepted', 'Mentorship Request Accepted', 'Your mentorship request has been accepted! You can now start communicating with your mentor.', ?)");
            $notificationStmt->execute([$request['mentee_id'], $requestId]);

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request accepted and mentorship started.']);
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'rejected') {
        // Reject: update request
        $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $updateResult = $updateStmt->execute([$requestId]);
        
        if (!$updateResult) {
            throw new Exception("Failed to update request status");
        }

        // Create notification for mentee
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_rejected', 'Mentorship Request Rejected', 'Your mentorship request has been declined.', ?)");
        $notificationStmt->execute([$request['mentee_id'], $requestId]);

        echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>