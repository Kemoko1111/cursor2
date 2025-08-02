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

function getDashboardStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stats = [];
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['mentors'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mentor'")->fetchColumn();
        $stats['mentees'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mentee'")->fetchColumn();
        $stats['active_mentorships'] = $pdo->query("SELECT COUNT(*) FROM mentorships WHERE status = 'active'")->fetchColumn();
        $stats['pending_requests'] = $pdo->query("SELECT COUNT(*) FROM mentorship_requests WHERE status = 'pending'")->fetchColumn();
        $stats['total_messages'] = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        $stats['new_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        $stats['completed_mentorships'] = $pdo->query("SELECT COUNT(*) FROM mentorships WHERE status = 'completed'")->fetchColumn();
        return $stats;
    } catch (Exception $e) { return []; }
}
function getRecentUsers() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT first_name, last_name, email, role, department, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}
function getPendingRequests() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT mr.*, mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name, mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name FROM mentorship_requests mr JOIN users mentor ON mr.mentor_id = mentor.id JOIN users mentee ON mr.mentee_id = mentee.id WHERE mr.status = 'pending' ORDER BY mr.created_at DESC LIMIT 5");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}
function getActiveMentorships() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT m.*, mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name, mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name FROM mentorships m JOIN users mentor ON m.mentor_id = mentor.id JOIN users mentee ON m.mentee_id = mentee.id WHERE m.status = 'active' ORDER BY m.created_at DESC LIMIT 5");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}
function getDepartmentStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT department, COUNT(*) as user_count FROM users WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY user_count DESC LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}
$stats = getDashboardStats();
$recent_users = getRecentUsers();
$pending_requests = getPendingRequests();
$active_mentorships = getActiveMentorships();
$department_stats = getDepartmentStats();
$pageTitle = 'Admin Dashboard - Menteego';
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
                    <a class="nav-link active" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link" href="/admin/users.php"><i class="fas fa-users me-2"></i>User Management</a>
                    <a class="nav-link" href="/admin/mentorships.php"><i class="fas fa-handshake me-2"></i>Mentorships</a>
                    <a class="nav-link" href="/admin/requests.php"><i class="fas fa-clipboard-list me-2"></i>Requests</a>
                    <a class="nav-link" href="/admin/messages.php"><i class="fas fa-comments me-2"></i>Messages</a>
                    <a class="nav-link" href="/admin/analytics.php"><i class="fas fa-chart-bar me-2"></i>Analytics</a>
                    <a class="nav-link" href="/admin/reports.php"><i class="fas fa-file-alt me-2"></i>Reports</a>
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
                    <h2 class="mb-1">Admin Dashboard</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary"><?php echo date('M j, Y'); ?></span>
                </div>
            </div>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                                    <p class="mb-0 opacity-75">Total Users</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?php echo number_format($stats['mentors'] ?? 0); ?></h3>
                                    <p class="mb-0 opacity-75">Mentors</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?php echo number_format($stats['mentees'] ?? 0); ?></h3>
                                    <p class="mb-0 opacity-75">Mentees</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?php echo number_format($stats['active_mentorships'] ?? 0); ?></h3>
                                    <p class="mb-0 opacity-75">Active Mentorships</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add more dashboard content as needed -->
        </div>
    </div>
</div>
</body>
</html>