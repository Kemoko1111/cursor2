<?php
require_once 'config/database.php';

class Message {
    private $conn;
    private $table = 'messages';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function sendMessage($mentorshipId, $senderId, $receiverId, $message) {
        try {
            // Verify users are part of this mentorship
            if (!$this->verifyMentorshipAccess($mentorshipId, $senderId)) {
                throw new Exception("Unauthorized access to mentorship");
            }

            $query = "INSERT INTO " . $this->table . " 
                     (mentorship_id, sender_id, receiver_id, message) 
                     VALUES (:mentorship_id, :sender_id, :receiver_id, :message)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentorship_id', $mentorshipId);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->bindParam(':receiver_id', $receiverId);
            $stmt->bindParam(':message', $message);

            if ($stmt->execute()) {
                $messageId = $this->conn->lastInsertId();

                // Create notification for receiver
                $this->createNotification($receiverId, 'new_message', 
                    'New Message', 'You have received a new message.', $messageId);

                // Send email notification
                $userModel = new User();
                $sender = $userModel->getUserById($senderId);
                $receiver = $userModel->getUserById($receiverId);
                
                $emailService = new EmailService();
                $emailService->sendNewMessageEmail($receiver, $sender, $mentorshipId);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'message' => 'Message sent successfully'
                ];
            }

            throw new Exception("Failed to send message");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getMessages($mentorshipId, $userId, $limit = 50, $offset = 0) {
        try {
            // Verify user access to mentorship
            if (!$this->verifyMentorshipAccess($mentorshipId, $userId)) {
                throw new Exception("Unauthorized access to mentorship");
            }

            $query = "SELECT m.*, 
                             u.first_name, u.last_name, u.profile_image
                      FROM " . $this->table . " m
                      JOIN users u ON m.sender_id = u.id
                      WHERE m.mentorship_id = :mentorship_id
                      ORDER BY m.created_at ASC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentorship_id', $mentorshipId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $messages = $stmt->fetchAll();

            // Mark messages as read for the current user
            $this->markMessagesAsRead($mentorshipId, $userId);

            return [
                'success' => true,
                'messages' => $messages
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " 
                 WHERE receiver_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }

    public function getConversations($userId) {
        $query = "SELECT DISTINCT m.mentorship_id,
                         mn.mentor_id, mn.mentee_id,
                         CASE 
                             WHEN mn.mentor_id = :user_id THEN mentee.first_name
                             ELSE mentor.first_name
                         END as other_first_name,
                         CASE 
                             WHEN mn.mentor_id = :user_id THEN mentee.last_name
                             ELSE mentor.last_name
                         END as other_last_name,
                         CASE 
                             WHEN mn.mentor_id = :user_id THEN mentee.profile_image
                             ELSE mentor.profile_image
                         END as other_profile_image,
                         (SELECT message FROM " . $this->table . " m2 
                          WHERE m2.mentorship_id = m.mentorship_id 
                          ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                         (SELECT created_at FROM " . $this->table . " m3 
                          WHERE m3.mentorship_id = m.mentorship_id 
                          ORDER BY m3.created_at DESC LIMIT 1) as last_message_time,
                         (SELECT COUNT(*) FROM " . $this->table . " m4 
                          WHERE m4.mentorship_id = m.mentorship_id 
                          AND m4.receiver_id = :user_id AND m4.is_read = 0) as unread_count
                  FROM " . $this->table . " m
                  JOIN mentorships mn ON m.mentorship_id = mn.id
                  JOIN users mentor ON mn.mentor_id = mentor.id
                  JOIN users mentee ON mn.mentee_id = mentee.id
                  WHERE (mn.mentor_id = :user_id OR mn.mentee_id = :user_id) 
                    AND mn.status = 'active'
                  ORDER BY last_message_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markMessagesAsRead($mentorshipId, $userId) {
        $query = "UPDATE " . $this->table . " 
                 SET is_read = 1 
                 WHERE mentorship_id = :mentorship_id AND receiver_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentorship_id', $mentorshipId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }

    public function deleteMessage($messageId, $userId) {
        try {
            // Verify user is the sender of the message
            $query = "SELECT sender_id FROM " . $this->table . " WHERE id = :message_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $messageId);
            $stmt->execute();
            
            $message = $stmt->fetch();
            if (!$message || $message['sender_id'] != $userId) {
                throw new Exception("Unauthorized to delete this message");
            }

            // Delete the message
            $deleteQuery = "DELETE FROM " . $this->table . " WHERE id = :message_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':message_id', $messageId);

            if ($deleteStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Message deleted successfully'
                ];
            }

            throw new Exception("Failed to delete message");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function verifyMentorshipAccess($mentorshipId, $userId) {
        $query = "SELECT COUNT(*) FROM mentorships 
                 WHERE id = :mentorship_id 
                 AND (mentor_id = :user_id OR mentee_id = :user_id) 
                 AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentorship_id', $mentorshipId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    private function createNotification($userId, $type, $title, $message, $relatedId = null) {
        try {
            $query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                     VALUES (:user_id, :type, :title, :message, :related_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':related_id', $relatedId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            // Don't fail if notifications table doesn't exist
            return true;
        }
    }

    public function getMentorshipMessages($mentorshipId) {
        $query = "SELECT m.*, u.first_name, u.last_name
                  FROM " . $this->table . " m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.mentorship_id = :mentorship_id
                  ORDER BY m.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentorship_id', $mentorshipId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getDirectMessages($userId1, $userId2) {
        $query = "SELECT m.*, u.first_name, u.last_name
                  FROM " . $this->table . " m
                  JOIN users u ON m.sender_id = u.id
                  WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
                     OR (m.sender_id = :user2 AND m.receiver_id = :user1)
                  ORDER BY m.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function sendMentorshipMessage($senderId, $mentorshipId, $content) {
        try {
            // Get mentorship details to find receiver
            $mentorshipQuery = "SELECT mentor_id, mentee_id FROM mentorships WHERE id = :mentorship_id";
            $stmt = $this->conn->prepare($mentorshipQuery);
            $stmt->bindParam(':mentorship_id', $mentorshipId);
            $stmt->execute();
            $mentorship = $stmt->fetch();
            
            if (!$mentorship) {
                return false;
            }
            
            $receiverId = ($mentorship['mentor_id'] == $senderId) ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
            
            $query = "INSERT INTO " . $this->table . " 
                     (mentorship_id, sender_id, receiver_id, content, created_at) 
                     VALUES (:mentorship_id, :sender_id, :receiver_id, :content, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentorship_id', $mentorshipId);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->bindParam(':receiver_id', $receiverId);
            $stmt->bindParam(':content', $content);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Send mentorship message error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendDirectMessage($senderId, $receiverId, $content) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (sender_id, receiver_id, content, created_at) 
                     VALUES (:sender_id, :receiver_id, :content, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->bindParam(':receiver_id', $receiverId);
            $stmt->bindParam(':content', $content);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Send direct message error: ' . $e->getMessage());
            return false;
        }
    }

    public function markMentorshipMessagesAsRead($mentorshipId, $userId) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET is_read = 1 
                     WHERE mentorship_id = :mentorship_id AND receiver_id = :user_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentorship_id', $mentorshipId);
            $stmt->bindParam(':user_id', $userId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Mark messages as read error: ' . $e->getMessage());
            return false;
        }
    }

    public function markDirectMessagesAsRead($userId, $otherUserId) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET is_read = 1 
                     WHERE sender_id = :other_user AND receiver_id = :user_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':other_user', $otherUserId);
            $stmt->bindParam(':user_id', $userId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Mark direct messages as read error: ' . $e->getMessage());
            return false;
        }
    }
}
?>