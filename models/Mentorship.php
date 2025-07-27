<?php
require_once 'config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

class Mentorship {
    private $conn;
    private $requestTable = 'mentorship_requests';
    private $mentorshipTable = 'mentorships';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function sendRequest($menteeId, $mentorId, $requestData) {
        try {
            // Check if mentee already has an active mentor
            if ($this->hasActiveMentor($menteeId)) {
                throw new Exception("You already have an active mentorship. Please complete or cancel your current mentorship before requesting a new one.");
            }

            // Check if mentor has reached maximum mentees
            if ($this->mentorAtCapacity($mentorId)) {
                throw new Exception("This mentor has reached their maximum capacity of mentees.");
            }

            // Check if there's already a pending request between these users
            if ($this->hasPendingRequest($menteeId, $mentorId)) {
                throw new Exception("You already have a pending request with this mentor.");
            }

            $query = "INSERT INTO " . $this->requestTable . " 
                     (mentee_id, mentor_id, message, preferred_meeting_type, goals, duration_weeks) 
                     VALUES (:mentee_id, :mentor_id, :message, :meeting_type, :goals, :duration)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':message', $requestData['message']);
            $stmt->bindParam(':meeting_type', $requestData['meeting_type']);
            $stmt->bindParam(':goals', $requestData['goals']);
            $stmt->bindParam(':duration', $requestData['duration_weeks']);

            if ($stmt->execute()) {
                $requestId = $this->conn->lastInsertId();
                
                // Send notification to mentor
                $this->createNotification($mentorId, 'mentorship_request', 
                    'New Mentorship Request', 
                    'You have received a new mentorship request.', 
                    $requestId);

                // Try to send email notification (but don't fail if it doesn't work)
                try {
                    $userModel = new User();
                    $mentor = $userModel->getUserById($mentorId);
                    $mentee = $userModel->getUserById($menteeId);
                    
                    $emailService = new EmailService();
                    $emailService->sendMentorshipRequestEmail($mentor, $mentee, $requestId);
                } catch (Exception $e) {
                    // Log email error but don't fail the request
                    error_log('Email notification failed: ' . $e->getMessage());
                }

                return [
                    'success' => true,
                    'request_id' => $requestId,
                    'message' => 'Mentorship request sent successfully'
                ];
            }

            throw new Exception("Failed to send mentorship request");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function respondToRequest($requestId, $mentorId, $response, $responseMessage = '') {
        try {
            // Verify the request belongs to this mentor
            $request = $this->getRequestById($requestId);
            if (!$request || $request['mentor_id'] != $mentorId) {
                throw new Exception("Invalid request or unauthorized access");
            }

            if ($request['status'] !== 'pending') {
                throw new Exception("This request has already been responded to");
            }

            // If accepting, check mentor capacity again
            if ($response === 'accepted' && $this->mentorAtCapacity($mentorId)) {
                throw new Exception("You have reached your maximum capacity of mentees");
            }

            // Update request status
            $updateQuery = "UPDATE " . $this->requestTable . " 
                           SET status = :status, responded_at = NOW() 
                           WHERE id = :request_id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $response);
            $updateStmt->bindParam(':request_id', $requestId);

            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update request status");
            }

            if ($response === 'accepted') {
                // Create mentorship relationship
                $mentorshipData = [
                    'request_id' => $requestId,
                    'mentee_id' => $request['mentee_id'],
                    'mentor_id' => $mentorId,
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+' . $request['duration_weeks'] . ' weeks'))
                ];

                $this->createMentorship($mentorshipData);

                // Cancel other pending requests from this mentee
                $this->cancelOtherRequests($request['mentee_id'], $requestId);
            }

            // Create notification for mentee
            $notificationType = $response === 'accepted' ? 'request_accepted' : 'request_rejected';
            $notificationTitle = 'Mentorship Request ' . ucfirst($response);
            $notificationMessage = $response === 'accepted' 
                ? 'Your mentorship request has been accepted! You can now start communicating with your mentor.'
                : 'Your mentorship request has been declined.';

            $this->createNotification($request['mentee_id'], $notificationType, 
                $notificationTitle, $notificationMessage, $requestId);

            // Send email notification
            $userModel = new User();
            $mentor = $userModel->getUserById($mentorId);
            $mentee = $userModel->getUserById($request['mentee_id']);
            
            $emailService = new EmailService();
            $emailService->sendRequestResponseEmail($mentee, $mentor, $response);

            return [
                'success' => true,
                'message' => 'Response sent successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getMentorRequests($mentorId, $status = 'pending') {
        $query = "SELECT r.*, 
                         u.first_name, u.last_name, u.email, u.department, 
                         u.year_of_study, u.profile_image, u.bio
                  FROM " . $this->requestTable . " r
                  JOIN users u ON r.mentee_id = u.id
                  WHERE r.mentor_id = :mentor_id AND r.status = :status
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getMenteeRequests($menteeId) {
        $query = "SELECT r.*, 
                         u.first_name, u.last_name, u.email, u.department, 
                         u.year_of_study, u.profile_image, u.bio
                  FROM " . $this->requestTable . " r
                  JOIN users u ON r.mentor_id = u.id
                  WHERE r.mentee_id = :mentee_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateRequestStatus($requestId, $action, $mentorId) {
        try {
            // Verify the request belongs to this mentor
            $request = $this->getRequestById($requestId);
            if (!$request || $request['mentor_id'] != $mentorId) {
                return false;
            }

            if ($request['status'] !== 'pending') {
                return false;
            }

            $response = ($action === 'accept') ? 'accepted' : 'rejected';

            // If accepting, check mentor capacity
            if ($response === 'accepted' && $this->mentorAtCapacity($mentorId)) {
                return false;
            }

            // Update request status
            $updateQuery = "UPDATE " . $this->requestTable . " 
                           SET status = :status, responded_at = NOW() 
                           WHERE id = :request_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $response);
            $updateStmt->bindParam(':request_id', $requestId);

            if (!$updateStmt->execute()) {
                return false;
            }

            if ($response === 'accepted') {
                // Create mentorship relationship
                $mentorshipData = [
                    'request_id' => $requestId,
                    'mentee_id' => $request['mentee_id'],
                    'mentor_id' => $mentorId,
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+' . $request['duration_weeks'] . ' weeks'))
                ];
                $this->createMentorship($mentorshipData);

                // Cancel other pending requests from this mentee
                $this->cancelOtherRequests($request['mentee_id'], $requestId);
            }

            // Create notification for mentee
            $notificationType = $response === 'accepted' ? 'request_accepted' : 'request_rejected';
            $notificationTitle = 'Mentorship Request ' . ucfirst($response);
            $notificationMessage = $response === 'accepted' 
                ? 'Your mentorship request has been accepted! You can now start communicating with your mentor.'
                : 'Your mentorship request has been declined.';
            $this->createNotification($request['mentee_id'], $notificationType, $notificationTitle, $notificationMessage, $requestId);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getActiveMentorships($userId, $role) {
        $roleColumn = $role === 'mentor' ? 'mentor_id' : 'mentee_id';
        $otherRoleColumn = $role === 'mentor' ? 'mentee_id' : 'mentor_id';

        $query = "SELECT m.*, 
                         u.first_name, u.last_name, u.email, u.department, 
                         u.year_of_study, u.profile_image, u.bio,
                         (SELECT COUNT(*) FROM messages msg 
                          WHERE msg.mentorship_id = m.id AND msg.receiver_id = :user_id AND msg.is_read = 0) as unread_messages
                  FROM " . $this->mentorshipTable . " m
                  JOIN users u ON m.{$otherRoleColumn} = u.id
                  WHERE m.{$roleColumn} = :user_id AND m.status = 'active'
                  ORDER BY m.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getMentorshipById($mentorshipId) {
        $query = "SELECT m.*, 
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name,
                         mentor.email as mentor_email, mentor.profile_image as mentor_image,
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name,
                         mentee.email as mentee_email, mentee.profile_image as mentee_image
                  FROM " . $this->mentorshipTable . " m
                  JOIN users mentor ON m.mentor_id = mentor.id
                  JOIN users mentee ON m.mentee_id = mentee.id
                  WHERE m.id = :mentorship_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentorship_id', $mentorshipId);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function completeMentorship($mentorshipId, $userId) {
        try {
            // Verify user is part of this mentorship
            $mentorship = $this->getMentorshipById($mentorshipId);
            if (!$mentorship || ($mentorship['mentor_id'] != $userId && $mentorship['mentee_id'] != $userId)) {
                throw new Exception("Unauthorized access to mentorship");
            }

            $query = "UPDATE " . $this->mentorshipTable . " 
                     SET status = 'completed', end_date = CURRENT_DATE 
                     WHERE id = :mentorship_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mentorship_id', $mentorshipId);

            if ($stmt->execute()) {
                // Create notifications for both users
                $otherUserId = $mentorship['mentor_id'] == $userId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
                $this->createNotification($otherUserId, 'system_announcement', 
                    'Mentorship Completed', 
                    'Your mentorship has been marked as completed.', 
                    $mentorshipId);

                return [
                    'success' => true,
                    'message' => 'Mentorship completed successfully'
                ];
            }

            throw new Exception("Failed to complete mentorship");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getMatchingMentors($menteeId, $limit = 10) {
        // Get mentee's learning skills
        $skillsQuery = "SELECT skill_id FROM user_skills 
                       WHERE user_id = :mentee_id AND is_learning_skill = 1";
        
        $skillsStmt = $this->conn->prepare($skillsQuery);
        $skillsStmt->bindParam(':mentee_id', $menteeId);
        $skillsStmt->execute();
        $menteeSkills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($menteeSkills)) {
            return []; // No skills specified for learning
        }

        // Find mentors with matching teaching skills
        $query = "SELECT DISTINCT u.*, 
                         GROUP_CONCAT(CONCAT(s.name, ':', us.proficiency_level) SEPARATOR '|') as skills,
                         (SELECT COUNT(*) FROM mentorships m2 WHERE m2.mentor_id = u.id AND m2.status = 'active') as current_mentees
                  FROM users u
                  JOIN user_skills us ON u.id = us.user_id AND us.is_teaching_skill = 1
                  JOIN skills s ON us.skill_id = s.id
                  WHERE u.role = 'mentor' 
                    AND u.status = 'active' 
                    AND u.email_verified = 1
                    AND u.id NOT IN (
                        SELECT m.mentor_id FROM mentorships m 
                        WHERE m.mentee_id = :mentee_id AND m.status = 'active'
                    )
                    AND us.skill_id IN (" . implode(',', array_fill(0, count($menteeSkills), '?')) . ")
                  GROUP BY u.id
                  HAVING current_mentees < (SELECT setting_value FROM system_settings WHERE setting_key = 'max_mentees_per_mentor')
                  ORDER BY current_mentees ASC, u.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        // Bind skill parameters
        foreach ($menteeSkills as $index => $skillId) {
            $stmt->bindValue($index + 2, $skillId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function hasActiveMentor($menteeId) {
        $query = "SELECT COUNT(*) FROM " . $this->mentorshipTable . " 
                 WHERE mentee_id = :mentee_id AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    public function mentorAtCapacity($mentorId) {
        // Get current mentees count
        $query = "SELECT COUNT(*) as current_mentees FROM " . $this->mentorshipTable . " 
                 WHERE mentor_id = :mentor_id AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $currentMentees = $result['current_mentees'];
        
        // Default max mentees is 3 if not set in system_settings
        $maxMentees = 3;
        
        return $currentMentees >= $maxMentees;
    }

    public function hasPendingRequest($menteeId, $mentorId) {
        $query = "SELECT COUNT(*) FROM " . $this->requestTable . " 
                 WHERE mentee_id = :mentee_id AND mentor_id = :mentor_id AND status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    public function getRequestById($requestId) {
        $query = "SELECT * FROM " . $this->requestTable . " WHERE id = :request_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $requestId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    private function createMentorship($data) {
        $query = "INSERT INTO " . $this->mentorshipTable . " 
                 (request_id, mentee_id, mentor_id, start_date, end_date) 
                 VALUES (:request_id, :mentee_id, :mentor_id, :start_date, :end_date)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $data['request_id']);
        $stmt->bindParam(':mentee_id', $data['mentee_id']);
        $stmt->bindParam(':mentor_id', $data['mentor_id']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        
        return $stmt->execute();
    }

    private function cancelOtherRequests($menteeId, $acceptedRequestId) {
        $query = "UPDATE " . $this->requestTable . " 
                 SET status = 'cancelled' 
                 WHERE mentee_id = :mentee_id AND id != :accepted_id AND status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->bindParam(':accepted_id', $acceptedRequestId);
        
        return $stmt->execute();
    }

    private function createNotification($userId, $type, $title, $message, $relatedId = null) {
        $query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                 VALUES (:user_id, :type, :title, :message, :related_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':related_id', $relatedId);
        
        return $stmt->execute();
    }
}
?>