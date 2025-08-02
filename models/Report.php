<?php
require_once 'config/database.php';

class Report {
    private $conn;
    private $table = 'reports';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new report
     */
    public function createReport($reportType, $title, $description, $parameters = [], $generatedBy = null) {
        $sql = "INSERT INTO reports (report_type, title, description, parameters, generated_by, status) 
                VALUES (:report_type, :title, :description, :parameters, :generated_by, 'generating')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':report_type', $reportType);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':parameters', json_encode($parameters));
        $stmt->bindParam(':generated_by', $generatedBy);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Update report status
     */
    public function updateReportStatus($reportId, $status, $filePath = null) {
        $sql = "UPDATE reports SET status = :status, completed_at = NOW()";
        if ($filePath) {
            $sql .= ", file_path = :file_path";
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $reportId);
        if ($filePath) {
            $stmt->bindParam(':file_path', $filePath);
        }
        
        return $stmt->execute();
    }

    /**
     * Get report by ID
     */
    public function getReportById($reportId) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reports r 
                LEFT JOIN users u ON r.generated_by = u.id 
                WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $reportId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all reports with pagination
     */
    public function getAllReports($limit = 20, $offset = 0) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reports r 
                LEFT JOIN users u ON r.generated_by = u.id 
                ORDER BY r.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reports by type
     */
    public function getReportsByType($reportType, $limit = 20) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reports r 
                LEFT JOIN users u ON r.generated_by = u.id 
                WHERE r.report_type = :report_type 
                ORDER BY r.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':report_type', $reportType);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate mentorship summary report
     */
    public function generateMentorshipSummary($parameters = []) {
        $reportId = $this->createReport(
            'mentorship_summary',
            'Mentorship Summary Report',
            'Summary of all mentorship activities',
            $parameters
        );

        if (!$reportId) {
            return false;
        }

        try {
            $data = $this->getMentorshipSummaryData($parameters);
            $filePath = $this->saveReportToFile($reportId, 'mentorship_summary', $data);
            
            $this->updateReportStatus($reportId, 'completed', $filePath);
            return $reportId;
        } catch (Exception $e) {
            $this->updateReportStatus($reportId, 'failed');
            return false;
        }
    }

    /**
     * Generate user activity report
     */
    public function generateUserActivity($parameters = []) {
        $reportId = $this->createReport(
            'user_activity',
            'User Activity Report',
            'Detailed user activity and engagement metrics',
            $parameters
        );

        if (!$reportId) {
            return false;
        }

        try {
            $data = $this->getUserActivityData($parameters);
            $filePath = $this->saveReportToFile($reportId, 'user_activity', $data);
            
            $this->updateReportStatus($reportId, 'completed', $filePath);
            return $reportId;
        } catch (Exception $e) {
            $this->updateReportStatus($reportId, 'failed');
            return false;
        }
    }

    /**
     * Generate resource usage report
     */
    public function generateResourceUsage($parameters = []) {
        $reportId = $this->createReport(
            'resource_usage',
            'Resource Usage Report',
            'Analysis of resource downloads and usage patterns',
            $parameters
        );

        if (!$reportId) {
            return false;
        }

        try {
            $data = $this->getResourceUsageData($parameters);
            $filePath = $this->saveReportToFile($reportId, 'resource_usage', $data);
            
            $this->updateReportStatus($reportId, 'completed', $filePath);
            return $reportId;
        } catch (Exception $e) {
            $this->updateReportStatus($reportId, 'failed');
            return false;
        }
    }

    /**
     * Generate system stats report
     */
    public function generateSystemStats($parameters = []) {
        $reportId = $this->createReport(
            'system_stats',
            'System Statistics Report',
            'Overall system performance and usage statistics',
            $parameters
        );

        if (!$reportId) {
            return false;
        }

        try {
            $data = $this->getSystemStatsData($parameters);
            $filePath = $this->saveReportToFile($reportId, 'system_stats', $data);
            
            $this->updateReportStatus($reportId, 'completed', $filePath);
            return $reportId;
        } catch (Exception $e) {
            $this->updateReportStatus($reportId, 'failed');
            return false;
        }
    }

    /**
     * Get mentorship summary data
     */
    private function getMentorshipSummaryData($parameters) {
        $sql = "SELECT 
                    COUNT(*) as total_mentorships,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_mentorships,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_mentorships,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_mentorships,
                    AVG(DATEDIFF(CURRENT_DATE, start_date)) as avg_duration_days
                FROM mentorships";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get top mentors
        $sql = "SELECT 
                    u.first_name, u.last_name, u.email,
                    COUNT(m.id) as mentorship_count
                FROM users u
                JOIN mentorships m ON u.id = m.mentor_id
                WHERE u.role = 'mentor'
                GROUP BY u.id
                ORDER BY mentorship_count DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $topMentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'summary' => $summary,
            'top_mentors' => $topMentors,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get user activity data
     */
    private function getUserActivityData($parameters) {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'mentor' THEN 1 END) as total_mentors,
                    COUNT(CASE WHEN role = 'mentee' THEN 1 END) as total_mentees,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_users
                FROM users";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get recent registrations
        $sql = "SELECT first_name, last_name, email, role, created_at
                FROM users
                ORDER BY created_at DESC
                LIMIT 20";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'user_stats' => $userStats,
            'recent_users' => $recentUsers,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get resource usage data
     */
    private function getResourceUsageData($parameters) {
        $sql = "SELECT 
                    COUNT(*) as total_resources,
                    SUM(download_count) as total_downloads,
                    AVG(download_count) as avg_downloads,
                    COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_resources
                FROM resources";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resourceStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get top resources
        $sql = "SELECT title, download_count, created_at
                FROM resources
                ORDER BY download_count DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $topResources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'resource_stats' => $resourceStats,
            'top_resources' => $topResources,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get system stats data
     */
    private function getSystemStatsData($parameters) {
        // Get various system statistics
        $stats = [];
        
        // User stats
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetchColumn();

        // Mentorship stats
        $sql = "SELECT COUNT(*) as total FROM mentorships WHERE status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['active_mentorships'] = $stmt->fetchColumn();

        // Message stats
        $sql = "SELECT COUNT(*) as total FROM messages";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['total_messages'] = $stmt->fetchColumn();

        // Resource stats
        $sql = "SELECT COUNT(*) as total FROM resources";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['total_resources'] = $stmt->fetchColumn();

        return [
            'system_stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Save report data to file
     */
    private function saveReportToFile($reportId, $type, $data) {
        $reportsDir = 'uploads/reports/';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        $filename = "report_{$type}_{$reportId}_" . date('Y-m-d_H-i-s') . '.json';
        $filePath = $reportsDir . $filename;
        
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filePath;
    }

    /**
     * Delete report
     */
    public function deleteReport($reportId) {
        $sql = "DELETE FROM reports WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $reportId);
        return $stmt->execute();
    }

    /**
     * Get report count by status
     */
    public function getReportCountByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM reports GROUP BY status";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>