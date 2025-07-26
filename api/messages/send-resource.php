<?php
// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Check authentication
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        sendJsonResponse(['success' => false, 'message' => 'No files uploaded'], 400);
    }

    $mentorshipId = $_POST['mentorship_id'] ?? null;
    if (!$mentorshipId) {
        sendJsonResponse(['success' => false, 'message' => 'Mentorship ID is required'], 400);
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify mentorship exists and user is part of it
    $mentorshipStmt = $pdo->prepare("SELECT * FROM mentorships WHERE id = ? AND (mentor_id = ? OR mentee_id = ?) AND status = 'active'");
    $mentorshipStmt->execute([$mentorshipId, $userId, $userId]);
    $mentorship = $mentorshipStmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentorship) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid mentorship or not authorized'], 403);
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/resources/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedFiles = [];
    $errors = [];

    // Process each uploaded file
    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = $_FILES['files']['name'][$key];
        $fileSize = $_FILES['files']['size'][$key];
        $fileError = $_FILES['files']['error'][$key];

        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading $fileName: " . $fileError;
            continue;
        }

        // Check file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $errors[] = "$fileName is too large (max 10MB)";
            continue;
        }

        // Generate unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFileName;

        // Move uploaded file
        if (move_uploaded_file($tmpName, $filePath)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize
            ];
        } else {
            $errors[] = "Failed to save $fileName";
        }
    }

    // If there were errors, return them
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => 'Some files failed to upload: ' . implode(', ', $errors)], 400);
    }

    // If no files were successfully uploaded
    if (empty($uploadedFiles)) {
        sendJsonResponse(['success' => false, 'message' => 'No files were uploaded successfully'], 400);
    }

    // Determine receiver ID
    $receiverId = $mentorship['mentor_id'] == $userId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];

    // Insert messages for each uploaded file
    $pdo->beginTransaction();

    try {
        foreach ($uploadedFiles as $file) {
            $content = $file['original_name'] . ' (Shared Resource)';
            
            $query = "INSERT INTO messages (sender_id, receiver_id, mentorship_id, content, message_type, created_at) 
                      VALUES (?, ?, ?, ?, 'resource', NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId, $receiverId, $mentorshipId, $content]);

            // You could also store file metadata in a separate table if needed
            // For now, we'll just store the filename in the message content
        }

        $pdo->commit();
        sendJsonResponse([
            'success' => true, 
            'message' => 'Resources shared successfully',
            'files_shared' => count($uploadedFiles)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }

} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>