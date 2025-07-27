<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Admin functions
function getDashboardStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        // Total mentors
        $stmt = $pdo->query("SELECT COUNT(*) as total_mentors FROM users WHERE user_role = 'mentor'");
        $stats['total_mentors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_mentors'];
        
        // Total mentees
        $stmt = $pdo->query("SELECT COUNT(*) as total_mentees FROM users WHERE user_role = 'mentee'");
        $stats['total_mentees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_mentees'];
        
        // Active mentorships
        $stmt = $pdo->query("SELECT COUNT(*) as active_mentorships FROM mentorships WHERE status = 'active'");
        $stats['active_mentorships'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_mentorships'];
        
        // Pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM mentorship_requests WHERE status = 'pending'");
        $stats['pending_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_requests'];
        
        // Total messages
        $stmt = $pdo->query("SELECT COUNT(*) as total_messages FROM messages");
        $stats['total_messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_messages'];
        
        // Recent registrations (last 7 days)
        $stmt = $pdo->query("SELECT COUNT(*) as recent_registrations FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['recent_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_registrations'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return [];
    }
}

function getRecentUsers($limit = 10) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT id, first_name, last_name, email, user_role, department, created_at 
                  FROM users 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent users: " . $e->getMessage());
        return [];
    }
}

function getPendingRequests($limit = 10) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT mr.*, 
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name,
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name
                  FROM mentorship_requests mr
                  JOIN users mentee ON mr.mentee_id = mentee.id
                  JOIN users mentor ON mr.mentor_id = mentor.id
                  WHERE mr.status = 'pending'
                  ORDER BY mr.created_at DESC
                  LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting pending requests: " . $e->getMessage());
        return [];
    }
}

function getActiveMentorships($limit = 10) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT m.*, 
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name,
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name
                  FROM mentorships m
                  JOIN users mentor ON m.mentor_id = mentor.id
                  JOIN users mentee ON m.mentee_id = mentee.id
                  WHERE m.status = 'active'
                  ORDER BY m.created_at DESC
                  LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting active mentorships: " . $e->getMessage());
        return [];
    }
}

function getDepartmentStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT department, COUNT(*) as user_count
                  FROM users 
                  WHERE department IS NOT NULL AND department != ''
                  GROUP BY department
                  ORDER BY user_count DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting department stats: " . $e->getMessage());
        return [];
    }
}

// Get dashboard data
$stats = getDashboardStats();
$recentUsers = getRecentUsers(5);
$pendingRequests = getPendingRequests(5);
$activeMentorships = getActiveMentorships(5);
$departmentStats = getDepartmentStats();

$pageTitle = 'Admin Dashboard - Menteego';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .card-body {
            color: white;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-item {
            border-left: 3px solid #667eea;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
                    </h4>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>User Management
                        </a>
                        <a class="nav-link" href="/admin/mentorships.php">
                            <i class="fas fa-handshake me-2"></i>Mentorships
                        </a>
                        <a class="nav-link" href="/admin/requests.php">
                            <i class="fas fa-clipboard-list me-2"></i>Requests
                        </a>
                        <a class="nav-link" href="/admin/messages.php">
                            <i class="fas fa-comments me-2"></i>Messages
                        </a>
                        <a class="nav-link" href="/admin/analytics.php">
                            <i class="fas fa-chart-bar me-2"></i>Analytics
                        </a>
                        <a class="nav-link" href="/admin/settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                        <hr class="text-white">
                        <a class="nav-link" href="/dashboard.php">
                            <i class="fas fa-arrow-left me-2"></i>Back to App
                        </a>
                        <a class="nav-link" href="/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Admin Dashboard</h2>
                        <p class="text-muted mb-0">Overview of platform statistics and activities</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Last updated: <?php echo date('M j, Y g:i A'); ?></small>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Total Users</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['active_mentorships'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Active Mentorships</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['pending_requests'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Pending Requests</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_messages'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Total Messages</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Analytics -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>User Growth
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="userGrowthChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Department Distribution
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="departmentChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-user-plus me-2"></i>Recent Registrations
                                </h6>
                            </div>
                            <div class="card-body recent-activity">
                                <?php if (empty($recentUsers)): ?>
                                    <p class="text-muted text-center">No recent registrations</p>
                                <?php else: ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge bg-<?php echo $user['user_role'] === 'mentor' ? 'success' : 'info'; ?>">
                                                            <?php echo ucfirst($user['user_role']); ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            <?php echo date('M j', strtotime($user['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Pending Requests
                                </h6>
                            </div>
                            <div class="card-body recent-activity">
                                <?php if (empty($pendingRequests)): ?>
                                    <p class="text-muted text-center">No pending requests</p>
                                <?php else: ?>
                                    <?php foreach ($pendingRequests as $request): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-clock text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['mentee_first_name'] . ' → ' . $request['mentor_first_name']); ?></h6>
                                                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars(substr($request['message'], 0, 50)) . '...'; ?></p>
                                                    <small class="text-muted">
                                                        <?php echo date('M j', strtotime($request['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-handshake me-2"></i>Active Mentorships
                                </h6>
                            </div>
                            <div class="card-body recent-activity">
                                <?php if (empty($activeMentorships)): ?>
                                    <p class="text-muted text-center">No active mentorships</p>
                                <?php else: ?>
                                    <?php foreach ($activeMentorships as $mentorship): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-handshake text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($mentorship['mentor_first_name'] . ' ↔ ' . $mentorship['mentee_first_name']); ?></h6>
                                                    <p class="mb-1 small text-muted">Active mentorship</p>
                                                    <small class="text-muted">
                                                        Started <?php echo date('M j', strtotime($mentorship['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Total Users',
                    data: [12, 19, 25, 32, 38, 45],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Department Distribution Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($departmentStats, 'department')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($departmentStats, 'user_count')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>