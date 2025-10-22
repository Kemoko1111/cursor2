<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'User not logged in'], 401);
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
}

// Validate required fields
$messageContent = trim($input['message_content'] ?? '');
$mentorshipId = $input['mentorship_id'] ?? null;

if (empty($messageContent)) {
    sendJsonResponse(['success' => false, 'message' => 'Message content is required'], 400);
}

if (!$mentorshipId) {
    sendJsonResponse(['success' => false, 'message' => 'Mentorship ID is required'], 400);
}

$userId = $_SESSION['user_id'];

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify mentorship exists and user is part of it
    $mentorshipStmt = $pdo->prepare("SELECT mentor_id, mentee_id FROM mentorships WHERE id = ? AND (mentor_id = ? OR mentee_id = ?) AND status = 'active'");
    $mentorshipStmt->execute([$mentorshipId, $userId, $userId]);
    $mentorship = $mentorshipStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mentorship) {
        sendJsonResponse(['success' => false, 'message' => 'Mentorship not found or not active'], 404);
    }
    
    // Determine receiver
    $receiverId = $mentorship['mentor_id'] == $userId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
    
    // Insert message using the correct column name 'message' instead of 'content'
    $query = "INSERT INTO messages (sender_id, receiver_id, mentorship_id, message, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$userId, $receiverId, $mentorshipId, $messageContent]);
    
    if ($result) {
        sendJsonResponse([
            'success' => true, 
            'message' => 'Message sent successfully',
            'data' => [
                'message_id' => $pdo->lastInsertId(),
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'mentorship_id' => $mentorshipId,
                'message' => $messageContent,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Failed to send message'], 500);
    }
    
} catch (Exception $e) {
    error_log("Error sending message: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>