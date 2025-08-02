<?php
require_once __DIR__ . '/../config/app.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Create a new notification
     */
    public function createNotification($userId, $type, $title, $message, $relatedId = null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                VALUES (:user_id, :type, :title, :message, :related_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId
        ]);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications($userId, $limit = 10) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id AND is_read = FALSE 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all notifications for a user
     */
    public function getAllNotifications($userId, $limit = 20) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = :user_id AND is_read = FALSE";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notificationId, $userId) {
        $sql = "DELETE FROM notifications 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId
        ]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = :user_id AND is_read = FALSE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Create mentorship request notification
     */
    public function createMentorshipRequestNotification($mentorId, $menteeId, $requestId) {
        // Get mentee details
        $userModel = new User();
        $mentee = $userModel->getUserById($menteeId);
        
        $title = "New Mentorship Request";
        $message = "{$mentee['first_name']} {$mentee['last_name']} has sent you a mentorship request.";
        
        return $this->createNotification($mentorId, 'mentorship_request', $title, $message, $requestId);
    }

    /**
     * Create request accepted notification
     */
    public function createRequestAcceptedNotification($menteeId, $mentorId, $requestId) {
        // Get mentor details
        $userModel = new User();
        $mentor = $userModel->getUserById($mentorId);
        
        $title = "Mentorship Request Accepted";
        $message = "{$mentor['first_name']} {$mentor['last_name']} has accepted your mentorship request!";
        
        return $this->createNotification($menteeId, 'request_accepted', $title, $message, $requestId);
    }

    /**
     * Create request rejected notification
     */
    public function createRequestRejectedNotification($menteeId, $mentorId, $requestId) {
        // Get mentor details
        $userModel = new User();
        $mentor = $userModel->getUserById($mentorId);
        
        $title = "Mentorship Request Update";
        $message = "{$mentor['first_name']} {$mentor['last_name']} has declined your mentorship request.";
        
        return $this->createNotification($menteeId, 'request_rejected', $title, $message, $requestId);
    }

    /**
     * Create new message notification
     */
    public function createMessageNotification($receiverId, $senderId, $mentorshipId, $messageId) {
        // Get sender details
        $userModel = new User();
        $sender = $userModel->getUserById($senderId);
        
        $title = "New Message";
        $message = "{$sender['first_name']} {$sender['last_name']} sent you a new message.";
        
        return $this->createNotification($receiverId, 'new_message', $title, $message, $messageId);
    }

    /**
     * Create system announcement notification
     */
    public function createSystemAnnouncement($userId, $title, $message) {
        return $this->createNotification($userId, 'system_announcement', $title, $message);
    }

    /**
     * Get notification with related data
     */
    public function getNotificationWithData($notificationId, $userId) {
        $sql = "SELECT n.*, 
                       u.first_name, u.last_name, u.profile_image,
                       mr.status as request_status
                FROM notifications n
                LEFT JOIN users u ON (n.type = 'mentorship_request' OR n.type = 'request_accepted' OR n.type = 'request_rejected') 
                    AND u.id = (SELECT mentee_id FROM mentorship_requests WHERE id = n.related_id)
                LEFT JOIN mentorship_requests mr ON n.related_id = mr.id
                WHERE n.id = :id AND n.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Format notification time
     */
    public function formatTimeAgo($timestamp) {
        $time = time() - strtotime($timestamp);
        
        if ($time < 60) {
            return 'Just now';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time < 604800) {
            $days = floor($time / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', strtotime($timestamp));
        }
    }

    /**
     * Get notification icon based on type
     */
    public function getNotificationIcon($type) {
        $icons = [
            'mentorship_request' => 'fas fa-user-plus',
            'request_accepted' => 'fas fa-check-circle',
            'request_rejected' => 'fas fa-times-circle',
            'new_message' => 'fas fa-comments',
            'system_announcement' => 'fas fa-bullhorn'
        ];
        
        return $icons[$type] ?? 'fas fa-bell';
    }

    /**
     * Get notification color based on type
     */
    public function getNotificationColor($type) {
        $colors = [
            'mentorship_request' => 'primary',
            'request_accepted' => 'success',
            'request_rejected' => 'danger',
            'new_message' => 'info',
            'system_announcement' => 'warning'
        ];
        
        return $colors[$type] ?? 'secondary';
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType($userId, $types, $limit = 20, $offset = 0) {
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? AND type IN ($placeholders)
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params = array_merge([$userId], $types, [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total notification count
     */
    public function getTotalCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Get notification count by type
     */
    public function getCountByType($userId, $type) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $type]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Get recent notifications for dashboard
     */
    public function getRecentNotifications($userId, $limit = 5) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clean old notifications (older than 30 days)
     */
    public function cleanOldNotifications($days = 30) {
        $sql = "DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND is_read = TRUE";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$days]);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId) {
        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    COUNT(CASE WHEN is_read = FALSE THEN 1 END) as unread_count
                FROM notifications 
                WHERE user_id = ? 
                GROUP BY type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>