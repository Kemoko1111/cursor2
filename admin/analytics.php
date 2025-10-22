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
function getAnalyticsData() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $data = [];
        
        // User growth over time
        $stmt = $pdo->query("SELECT 
                                DATE(created_at) as date,
                                COUNT(*) as new_users
                             FROM users 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                             GROUP BY DATE(created_at)
                             ORDER BY date");
        $data['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Department distribution
        $stmt = $pdo->query("SELECT 
                                department,
                                COUNT(*) as user_count
                             FROM users 
                             WHERE department IS NOT NULL AND department != ''
                             GROUP BY department
                             ORDER BY user_count DESC");
        $data['department_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mentorship statistics
        $stmt = $pdo->query("SELECT 
                                status,
                                COUNT(*) as count
                             FROM mentorships 
                             GROUP BY status");
        $data['mentorship_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Request statistics
        $stmt = $pdo->query("SELECT 
                                status,
                                COUNT(*) as count
                             FROM mentorship_requests 
                             GROUP BY status");
        $data['request_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Message activity
        $stmt = $pdo->query("SELECT 
                                DATE(created_at) as date,
                                COUNT(*) as message_count
                             FROM messages 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                             GROUP BY DATE(created_at)
                             ORDER BY date");
        $data['message_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top mentors
        $stmt = $pdo->query("SELECT 
                                u.first_name,
                                u.last_name,
                                u.department,
                                COUNT(m.id) as mentorship_count
                             FROM users u
                             LEFT JOIN mentorships m ON u.id = m.mentor_id AND m.status = 'active'
                             WHERE u.role = 'mentor'
                             GROUP BY u.id
                             ORDER BY mentorship_count DESC
                             LIMIT 10");
        $data['top_mentors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Platform overview
        $stmt = $pdo->query("SELECT 
                                (SELECT COUNT(*) FROM users) as total_users,
                                (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentors,
                                (SELECT COUNT(*) FROM users WHERE role = 'mentee') as total_mentees,
                                (SELECT COUNT(*) FROM mentorships WHERE status = 'active') as active_mentorships,
                                (SELECT COUNT(*) FROM mentorship_requests WHERE status = 'pending') as pending_requests,
                                (SELECT COUNT(*) FROM messages) as total_messages");
        $data['platform_overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data;
    } catch (Exception $e) {
        error_log("Error getting analytics data: " . $e->getMessage());
        return [];
    }
}

// Get analytics data
$analytics = getAnalyticsData();

$pageTitle = 'Analytics & Reports - Admin Dashboard';
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 1rem;
            color: white;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .analytics-card {
            transition: transform 0.2s ease;
        }
        .analytics-card:hover {
            transform: translateY(-2px);
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
                        <a class="nav-link" href="/admin/dashboard.php">
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
                        <a class="nav-link active" href="/admin/analytics.php">
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
                        <h2 class="mb-1">Analytics & Reports</h2>
                        <p class="text-muted mb-0">Platform insights and performance metrics</p>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-primary" onclick="exportReport()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>

                <!-- Platform Overview -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($analytics['platform_overview']['total_users'] ?? 0); ?></h3>
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
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($analytics['platform_overview']['active_mentorships'] ?? 0); ?></h3>
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
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($analytics['platform_overview']['pending_requests'] ?? 0); ?></h3>
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
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($analytics['platform_overview']['total_messages'] ?? 0); ?></h3>
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

                <!-- Charts Row 1 -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="card shadow-sm analytics-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>User Growth (Last 30 Days)
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
                        <div class="card shadow-sm analytics-card">
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

                <!-- Charts Row 2 -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm analytics-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Mentorship Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="mentorshipChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm analytics-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Request Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="requestChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Activity Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm analytics-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Message Activity (Last 7 Days)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="messageActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Mentors -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm analytics-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-trophy me-2"></i>Top Mentors
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($analytics['top_mentors'])): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No mentor data available</h5>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Mentor</th>
                                                    <th>Department</th>
                                                    <th>Active Mentorships</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($analytics['top_mentors'] as $index => $mentor): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-<?php echo $index < 3 ? 'warning' : 'secondary'; ?>">
                                                                #<?php echo $index + 1; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold">
                                                                <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">
                                                                <?php echo htmlspecialchars($mentor['department']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <?php echo $mentor['mentorship_count']; ?> active
                                                            </span>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($analytics['user_growth'], 'date')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($analytics['user_growth'], 'new_users')); ?>,
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
                labels: <?php echo json_encode(array_column($analytics['department_distribution'], 'department')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['department_distribution'], 'user_count')); ?>,
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

        // Mentorship Status Chart
        const mentorshipCtx = document.getElementById('mentorshipChart').getContext('2d');
        new Chart(mentorshipCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($analytics['mentorship_stats'], 'status')); ?>,
                datasets: [{
                    label: 'Mentorships',
                    data: <?php echo json_encode(array_column($analytics['mentorship_stats'], 'count')); ?>,
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#dc3545'
                    ]
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

        // Request Status Chart
        const requestCtx = document.getElementById('requestChart').getContext('2d');
        new Chart(requestCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($analytics['request_stats'], 'status')); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode(array_column($analytics['request_stats'], 'count')); ?>,
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#dc3545',
                        '#6c757d'
                    ]
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

        // Message Activity Chart
        const messageActivityCtx = document.getElementById('messageActivityChart').getContext('2d');
        new Chart(messageActivityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($analytics['message_activity'], 'date')); ?>,
                datasets: [{
                    label: 'Messages',
                    data: <?php echo json_encode(array_column($analytics['message_activity'], 'message_count')); ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
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

        function exportReport() {
            // Implement report export functionality
            alert('Export functionality will be implemented here');
        }
    </script>
</body>
</html>