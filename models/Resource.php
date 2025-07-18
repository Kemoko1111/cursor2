<?php
require_once 'config/database.php';

class Resource {
    private $conn;
    private $table = 'resources';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function uploadResource($mentorId, $resourceData, $file) {
        try {
            // Validate file
            $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed: " . implode(', ', $allowedTypes));
            }
            
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                throw new Exception("File too large. Maximum size: 10MB");
            }

            // Create upload directory if it doesn't exist
            $uploadDir = UPLOAD_PATH . 'resources/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileName = $mentorId . '_' . time() . '_' . uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save to database
                $query = "INSERT INTO " . $this->table . " 
                         (mentor_id, title, description, file_name, file_size, file_type, category, tags, is_public) 
                         VALUES (:mentor_id, :title, :description, :file_name, :file_size, :file_type, :category, :tags, :is_public)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->bindParam(':title', $resourceData['title']);
                $stmt->bindParam(':description', $resourceData['description']);
                $stmt->bindParam(':file_name', $fileName);
                $stmt->bindParam(':file_size', $file['size']);
                $stmt->bindParam(':file_type', $fileExt);
                $stmt->bindParam(':category', $resourceData['category']);
                $stmt->bindParam(':tags', $resourceData['tags']);
                $stmt->bindParam(':is_public', $resourceData['is_public']);
                
                if ($stmt->execute()) {
                    $resourceId = $this->conn->lastInsertId();
                    
                    // Create notification for mentor's mentees if public
                    if ($resourceData['is_public']) {
                        $this->notifyMentees($mentorId, $resourceId);
                    }
                    
                    return [
                        'success' => true,
                        'resource_id' => $resourceId,
                        'message' => 'Resource uploaded successfully'
                    ];
                }
            }
            
            throw new Exception("Failed to upload resource");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getMentorResources($mentorId, $limit = 50, $offset = 0) {
        $query = "SELECT r.*, 
                         COUNT(rd.id) as download_count,
                         GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as shared_with
                  FROM " . $this->table . " r
                  LEFT JOIN resource_downloads rd ON r.id = rd.resource_id
                  LEFT JOIN resource_shares rs ON r.id = rs.resource_id
                  LEFT JOIN users s ON rs.mentee_id = s.id
                  WHERE r.mentor_id = :mentor_id
                  GROUP BY r.id
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getAvailableResources($menteeId, $searchTerm = '', $category = '') {
        $query = "SELECT DISTINCT r.*, 
                         u.first_name, u.last_name, u.department,
                         COUNT(rd.id) as download_count,
                         CASE 
                             WHEN rs.id IS NOT NULL THEN 1 
                             ELSE 0 
                         END as is_shared_with_me
                  FROM " . $this->table . " r
                  JOIN users u ON r.mentor_id = u.id
                  LEFT JOIN resource_downloads rd ON r.id = rd.resource_id
                  LEFT JOIN resource_shares rs ON r.id = rs.resource_id AND rs.mentee_id = :mentee_id
                  LEFT JOIN mentorships m ON (r.mentor_id = m.mentor_id AND m.mentee_id = :mentee_id AND m.status = 'active')
                  WHERE (r.is_public = 1 OR m.id IS NOT NULL OR rs.id IS NOT NULL)";

        $params = [':mentee_id' => $menteeId];

        if (!empty($searchTerm)) {
            $query .= " AND (r.title LIKE :search OR r.description LIKE :search OR r.tags LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        if (!empty($category)) {
            $query .= " AND r.category = :category";
            $params[':category'] = $category;
        }

        $query .= " GROUP BY r.id ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function shareResource($resourceId, $mentorId, $menteeIds) {
        try {
            // Verify resource belongs to mentor
            if (!$this->verifyResourceOwnership($resourceId, $mentorId)) {
                throw new Exception("Unauthorized access to resource");
            }

            // Remove existing shares for this resource
            $deleteQuery = "DELETE FROM resource_shares WHERE resource_id = :resource_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':resource_id', $resourceId);
            $deleteStmt->execute();

            // Add new shares
            $insertQuery = "INSERT INTO resource_shares (resource_id, mentee_id) VALUES (:resource_id, :mentee_id)";
            $insertStmt = $this->conn->prepare($insertQuery);

            foreach ($menteeIds as $menteeId) {
                $insertStmt->bindParam(':resource_id', $resourceId);
                $insertStmt->bindParam(':mentee_id', $menteeId);
                $insertStmt->execute();

                // Create notification
                $this->createNotification($menteeId, 'resource_shared', 
                    'New Resource Shared', 
                    'A mentor has shared a new resource with you.', 
                    $resourceId);
            }

            return [
                'success' => true,
                'message' => 'Resource shared successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function downloadResource($resourceId, $userId) {
        try {
            // Verify access
            if (!$this->verifyResourceAccess($resourceId, $userId)) {
                throw new Exception("Unauthorized access to resource");
            }

            // Get resource info
            $resource = $this->getResourceById($resourceId);
            if (!$resource) {
                throw new Exception("Resource not found");
            }

            // Log download
            $this->logDownload($resourceId, $userId);

            $filePath = UPLOAD_PATH . 'resources/' . $resource['file_name'];
            
            if (file_exists($filePath)) {
                return [
                    'success' => true,
                    'file_path' => $filePath,
                    'file_name' => $resource['title'] . '.' . $resource['file_type'],
                    'mime_type' => $this->getMimeType($resource['file_type'])
                ];
            }

            throw new Exception("File not found on server");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteResource($resourceId, $mentorId) {
        try {
            // Verify ownership
            if (!$this->verifyResourceOwnership($resourceId, $mentorId)) {
                throw new Exception("Unauthorized access to resource");
            }

            $resource = $this->getResourceById($resourceId);
            if (!$resource) {
                throw new Exception("Resource not found");
            }

            // Delete file from filesystem
            $filePath = UPLOAD_PATH . 'resources/' . $resource['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database (cascading will handle related records)
            $query = "DELETE FROM " . $this->table . " WHERE id = :resource_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':resource_id', $resourceId);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Resource deleted successfully'
                ];
            }

            throw new Exception("Failed to delete resource");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getResourceStats($mentorId) {
        $query = "SELECT 
                    COUNT(r.id) as total_resources,
                    COUNT(CASE WHEN r.is_public = 1 THEN 1 END) as public_resources,
                    COUNT(CASE WHEN r.is_public = 0 THEN 1 END) as private_resources,
                    COALESCE(SUM(rd.download_count), 0) as total_downloads
                  FROM " . $this->table . " r
                  LEFT JOIN (
                      SELECT resource_id, COUNT(*) as download_count 
                      FROM resource_downloads 
                      GROUP BY resource_id
                  ) rd ON r.id = rd.resource_id
                  WHERE r.mentor_id = :mentor_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getResourceCategories() {
        $query = "SELECT DISTINCT category FROM " . $this->table . " WHERE category IS NOT NULL ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function verifyResourceOwnership($resourceId, $mentorId) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE id = :resource_id AND mentor_id = :mentor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    private function verifyResourceAccess($resourceId, $userId) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " r
                  LEFT JOIN resource_shares rs ON r.id = rs.resource_id AND rs.mentee_id = :user_id
                  LEFT JOIN mentorships m ON (r.mentor_id = m.mentor_id AND m.mentee_id = :user_id AND m.status = 'active')
                  WHERE r.id = :resource_id 
                  AND (r.mentor_id = :user_id OR r.is_public = 1 OR rs.id IS NOT NULL OR m.id IS NOT NULL)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    private function getResourceById($resourceId) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :resource_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    private function logDownload($resourceId, $userId) {
        $query = "INSERT INTO resource_downloads (resource_id, user_id, ip_address) 
                 VALUES (:resource_id, :user_id, :ip_address)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->execute();
    }

    private function notifyMentees($mentorId, $resourceId) {
        // Get all active mentees of this mentor
        $query = "SELECT mentee_id FROM mentorships WHERE mentor_id = :mentor_id AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        $mentees = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($mentees as $menteeId) {
            $this->createNotification($menteeId, 'new_resource', 
                'New Resource Available', 
                'Your mentor has uploaded a new resource.', 
                $resourceId);
        }
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

    private function getMimeType($fileType) {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];
        
        return $mimeTypes[$fileType] ?? 'application/octet-stream';
    }
}
?>