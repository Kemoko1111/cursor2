<?php
require_once 'config/app.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$mentorshipModel = new Mentorship();
$resourceModel = new Resource();
$messageModel = new Message();

// Get current user data
$currentUser = $userModel->getUserById($userId);
if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

// Initialize database connection for custom queries
$database = new Database();
$conn = $database->getConnection();

// Get date range from request (default to last 30 days)
$dateRange = $_GET['range'] ?? '30';
$startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
$endDate = date('Y-m-d');

// Generate reports based on user role
$reports = [];

if ($userRole === 'admin') {
    // Admin gets all system reports
    $reports = generateSystemReports($conn, $startDate, $endDate);
} elseif ($userRole === 'mentor') {
    // Mentors get their mentorship and resource reports
    $reports = generateMentorReports($conn, $userId, $startDate, $endDate);
} else {
    // Mentees get their learning progress reports
    $reports = generateMenteeReports($conn, $userId, $startDate, $endDate);
}

$pageTitle = 'Reports & Analytics - Menteego';

function generateSystemReports($conn, $startDate, $endDate) {
    $reports = [];
    
    // User Registration Stats
    $userStatsQuery = "SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'mentor' THEN 1 END) as total_mentors,
        COUNT(CASE WHEN role = 'mentee' THEN 1 END) as total_mentees,
        COUNT(CASE WHEN created_at >= :start_date THEN 1 END) as new_users,
        COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_users
        FROM users WHERE created_at <= :end_date";
    
    $stmt = $conn->prepare($userStatsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['user_stats'] = $stmt->fetch();
    
    // Mentorship Stats
    $mentorshipStatsQuery = "SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_requests,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
        ROUND(AVG(CASE WHEN responded_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, created_at, responded_at) END), 2) as avg_response_time_hours
        FROM mentorship_requests WHERE created_at BETWEEN :start_date AND :end_date";
    
    $stmt = $conn->prepare($mentorshipStatsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['mentorship_stats'] = $stmt->fetch();
    
    // Active Mentorships
    $activeMentorshipsQuery = "SELECT COUNT(*) as active_mentorships 
        FROM mentorships WHERE status = 'active'";
    $stmt = $conn->prepare($activeMentorshipsQuery);
    $stmt->execute();
    $reports['active_mentorships'] = $stmt->fetch()['active_mentorships'];
    
    // Resource Stats
    $resourceStatsQuery = "SELECT 
        COUNT(*) as total_resources,
        COUNT(CASE WHEN is_public = 1 THEN 1 END) as public_resources,
        SUM(file_size) as total_storage_bytes,
        (SELECT COUNT(*) FROM resource_downloads WHERE downloaded_at BETWEEN :start_date AND :end_date) as total_downloads
        FROM resources WHERE created_at <= :end_date";
    
    $stmt = $conn->prepare($resourceStatsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['resource_stats'] = $stmt->fetch();
    
    // Message Stats
    $messageStatsQuery = "SELECT 
        COUNT(*) as total_messages,
        COUNT(DISTINCT mentorship_id) as active_conversations
        FROM messages WHERE created_at BETWEEN :start_date AND :end_date";
    
    $stmt = $conn->prepare($messageStatsQuery);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['message_stats'] = $stmt->fetch();
    
    // Top Mentors by Mentees
    $topMentorsQuery = "SELECT 
        u.first_name, u.last_name, u.department,
        COUNT(m.id) as mentee_count
        FROM users u
        JOIN mentorships m ON u.id = m.mentor_id
        WHERE u.role = 'mentor' AND m.status = 'active'
        GROUP BY u.id
        ORDER BY mentee_count DESC
        LIMIT 10";
    
    $stmt = $conn->prepare($topMentorsQuery);
    $stmt->execute();
    $reports['top_mentors'] = $stmt->fetchAll();
    
    // Department Distribution
    $departmentStatsQuery = "SELECT 
        department, 
        COUNT(*) as user_count,
        COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentor_count,
        COUNT(CASE WHEN role = 'mentee' THEN 1 END) as mentee_count
        FROM users 
        WHERE status = 'active' 
        GROUP BY department 
        ORDER BY user_count DESC";
    
    $stmt = $conn->prepare($departmentStatsQuery);
    $stmt->execute();
    $reports['department_stats'] = $stmt->fetchAll();
    
    return $reports;
}

function generateMentorReports($conn, $mentorId, $startDate, $endDate) {
    $reports = [];
    
    // Mentor's Mentorship Stats
    $mentorshipStatsQuery = "SELECT 
        COUNT(CASE WHEN mr.status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN mr.status = 'accepted' THEN 1 END) as accepted_requests,
        COUNT(CASE WHEN mr.status = 'rejected' THEN 1 END) as rejected_requests,
        COUNT(CASE WHEN m.status = 'active' THEN 1 END) as active_mentorships,
        COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as completed_mentorships
        FROM mentorship_requests mr
        LEFT JOIN mentorships m ON mr.id = m.request_id
        WHERE mr.mentor_id = :mentor_id AND mr.created_at BETWEEN :start_date AND :end_date";
    
    $stmt = $conn->prepare($mentorshipStatsQuery);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['mentorship_stats'] = $stmt->fetch();
    
    // Resource Performance
    $resourceStatsQuery = "SELECT 
        COUNT(*) as total_resources,
        SUM(file_size) as total_storage_bytes,
        (SELECT COUNT(*) FROM resource_downloads rd 
         WHERE rd.resource_id IN (SELECT id FROM resources WHERE mentor_id = :mentor_id)
         AND rd.downloaded_at BETWEEN :start_date AND :end_date) as total_downloads
        FROM resources WHERE mentor_id = :mentor_id";
    
    $stmt = $conn->prepare($resourceStatsQuery);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['resource_stats'] = $stmt->fetch();
    
    // Current Mentees Performance
    $menteesQuery = "SELECT 
        u.first_name, u.last_name, u.department, u.year_of_study,
        m.start_date,
        DATEDIFF(CURRENT_DATE, m.start_date) as days_active,
        (SELECT COUNT(*) FROM messages msg 
         WHERE msg.mentorship_id = m.id AND msg.sender_id = u.id) as messages_sent,
        (SELECT COUNT(*) FROM resource_downloads rd 
         JOIN resources r ON rd.resource_id = r.id 
         WHERE r.mentor_id = :mentor_id AND rd.user_id = u.id) as resources_downloaded
        FROM mentorships m
        JOIN users u ON m.mentee_id = u.id
        WHERE m.mentor_id = :mentor_id AND m.status = 'active'
        ORDER BY m.start_date DESC";
    
    $stmt = $conn->prepare($menteesQuery);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $reports['mentees'] = $stmt->fetchAll();
    
    // Most Downloaded Resources
    $popularResourcesQuery = "SELECT 
        r.title, r.category, r.created_at,
        COUNT(rd.id) as download_count
        FROM resources r
        LEFT JOIN resource_downloads rd ON r.id = rd.resource_id
        WHERE r.mentor_id = :mentor_id
        GROUP BY r.id
        ORDER BY download_count DESC
        LIMIT 10";
    
    $stmt = $conn->prepare($popularResourcesQuery);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $reports['popular_resources'] = $stmt->fetchAll();
    
    return $reports;
}

function generateMenteeReports($conn, $menteeId, $startDate, $endDate) {
    $reports = [];
    
    // Learning Progress
    $progressQuery = "SELECT 
        COUNT(CASE WHEN mr.status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN mr.status = 'accepted' THEN 1 END) as accepted_requests,
        COUNT(CASE WHEN mr.status = 'rejected' THEN 1 END) as rejected_requests,
        COUNT(CASE WHEN m.status = 'active' THEN 1 END) as active_mentorships,
        COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as completed_mentorships
        FROM mentorship_requests mr
        LEFT JOIN mentorships m ON mr.id = m.request_id
        WHERE mr.mentee_id = :mentee_id";
    
    $stmt = $conn->prepare($progressQuery);
    $stmt->bindParam(':mentee_id', $menteeId);
    $stmt->execute();
    $reports['progress_stats'] = $stmt->fetch();
    
    // Resource Usage
    $resourceUsageQuery = "SELECT 
        COUNT(DISTINCT rd.resource_id) as resources_downloaded,
        COUNT(rd.id) as total_downloads,
        (SELECT COUNT(DISTINCT r.mentor_id) FROM resource_downloads rd2 
         JOIN resources r ON rd2.resource_id = r.id 
         WHERE rd2.user_id = :mentee_id) as mentors_learned_from
        FROM resource_downloads rd
        WHERE rd.user_id = :mentee_id AND rd.downloaded_at BETWEEN :start_date AND :end_date";
    
    $stmt = $conn->prepare($resourceUsageQuery);
    $stmt->bindParam(':mentee_id', $menteeId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['resource_usage'] = $stmt->fetch();
    
    // Current Mentors
    $mentorsQuery = "SELECT 
        u.first_name, u.last_name, u.department,
        m.start_date,
        DATEDIFF(CURRENT_DATE, m.start_date) as days_active,
        (SELECT COUNT(*) FROM messages msg 
         WHERE msg.mentorship_id = m.id) as total_messages,
        (SELECT COUNT(*) FROM resources r 
         WHERE r.mentor_id = u.id) as available_resources
        FROM mentorships m
        JOIN users u ON m.mentor_id = u.id
        WHERE m.mentee_id = :mentee_id AND m.status = 'active'
        ORDER BY m.start_date DESC";
    
    $stmt = $conn->prepare($mentorsQuery);
    $stmt->bindParam(':mentee_id', $menteeId);
    $stmt->execute();
    $reports['mentors'] = $stmt->fetchAll();
    
    // Recent Activity
    $activityQuery = "SELECT 
        'message' as activity_type,
        'Sent a message' as activity,
        msg.created_at as activity_date,
        CONCAT(u.first_name, ' ', u.last_name) as related_person
        FROM messages msg
        JOIN mentorships m ON msg.mentorship_id = m.id
        JOIN users u ON m.mentor_id = u.id
        WHERE msg.sender_id = :mentee_id AND msg.created_at BETWEEN :start_date AND :end_date
        
        UNION ALL
        
        SELECT 
        'download' as activity_type,
        CONCAT('Downloaded ', r.title) as activity,
        rd.downloaded_at as activity_date,
        CONCAT(u.first_name, ' ', u.last_name) as related_person
        FROM resource_downloads rd
        JOIN resources r ON rd.resource_id = r.id
        JOIN users u ON r.mentor_id = u.id
        WHERE rd.user_id = :mentee_id AND rd.downloaded_at BETWEEN :start_date AND :end_date
        
        ORDER BY activity_date DESC
        LIMIT 20";
    
    $stmt = $conn->prepare($activityQuery);
    $stmt->bindParam(':mentee_id', $menteeId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $reports['recent_activity'] = $stmt->fetchAll();
    
    return $reports;
}
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
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Menteego
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/resources.php">
                            <i class="fas fa-file-alt me-1"></i>Resources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $currentUser['profile_image'] ? 'uploads/profiles/' . $currentUser['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                 class="rounded-circle me-2" width="32" height="32" alt="">
                            <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">
                        <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                    </h1>
                    <p class="mb-0 opacity-75">
                        <?php if ($userRole === 'admin'): ?>
                            Comprehensive system analytics and user insights
                        <?php elseif ($userRole === 'mentor'): ?>
                            Track your mentoring impact and resource performance
                        <?php else: ?>
                            Monitor your learning progress and mentorship journey
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar me-2"></i>
                            Last <?php echo $dateRange; ?> days
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?range=7">Last 7 days</a></li>
                            <li><a class="dropdown-item" href="?range=30">Last 30 days</a></li>
                            <li><a class="dropdown-item" href="?range=90">Last 90 days</a></li>
                            <li><a class="dropdown-item" href="?range=365">Last year</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if ($userRole === 'admin'): ?>
            <!-- Admin Reports -->
            <div class="row g-4 mb-5">
                <!-- User Stats -->
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary">
                            <?php echo $reports['user_stats']['total_users']; ?>
                        </div>
                        <div class="fw-semibold">Total Users</div>
                        <small class="text-success">
                            +<?php echo $reports['user_stats']['new_users']; ?> new
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-success">
                            <?php echo $reports['active_mentorships']; ?>
                        </div>
                        <div class="fw-semibold">Active Mentorships</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning">
                            <?php echo $reports['resource_stats']['total_resources']; ?>
                        </div>
                        <div class="fw-semibold">Total Resources</div>
                        <small class="text-muted">
                            <?php echo number_format($reports['resource_stats']['total_storage_bytes'] / 1024 / 1024, 1); ?>MB stored
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info">
                            <?php echo $reports['message_stats']['total_messages']; ?>
                        </div>
                        <div class="fw-semibold">Messages Sent</div>
                        <small class="text-muted">
                            <?php echo $reports['message_stats']['active_conversations']; ?> conversations
                        </small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Mentorship Request Stats -->
                <div class="col-lg-6">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-handshake me-2 text-primary"></i>
                                Mentorship Requests
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <canvas id="mentorshipChart" height="200"></canvas>
                            <div class="row text-center mt-3">
                                <div class="col-4">
                                    <small class="text-muted">Pending</small>
                                    <div class="fw-bold text-warning">
                                        <?php echo $reports['mentorship_stats']['pending_requests']; ?>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Accepted</small>
                                    <div class="fw-bold text-success">
                                        <?php echo $reports['mentorship_stats']['accepted_requests']; ?>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Rejected</small>
                                    <div class="fw-bold text-danger">
                                        <?php echo $reports['mentorship_stats']['rejected_requests']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Distribution -->
                <div class="col-lg-6">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-university me-2 text-success"></i>
                                Department Distribution
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <canvas id="departmentChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Mentors -->
                <div class="col-lg-6">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-star me-2 text-warning"></i>
                                Top Mentors
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <?php if (!empty($reports['top_mentors'])): ?>
                                <?php foreach ($reports['top_mentors'] as $mentor): ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div>
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($mentor['department']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $mentor['mentee_count']; ?> mentees
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No active mentors found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Registration Trends -->
                <div class="col-lg-6">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-users me-2 text-info"></i>
                                User Overview
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-number text-primary">
                                        <?php echo $reports['user_stats']['total_mentors']; ?>
                                    </div>
                                    <div class="fw-semibold">Mentors</div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number text-success">
                                        <?php echo $reports['user_stats']['total_mentees']; ?>
                                    </div>
                                    <div class="fw-semibold">Mentees</div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Email Verification Rate</small>
                                <div class="progress mt-2">
                                    <?php 
                                    $verificationRate = $reports['user_stats']['total_users'] > 0 
                                        ? ($reports['user_stats']['verified_users'] / $reports['user_stats']['total_users']) * 100 
                                        : 0;
                                    ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo $verificationRate; ?>%">
                                        <?php echo round($verificationRate, 1); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($userRole === 'mentor'): ?>
            <!-- Mentor Reports -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary">
                            <?php echo $reports['mentorship_stats']['active_mentorships']; ?>
                        </div>
                        <div class="fw-semibold">Active Mentees</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning">
                            <?php echo $reports['mentorship_stats']['pending_requests']; ?>
                        </div>
                        <div class="fw-semibold">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-success">
                            <?php echo $reports['resource_stats']['total_resources']; ?>
                        </div>
                        <div class="fw-semibold">Resources Shared</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info">
                            <?php echo $reports['resource_stats']['total_downloads']; ?>
                        </div>
                        <div class="fw-semibold">Total Downloads</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Current Mentees -->
                <div class="col-lg-8">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-users me-2 text-primary"></i>
                                Current Mentees Performance
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <?php if (!empty($reports['mentees'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mentee</th>
                                                <th>Department</th>
                                                <th>Duration</th>
                                                <th>Messages</th>
                                                <th>Downloads</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reports['mentees'] as $mentee): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <div class="fw-semibold">
                                                                <?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                Year <?php echo $mentee['year_of_study']; ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($mentee['department']); ?></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo $mentee['days_active']; ?> days
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">
                                                            <?php echo $mentee['messages_sent']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <?php echo $mentee['resources_downloaded']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No active mentees</h6>
                                    <p class="text-muted mb-0">You don't have any active mentees at the moment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Popular Resources -->
                <div class="col-lg-4">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-download me-2 text-success"></i>
                                Most Downloaded
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <?php if (!empty($reports['popular_resources'])): ?>
                                <?php foreach (array_slice($reports['popular_resources'], 0, 5) as $resource): ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars(substr($resource['title'], 0, 30)); ?>
                                                <?php echo strlen($resource['title']) > 30 ? '...' : ''; ?>
                                            </div>
                                            <?php if (!empty($resource['category'])): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($resource['category']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill ms-2">
                                            <?php echo $resource['download_count']; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No resources uploaded yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Mentee Reports -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary">
                            <?php echo $reports['progress_stats']['active_mentorships']; ?>
                        </div>
                        <div class="fw-semibold">Active Mentors</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-success">
                            <?php echo $reports['progress_stats']['completed_mentorships']; ?>
                        </div>
                        <div class="fw-semibold">Completed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info">
                            <?php echo $reports['resource_usage']['resources_downloaded']; ?>
                        </div>
                        <div class="fw-semibold">Resources Used</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning">
                            <?php echo $reports['resource_usage']['mentors_learned_from']; ?>
                        </div>
                        <div class="fw-semibold">Mentors Learned From</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Current Mentors -->
                <div class="col-lg-8">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>
                                Your Mentors
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <?php if (!empty($reports['mentors'])): ?>
                                <?php foreach ($reports['mentors'] as $mentor): ?>
                                    <div class="d-flex align-items-center p-3 border rounded mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold">
                                                <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                            </h6>
                                            <p class="text-muted mb-1">
                                                <?php echo htmlspecialchars($mentor['department']); ?>
                                            </p>
                                            <small class="text-muted">
                                                Mentoring for <?php echo $mentor['days_active']; ?> days
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <small class="text-muted">Messages:</small>
                                                <span class="badge bg-primary"><?php echo $mentor['total_messages']; ?></span>
                                            </div>
                                            <div>
                                                <small class="text-muted">Resources:</small>
                                                <span class="badge bg-success"><?php echo $mentor['available_resources']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No active mentors</h6>
                                    <p class="text-muted mb-0">Start by requesting mentorship from available mentors.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-4">
                    <div class="card border-radius-lg shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-clock me-2 text-info"></i>
                                Recent Activity
                            </h5>
                        </div>
                        <div class="card-body px-4">
                            <?php if (!empty($reports['recent_activity'])): ?>
                                <?php foreach (array_slice($reports['recent_activity'], 0, 10) as $activity): ?>
                                    <div class="d-flex align-items-start py-2 border-bottom">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($activity['activity_type'] === 'message'): ?>
                                                <i class="fas fa-comment text-primary"></i>
                                            <?php else: ?>
                                                <i class="fas fa-download text-success"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($activity['activity']); ?>
                                            </div>
                                            <small class="text-muted">
                                                with <?php echo htmlspecialchars($activity['related_person']); ?>
                                            </small>
                                            <div class="text-muted small">
                                                <?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No recent activity</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        <?php if ($userRole === 'admin'): ?>
            // Mentorship Requests Chart
            const mentorshipCtx = document.getElementById('mentorshipChart').getContext('2d');
            new Chart(mentorshipCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Accepted', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $reports['mentorship_stats']['pending_requests']; ?>,
                            <?php echo $reports['mentorship_stats']['accepted_requests']; ?>,
                            <?php echo $reports['mentorship_stats']['rejected_requests']; ?>
                        ],
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545']
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

            // Department Distribution Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            new Chart(departmentCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo "'" . implode("','", array_map(function($d) { return $d['department']; }, $reports['department_stats'])) . "'"; ?>],
                    datasets: [{
                        label: 'Users',
                        data: [<?php echo implode(',', array_map(function($d) { return $d['user_count']; }, $reports['department_stats'])); ?>],
                        backgroundColor: '#0066cc'
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
        <?php endif; ?>
    </script>
</body>
</html>