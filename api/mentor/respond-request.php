<?php
// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Simple test endpoint
if (isset($_GET['test'])) {
    sendJsonResponse(['success' => true, 'message' => 'API is working']);
}

// Debug endpoint
if (isset($_GET['debug'])) {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $debug = [
            'session_user_id' => $_SESSION['user_id'] ?? 'not set',
            'session_user_role' => $_SESSION['user_role'] ?? 'not set',
            'database_connected' => 'yes',
            'php_version' => PHP_VERSION
        ];
        
        sendJsonResponse(['success' => true, 'debug' => $debug]);
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mentor') {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized - Please log in as a mentor'], 401);
}

$userId = $_SESSION['user_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    // Validate required fields
    if (!isset($input['request_id']) || !isset($input['action'])) {
        sendJsonResponse(['success' => false, 'message' => 'Missing required fields: request_id and action'], 400);
    }
    
    $requestId = (int)$input['request_id'];
    $action = $input['action'];

    if (!in_array($action, ['accepted', 'rejected'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action. Must be "accepted" or "rejected"'], 400);
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the request
    $stmt = $pdo->prepare("SELECT * FROM mentorship_requests WHERE id = ? AND mentor_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        sendJsonResponse(['success' => false, 'message' => 'Request not found or already processed'], 404);
    }

    if ($action === 'accepted') {
        // Check if mentor has reached capacity (max 3 mentees)
        $capacityStmt = $pdo->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'active'");
        $capacityStmt->execute([$userId]);
        $currentMentees = $capacityStmt->fetchColumn();
        
        if ($currentMentees >= 3) {
            sendJsonResponse(['success' => false, 'message' => 'You have reached your maximum capacity of 3 mentees'], 400);
        }

        // Check if mentee already has an active mentorship
        $menteeActiveStmt = $pdo->prepare("SELECT COUNT(*) FROM mentorships WHERE mentee_id = ? AND status = 'active'");
        $menteeActiveStmt->execute([$request['mentee_id']]);
        $menteeActiveCount = $menteeActiveStmt->fetchColumn();
        
        if ($menteeActiveCount > 0) {
            sendJsonResponse(['success' => false, 'message' => 'This mentee already has an active mentorship'], 400);
        }

        // Accept: update request, create mentorship
        $pdo->beginTransaction();
        
        try {
            // Update request status
            $updateStmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?");
            $updateResult = $updateStmt->execute([$requestId]);
            
            if (!$updateResult) {
                throw new Exception("Failed to update request status");
            }

            // Create mentorship
            $mentorshipStmt = $pdo->prepare("INSERT INTO mentorships (request_id, mentee_id, mentor_id, start_date, status, meeting_frequency) VALUES (?, ?, ?, CURDATE(), 'active', 'weekly')");
            $mentorshipResult = $mentorshipStmt->execute([$requestId, $request['mentee_id'], $userId]);
            
            if (!$mentorshipResult) {
                throw new Exception("Failed to create mentorship");
            }

            // Cancel other pending requests from this mentee
            $cancelStmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'cancelled' WHERE mentee_id = ? AND id != ? AND status = 'pending'");
            $cancelStmt->execute([$request['mentee_id'], $requestId]);

            // Create notification for mentee
            $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_accepted', 'Mentorship Request Accepted', 'Your mentorship request has been accepted! You can now start communicating with your mentor.', ?)");
            $notificationStmt->execute([$request['mentee_id'], $requestId]);

            $pdo->commit();
            sendJsonResponse(['success' => true, 'message' => 'Request accepted and mentorship started successfully.']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        
    } elseif ($action === 'rejected') {
        // Reject: update request
        $updateStmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $updateResult = $updateStmt->execute([$requestId]);
        
        if (!$updateResult) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to update request status'], 500);
        }

        // Create notification for mentee
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_rejected', 'Mentorship Request Rejected', 'Your mentorship request has been declined.', ?)");
        $notificationStmt->execute([$request['mentee_id'], $requestId]);

        sendJsonResponse(['success' => true, 'message' => 'Request rejected successfully.']);
    }

} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>