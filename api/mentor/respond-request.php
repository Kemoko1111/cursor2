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
    error_log("Mentor respond request input: " . json_encode($input));
    
    if (!isset($input['request_id']) || !isset($input['action'])) {
        throw new Exception("Missing required fields");
    }
    $requestId = (int)$input['request_id'];
    $action = $input['action'];

    error_log("Processing request ID: $requestId, Action: $action, User ID: $userId");

    if (!in_array($action, ['accepted', 'rejected'])) {
        throw new Exception("Invalid action");
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Get the request
    $stmt = $conn->prepare("SELECT * FROM mentorship_requests WHERE id = ? AND mentor_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    error_log("Request data: " . json_encode($request));

    if (!$request) {
        throw new Exception("Request not found or already processed");
    }

    if ($action === 'accepted') {
        error_log("Processing acceptance for request ID: $requestId");
        
        // Check if mentor has reached capacity (max 3 mentees)
        $capacityStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'active'");
        $capacityStmt->execute([$userId]);
        $currentMentees = $capacityStmt->fetchColumn();
        error_log("Current mentees count: $currentMentees");
        
        if ($currentMentees >= 3) {
            throw new Exception("You have reached your maximum capacity of mentees");
        }

        // Check if mentee already has an active mentorship
        $menteeActiveStmt = $conn->prepare("SELECT COUNT(*) FROM mentorships WHERE mentee_id = ? AND status = 'active'");
        $menteeActiveStmt->execute([$request['mentee_id']]);
        $menteeActiveCount = $menteeActiveStmt->fetchColumn();
        error_log("Mentee active mentorships count: $menteeActiveCount");
        
        if ($menteeActiveCount > 0) {
            throw new Exception("This mentee already has an active mentorship");
        }

        // Accept: update request, create mentorship
        error_log("Starting transaction for acceptance");
        $conn->beginTransaction();
        
        try {
            // Update request status
            error_log("Updating request status to accepted");
            $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?");
            $updateResult = $updateStmt->execute([$requestId]);
            error_log("Request update result: " . ($updateResult ? 'success' : 'failed'));

            // Create mentorship
            error_log("Creating mentorship record");
            $mentorshipStmt = $conn->prepare("INSERT INTO mentorships (request_id, mentee_id, mentor_id, start_date, status, meeting_frequency) VALUES (?, ?, ?, CURDATE(), 'active', 'weekly')");
            $mentorshipResult = $mentorshipStmt->execute([$requestId, $request['mentee_id'], $userId]);
            error_log("Mentorship creation result: " . ($mentorshipResult ? 'success' : 'failed'));

            // Cancel other pending requests from this mentee
            error_log("Cancelling other pending requests");
            $cancelStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'cancelled' WHERE mentee_id = ? AND id != ? AND status = 'pending'");
            $cancelResult = $cancelStmt->execute([$request['mentee_id'], $requestId]);
            error_log("Cancel other requests result: " . ($cancelResult ? 'success' : 'failed'));

            // Create notification for mentee
            error_log("Creating notification for mentee");
            $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_accepted', 'Mentorship Request Accepted', 'Your mentorship request has been accepted! You can now start communicating with your mentor.', ?)");
            $notificationResult = $notificationStmt->execute([$request['mentee_id'], $requestId]);
            error_log("Notification creation result: " . ($notificationResult ? 'success' : 'failed'));

            $conn->commit();
            error_log("Transaction committed successfully");
            echo json_encode(['success' => true, 'message' => 'Request accepted and mentorship started.']);
            
        } catch (Exception $e) {
            error_log("Error during transaction: " . $e->getMessage());
            $conn->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'rejected') {
        error_log("Processing rejection for request ID: $requestId");
        
        // Reject: update request
        $updateStmt = $conn->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $updateResult = $updateStmt->execute([$requestId]);
        error_log("Request rejection update result: " . ($updateResult ? 'success' : 'failed'));

        // Create notification for mentee
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_rejected', 'Mentorship Request Rejected', 'Your mentorship request has been declined.', ?)");
        $notificationResult = $notificationStmt->execute([$request['mentee_id'], $requestId]);
        error_log("Rejection notification result: " . ($notificationResult ? 'success' : 'failed'));

        echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    }

} catch (Exception $e) {
    error_log('Mentor respond request error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>