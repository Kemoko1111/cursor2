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
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Simple test endpoint
if (isset($_GET['test'])) {
    echo json_encode(['success' => true, 'message' => 'API is working']);
    exit;
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
        
        echo json_encode(['success' => true, 'debug' => $debug]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mentor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in as a mentor']);
    exit;
}

$userId = $_SESSION['user_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    // Validate required fields
    if (!isset($input['request_id']) || !isset($input['action'])) {
        throw new Exception("Missing required fields: request_id and action");
    }
    
    $requestId = (int)$input['request_id'];
    $action = $input['action'];

    if (!in_array($action, ['accepted', 'rejected'])) {
        throw new Exception("Invalid action. Must be 'accepted' or 'rejected'");
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the request
    $stmt = $pdo->prepare("SELECT * FROM mentorship_requests WHERE id = ? AND mentor_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Request not found or already processed");
    }

    if ($action === 'accepted') {
        // Check if mentor has reached capacity (max 3 mentees)
        $capacityStmt = $pdo->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'active'");
        $capacityStmt->execute([$userId]);
        $currentMentees = $capacityStmt->fetchColumn();
        
        if ($currentMentees >= 3) {
            throw new Exception("You have reached your maximum capacity of 3 mentees");
        }

        // Check if mentee already has an active mentorship
        $menteeActiveStmt = $pdo->prepare("SELECT COUNT(*) FROM mentorships WHERE mentee_id = ? AND status = 'active'");
        $menteeActiveStmt->execute([$request['mentee_id']]);
        $menteeActiveCount = $menteeActiveStmt->fetchColumn();
        
        if ($menteeActiveCount > 0) {
            throw new Exception("This mentee already has an active mentorship");
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
            echo json_encode(['success' => true, 'message' => 'Request accepted and mentorship started successfully.']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'rejected') {
        // Reject: update request
        $updateStmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
        $updateResult = $updateStmt->execute([$requestId]);
        
        if (!$updateResult) {
            throw new Exception("Failed to update request status");
        }

        // Create notification for mentee
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, 'request_rejected', 'Mentorship Request Rejected', 'Your mentorship request has been declined.', ?)");
        $notificationStmt->execute([$request['mentee_id'], $requestId]);

        echo json_encode(['success' => true, 'message' => 'Request rejected successfully.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>