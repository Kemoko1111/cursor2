<?php
require_once '../config/app.php';
require_once '../models/Notification.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$notificationModel = new Notification();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get notifications
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'unread':
                $notifications = $notificationModel->getUnreadNotifications($userId);
                echo json_encode(['success' => true, 'notifications' => $notifications]);
                break;
                
            case 'count':
                $count = $notificationModel->getUnreadCount($userId);
                echo json_encode(['success' => true, 'count' => $count]);
                break;
                
            default:
                $notifications = $notificationModel->getAllNotifications($userId);
                echo json_encode(['success' => true, 'notifications' => $notifications]);
                break;
        }
        break;
        
    case 'POST':
        // Mark notification as read
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $notificationId = $data['notification_id'] ?? null;
                if ($notificationId) {
                    $success = $notificationModel->markAsRead($notificationId, $userId);
                    echo json_encode(['success' => $success]);
                } else {
                    echo json_encode(['error' => 'Notification ID required']);
                }
                break;
                
            case 'mark_all_read':
                $success = $notificationModel->markAllAsRead($userId);
                echo json_encode(['success' => $success]);
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
                break;
        }
        break;
        
    case 'DELETE':
        // Delete notification
        $data = json_decode(file_get_contents('php://input'), true);
        $notificationId = $data['notification_id'] ?? null;
        
        if ($notificationId) {
            $success = $notificationModel->deleteNotification($notificationId, $userId);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Notification ID required']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>