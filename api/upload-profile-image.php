<?php
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

try {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }

    $file = $_FILES['profile_image'];
    
    // Validate file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
        exit;
    }

    // Validate file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit;
    }

    // Validate image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        echo json_encode(['success' => false, 'message' => 'Invalid image file']);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = UPLOAD_PATH . 'profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $filename = $userId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update user profile in database
        $userModel = new User();
        
        // Get current profile image to delete old one
        $currentUser = $userModel->getUserById($userId);
        $oldImage = $currentUser['profile_image'];
        
        // Update with new image
        if ($userModel->updateProfileImage($userId, $filename)) {
            // Delete old image if it exists and is not default
            if ($oldImage && file_exists($uploadDir . $oldImage)) {
                unlink($uploadDir . $oldImage);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile image updated successfully',
                'image_url' => 'uploads/profiles/' . $filename
            ]);
        } else {
            // Remove uploaded file if database update failed
            unlink($targetPath);
            echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }

} catch (Exception $e) {
    error_log('Profile image upload error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>