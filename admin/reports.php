<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php'); exit();
}
$userId = $_SESSION['user_id'];
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Report generation functions
function createReport($reportType, $title, $description, $parameters = [], $generatedBy = null) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "INSERT INTO reports (report_type, title, description, parameters, generated_by, status) 
                VALUES (:report_type, :title, :description, :parameters, :generated_by, 'generating')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':report_type', $reportType);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':parameters', json_encode($parameters));
        $stmt->bindParam(':generated_by', $generatedBy);
        
        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function updateReportStatus($reportId, $status, $filePath = null) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "UPDATE reports SET status = :status, completed_at = NOW()";
        if ($filePath) {
            $sql .= ", file_path = :file_path";
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $reportId);
        if ($filePath) {
            $stmt->bindParam(':file_path', $filePath);
        }
        
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

function getAllReports($limit = 50, $offset = 0) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reports r 
                LEFT JOIN users u ON r.generated_by = u.id 
                ORDER BY r.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getReportCountByStatus() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT status, COUNT(*) as count FROM reports GROUP BY status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Report data generation functions
function getMentorshipSummaryData() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT 
                    COUNT(*) as total_mentorships,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_mentorships,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_mentorships,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_mentorships,
                    AVG(DATEDIFF(CURRENT_DATE, start_date)) as avg_duration_days
                FROM mentorships";
        
        $stmt = $pdo->prepare($sql);
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
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $topMentors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'summary' => $summary,
            'top_mentors' => $topMentors,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [];
    }
}

function getUserActivityData() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'mentor' THEN 1 END) as total_mentors,
                    COUNT(CASE WHEN role = 'mentee' THEN 1 END) as total_mentees,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_users
                FROM users";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get recent registrations
        $sql = "SELECT first_name, last_name, email, role, created_at
                FROM users
                ORDER BY created_at DESC
                LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'user_stats' => $userStats,
            'recent_users' => $recentUsers,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [];
    }
}

function getResourceUsageData() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "SELECT 
                    COUNT(*) as total_resources,
                    SUM(download_count) as total_downloads,
                    AVG(download_count) as avg_downloads,
                    COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_resources
                FROM resources";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $resourceStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get top resources
        $sql = "SELECT title, download_count, created_at
                FROM resources
                ORDER BY download_count DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $topResources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'resource_stats' => $resourceStats,
            'top_resources' => $topResources,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [];
    }
}

function getSystemStatsData() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        
        // User stats
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetchColumn();

        // Mentorship stats
        $sql = "SELECT COUNT(*) as total FROM mentorships WHERE status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats['active_mentorships'] = $stmt->fetchColumn();

        // Message stats
        $sql = "SELECT COUNT(*) as total FROM messages";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats['total_messages'] = $stmt->fetchColumn();

        // Resource stats
        $sql = "SELECT COUNT(*) as total FROM resources";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats['total_resources'] = $stmt->fetchColumn();

        return [
            'system_stats' => $stats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [];
    }
}

function saveReportToFile($reportId, $type, $data) {
    $reportsDir = '../uploads/reports/';
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }

    $filename = "report_{$type}_{$reportId}_" . date('Y-m-d_H-i-s') . '.json';
    $filePath = $reportsDir . $filename;
    
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    
    return $filePath;
}

// Handle report generation
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    $parameters = [];
    
    switch ($reportType) {
        case 'mentorship_summary':
            $reportId = createReport('mentorship_summary', 'Mentorship Summary Report', 'Summary of all mentorship activities', $parameters, $userId);
            if ($reportId) {
                try {
                    $data = getMentorshipSummaryData();
                    $filePath = saveReportToFile($reportId, 'mentorship_summary', $data);
                    updateReportStatus($reportId, 'completed', $filePath);
                    $success = "Mentorship Summary Report generated successfully! Report ID: " . $reportId;
                } catch (Exception $e) {
                    updateReportStatus($reportId, 'failed');
                    $error = "Failed to generate report.";
                }
            } else {
                $error = "Failed to create report.";
            }
            break;
            
        case 'user_activity':
            $reportId = createReport('user_activity', 'User Activity Report', 'Detailed user activity and engagement metrics', $parameters, $userId);
            if ($reportId) {
                try {
                    $data = getUserActivityData();
                    $filePath = saveReportToFile($reportId, 'user_activity', $data);
                    updateReportStatus($reportId, 'completed', $filePath);
                    $success = "User Activity Report generated successfully! Report ID: " . $reportId;
                } catch (Exception $e) {
                    updateReportStatus($reportId, 'failed');
                    $error = "Failed to generate report.";
                }
            } else {
                $error = "Failed to create report.";
            }
            break;
            
        case 'resource_usage':
            $reportId = createReport('resource_usage', 'Resource Usage Report', 'Analysis of resource downloads and usage patterns', $parameters, $userId);
            if ($reportId) {
                try {
                    $data = getResourceUsageData();
                    $filePath = saveReportToFile($reportId, 'resource_usage', $data);
                    updateReportStatus($reportId, 'completed', $filePath);
                    $success = "Resource Usage Report generated successfully! Report ID: " . $reportId;
                } catch (Exception $e) {
                    updateReportStatus($reportId, 'failed');
                    $error = "Failed to generate report.";
                }
            } else {
                $error = "Failed to create report.";
            }
            break;
            
        case 'system_stats':
            $reportId = createReport('system_stats', 'System Statistics Report', 'Overall system performance and usage statistics', $parameters, $userId);
            if ($reportId) {
                try {
                    $data = getSystemStatsData();
                    $filePath = saveReportToFile($reportId, 'system_stats', $data);
                    updateReportStatus($reportId, 'completed', $filePath);
                    $success = "System Statistics Report generated successfully! Report ID: " . $reportId;
                } catch (Exception $e) {
                    updateReportStatus($reportId, 'failed');
                    $error = "Failed to generate report.";
                }
            } else {
                $error = "Failed to create report.";
            }
            break;
            
        default:
            $error = "Invalid report type.";
            break;
    }
}

// Get existing reports
$reports = getAllReports(50, 0);
$reportCounts = getReportCountByStatus();

$pageTitle = 'Reports - Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);}
        .admin-sidebar .nav-link { color: rgba(255,255,255,0.8); border-radius: 0.5rem; margin: 0.25rem 0; transition: all 0.3s;}
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); transform: translateX(5px);}
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 1rem; color: white; transition: transform 0.3s;}
        .stats-card:hover { transform: translateY(-5px);}
        .dashboard-card { transition: transform 0.2s;}
        .dashboard-card:hover { transform: translateY(-2px);}
        .report-card { transition: transform 0.2s;}
        .report-card:hover { transform: translateY(-2px);}
        .status-badge { font-size: 0.75rem; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 admin-sidebar p-0">
            <div class="p-4">
                <h4 class="text-white mb-4"><i class="fas fa-shield-alt me-2"></i>Admin Panel</h4>
                <nav class="nav flex-column">
                    <a class="nav-link" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link" href="/admin/users.php"><i class="fas fa-users me-2"></i>User Management</a>
                    <a class="nav-link" href="/admin/mentorships.php"><i class="fas fa-handshake me-2"></i>Mentorships</a>
                    <a class="nav-link" href="/admin/requests.php"><i class="fas fa-clipboard-list me-2"></i>Requests</a>
                    <a class="nav-link" href="/admin/messages.php"><i class="fas fa-comments me-2"></i>Messages</a>
                    <a class="nav-link" href="/admin/analytics.php"><i class="fas fa-chart-bar me-2"></i>Analytics</a>
                    <a class="nav-link active" href="/admin/reports.php"><i class="fas fa-file-alt me-2"></i>Reports</a>
                    <a class="nav-link" href="/admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                    <hr class="text-white">
                    <a class="nav-link" href="/dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to App</a>
                    <a class="nav-link" href="/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </nav>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">System Reports</h2>
                    <p class="text-muted mb-0">Generate and manage system reports</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary"><?php echo date('M j, Y'); ?></span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Report Generation -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card dashboard-card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-plus-circle me-2 text-success"></i>
                                Generate New Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="report_type" class="form-label fw-semibold">
                                            <i class="fas fa-chart-line me-2"></i>Report Type
                                        </label>
                                        <select class="form-select" id="report_type" name="report_type" required>
                                            <option value="">Select a report type...</option>
                                            <option value="mentorship_summary">Mentorship Summary Report</option>
                                            <option value="user_activity">User Activity Report</option>
                                            <option value="resource_usage">Resource Usage Report</option>
                                            <option value="system_stats">System Statistics Report</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-cog me-2"></i>Generate Report
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-chart-pie me-2 text-info"></i>
                        Report Statistics
                    </h5>
                    <div class="row">
                        <?php foreach ($reportCounts as $count): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card text-center">
                                    <div class="card-body">
                                        <h3 class="mb-1"><?php echo $count['count']; ?></h3>
                                        <p class="mb-0 opacity-75 text-capitalize"><?php echo $count['status']; ?> Reports</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>
                                Recent Reports
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($reports)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No reports generated yet</h6>
                                    <p class="text-muted mb-0">Generate your first report using the form above.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Report</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Generated By</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                                            <?php if ($report['description']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($report['description']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary text-capitalize">
                                                            <?php echo str_replace('_', ' ', $report['report_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusClass = match($report['status']) {
                                                            'completed' => 'success',
                                                            'generating' => 'warning',
                                                            'failed' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        ?>
                                                        <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                                            <?php echo ucfirst($report['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($report['first_name']): ?>
                                                            <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">System</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($report['status'] === 'completed' && $report['file_path']): ?>
                                                                <a href="/<?php echo $report['file_path']; ?>" 
                                                                   class="btn btn-outline-primary btn-sm" 
                                                                   target="_blank" 
                                                                   title="Download Report">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger btn-sm" 
                                                                    onclick="deleteReport(<?php echo $report['id']; ?>)"
                                                                    title="Delete Report">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function deleteReport(reportId) {
        if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
            // You can implement AJAX deletion here
            console.log('Delete report:', reportId);
            alert('Delete functionality would be implemented here.');
        }
    }
</script>
</body>
</html>