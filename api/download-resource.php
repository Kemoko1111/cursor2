<?php
require_once '../config/app.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$userId = $_SESSION['user_id'];
$resourceId = intval($_GET['id'] ?? 0);

if ($resourceId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid resource ID']);
    exit();
}

// Initialize resource model
$resourceModel = new Resource();

// Attempt to download the resource
$result = $resourceModel->downloadResource($resourceId, $userId);

if ($result['success']) {
    // File download successful, serve the file
    $filePath = $result['file_path'];
    $fileName = $result['file_name'];
    $mimeType = $result['mime_type'];
    
    // Set headers for file download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: 0');
    
    // Read and output the file
    readfile($filePath);
    exit();
} else {
    // Download failed
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit();
}
?>