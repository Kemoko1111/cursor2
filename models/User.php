<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($userData) {
        try {
            // Validate ACES email domain
            if (!isAcesEmail($userData['email'])) {
                throw new Exception("Only " . ORG_DOMAIN . " email addresses are allowed");
            }

            // Check if email already exists
            if ($this->emailExists($userData['email'])) {
                throw new Exception("Email already exists");
            }

            // Check if student ID already exists
            if ($this->studentIdExists($userData['student_id'])) {
                throw new Exception("Student ID already exists");
            }

            $query = "INSERT INTO " . $this->table . " 
                     (email, password_hash, first_name, last_name, student_id, phone, 
                      year_of_study, department, role, email_verification_token) 
                     VALUES (:email, :password_hash, :first_name, :last_name, :student_id, 
                             :phone, :year_of_study, :department, :role, :verification_token)";

            $stmt = $this->conn->prepare($query);

            // Generate verification token
            $verificationToken = generateToken();

            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password_hash', hashPassword($userData['password']));
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':student_id', $userData['student_id']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':year_of_study', $userData['year_of_study']);
            $stmt->bindParam(':department', $userData['department']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':verification_token', $verificationToken);

            if ($stmt->execute()) {
                $userId = $this->conn->lastInsertId();
                
                // Send verification email
                $emailService = new EmailService();
                $emailService->sendVerificationEmail($userData, $verificationToken);
                
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'message' => 'Registration successful. Please check your email to verify your account.'
                ];
            }
            
            throw new Exception("Registration failed");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function login($email, $password) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE email = :email AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (!$user['email_verified']) {
                    throw new Exception("Please verify your email address before logging in");
                }

                if (verifyPassword($password, $user['password_hash'])) {
                    // Create session
                    $sessionId = $this->createSession($user['id']);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['session_id'] = $sessionId;
                    $_SESSION['last_activity'] = time();

                    return [
                        'success' => true,
                        'user' => $user,
                        'message' => 'Login successful'
                    ];
                }
            }
            
            throw new Exception("Invalid email or password");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyEmail($token) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET email_verified = 1, email_verification_token = NULL 
                     WHERE email_verification_token = :token";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Email verified successfully'
                ];
            }
            
            throw new Exception("Invalid or expired verification token");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function requestPasswordReset($email) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                throw new Exception("Email not found");
            }

            $resetToken = generateToken();
            $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $query = "UPDATE " . $this->table . " 
                     SET password_reset_token = :token, password_reset_expires = :expires 
                     WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $resetToken);
            $stmt->bindParam(':expires', $resetExpires);
            $stmt->bindParam(':email', $email);
            
            if ($stmt->execute()) {
                // Send reset email
                $emailService = new EmailService();
                $emailService->sendPasswordResetEmail($user, $resetToken);
                
                return [
                    'success' => true,
                    'message' => 'Password reset link sent to your email'
                ];
            }
            
            throw new Exception("Failed to generate reset token");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function resetPassword($token, $newPassword) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE password_reset_token = :token 
                     AND password_reset_expires > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new Exception("Invalid or expired reset token");
            }

            $updateQuery = "UPDATE " . $this->table . " 
                           SET password_hash = :password, 
                               password_reset_token = NULL, 
                               password_reset_expires = NULL 
                           WHERE password_reset_token = :token";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', hashPassword($newPassword));
            $updateStmt->bindParam(':token', $token);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Password reset successfully'
                ];
            }
            
            throw new Exception("Failed to reset password");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateProfile($userId, $profileData) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET first_name = :first_name, last_name = :last_name, 
                         phone = :phone, bio = :bio, department = :department, 
                         year_of_study = :year_of_study 
                     WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $profileData['first_name']);
            $stmt->bindParam(':last_name', $profileData['last_name']);
            $stmt->bindParam(':phone', $profileData['phone']);
            $stmt->bindParam(':bio', $profileData['bio']);
            $stmt->bindParam(':department', $profileData['department']);
            $stmt->bindParam(':year_of_study', $profileData['year_of_study']);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            }
            
            throw new Exception("Failed to update profile");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function uploadProfileImage($userId, $file) {
        try {
            // Validate file
            $allowedTypes = ALLOWED_IMAGE_TYPES;
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed: " . implode(', ', $allowedTypes));
            }
            
            if ($file['size'] > UPLOAD_MAX_SIZE) {
                throw new Exception("File too large. Max size: " . (UPLOAD_MAX_SIZE / 1024 / 1024) . "MB");
            }

            // Create upload directory if it doesn't exist
            $uploadDir = UPLOAD_PATH . 'profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileName = $userId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Update database
                $query = "UPDATE " . $this->table . " SET profile_image = :image WHERE id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':image', $fileName);
                $stmt->bindParam(':user_id', $userId);
                
                if ($stmt->execute()) {
                    return [
                        'success' => true,
                        'filename' => $fileName,
                        'message' => 'Profile image uploaded successfully'
                    ];
                }
            }
            
            throw new Exception("Failed to upload image");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getUserById($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getAllMentors($limit = 50, $offset = 0) {
        $query = "SELECT u.*, 
                         GROUP_CONCAT(CONCAT(s.name, ':', us.proficiency_level) SEPARATOR '|') as skills
                  FROM " . $this->table . " u
                  LEFT JOIN user_skills us ON u.id = us.user_id AND us.is_teaching_skill = 1
                  LEFT JOIN skills s ON us.skill_id = s.id
                  WHERE u.role = 'mentor' AND u.status = 'active' AND u.email_verified = 1
                  GROUP BY u.id
                  ORDER BY u.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function searchMentors($searchTerm, $skills = [], $department = '') {
        $query = "SELECT DISTINCT u.*, 
                         GROUP_CONCAT(CONCAT(s.name, ':', us.proficiency_level) SEPARATOR '|') as skills
                  FROM " . $this->table . " u
                  LEFT JOIN user_skills us ON u.id = us.user_id AND us.is_teaching_skill = 1
                  LEFT JOIN skills s ON us.skill_id = s.id
                  WHERE u.role = 'mentor' AND u.status = 'active' AND u.email_verified = 1";

        $params = [];

        if (!empty($searchTerm)) {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.bio LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        if (!empty($department)) {
            $query .= " AND u.department = :department";
            $params[':department'] = $department;
        }

        if (!empty($skills)) {
            $skillPlaceholders = [];
            foreach ($skills as $index => $skill) {
                $placeholder = ':skill' . $index;
                $skillPlaceholders[] = $placeholder;
                $params[$placeholder] = $skill;
            }
            $query .= " AND s.id IN (" . implode(',', $skillPlaceholders) . ")";
        }

        $query .= " GROUP BY u.id ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getUserStats($userId) {
        // Get mentorship stats
        $query = "SELECT 
                    (SELECT COUNT(*) FROM mentorships WHERE mentor_id = :user_id AND status = 'active') as active_mentees,
                    (SELECT COUNT(*) FROM mentorships WHERE mentee_id = :user_id AND status = 'active') as active_mentors,
                    (SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = :user_id AND status = 'pending') as pending_requests,
                    (SELECT COUNT(*) FROM messages WHERE receiver_id = :user_id AND is_read = 0) as unread_messages";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Returns mentors not already in an active mentorship with this mentee, filtered by department, year_of_study, and search
    public function getAvailableMentors($menteeId, $filters = []) {
        $params = [];
        $where = [
            "u.role = 'mentor'",
            "u.status = 'active'",
            "u.email_verified = 1",
            // Exclude mentors already in an active mentorship with this mentee
            "u.id NOT IN (SELECT m.mentor_id FROM mentorships m WHERE m.mentee_id = :mentee_id AND m.status = 'active')"
        ];
        $params[':mentee_id'] = $menteeId;

        if (!empty($filters['department'])) {
            $where[] = 'u.department = :department';
            $params[':department'] = $filters['department'];
        }
        if (!empty($filters['year_of_study'])) {
            $where[] = 'u.year_of_study = :year_of_study';
            $params[':year_of_study'] = $filters['year_of_study'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search OR u.bio LIKE :search OR u.skills LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $query = "SELECT u.*, 
                         (SELECT AVG(r.rating) FROM reviews r WHERE r.mentor_id = u.id) as rating,
                         (SELECT COUNT(*) FROM reviews r WHERE r.mentor_id = u.id) as review_count
                  FROM users u
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY u.created_at DESC";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Returns all unique department names from users table
    public function getAllDepartments() {
        $query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $departments;
    }

    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    private function studentIdExists($studentId) {
        $query = "SELECT id FROM " . $this->table . " WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    private function createSession($userId) {
        $sessionId = generateToken();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $query = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent) 
                 VALUES (:session_id, :user_id, :ip_address, :user_agent)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->execute();

        return $sessionId;
    }

    public function logout($sessionId) {
        $query = "DELETE FROM user_sessions WHERE id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();

        // Clear session
        session_destroy();
    }
}
?>