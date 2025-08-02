<?php
require_once 'config/app.php';
require_once 'models/User.php';
require_once 'models/Mentorship.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userModel = new User();
$mentorshipModel = new Mentorship();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

if ($currentUser['role'] !== 'mentee') {
    // Only mentees can view mentor availability
    header('HTTP/1.1 403 Forbidden');
    echo "<h2>Access Denied</h2><p>Only mentees can view mentor availability.</p>";
    exit;
}

// Get mentor ID from URL parameter or from active mentorship
$mentorId = $_GET['mentor_id'] ?? null;

if (!$mentorId) {
    // Try to get mentor from active mentorship
    $activeMentorship = $mentorshipModel->getActiveMentorships($_SESSION['user_id'], 'mentee');
    if (!empty($activeMentorship)) {
        $mentorId = $activeMentorship[0]['mentor_id'];
    } else {
        $error = "No mentor found. Please ensure you have an active mentorship.";
    }
}

$mentor = null;
$availability = [];
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

if ($mentorId) {
    $mentor = $userModel->getUserById($mentorId);
    if ($mentor && $mentor['role'] === 'mentor') {
        // Fetch mentor's availability
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM availability WHERE user_id = :user_id ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
        $stmt->bindParam(':user_id', $mentorId);
        $stmt->execute();
        
        foreach ($stmt->fetchAll() as $row) {
            $availability[$row['day_of_week']] = $row;
        }
    } else {
        $error = "Invalid mentor or mentor not found.";
    }
}

$pageTitle = 'Mentor Availability - Menteego';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .availability-card {
            transition: transform 0.2s ease;
        }
        .availability-card:hover {
            transform: translateY(-2px);
        }
        .day-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .time-slot {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .no-availability {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            color: #6c757d;
        }
        .mentor-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .availability-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                        <a class="nav-link" href="/browse-mentors.php">
                            <i class="fas fa-search me-1"></i>Find Mentors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/messages.php">
                            <i class="fas fa-comments me-1"></i>Messages
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

    <div class="container my-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mentor Availability</li>
                    </ol>
                </nav>
                <h1 class="fw-bold">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                    Mentor Availability
                </h1>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php elseif ($mentor): ?>
            <!-- Mentor Information -->
            <div class="mentor-info">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="<?php echo $mentor['profile_image'] ? 'uploads/profiles/' . $mentor['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                             class="rounded-circle" width="80" height="80" alt="Mentor">
                    </div>
                    <div class="col-md-10">
                        <h3 class="mb-2"><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h3>
                        <p class="mb-1">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo htmlspecialchars($mentor['department']); ?> • 
                            <?php echo ucfirst($mentor['year_of_study']); ?> Year
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($mentor['email']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Availability Schedule -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                Weekly Availability Schedule
                            </h5>
                            <p class="text-muted mb-0 mt-2">
                                Check when your mentor is available for sessions
                            </p>
                        </div>
                        <div class="card-body">
                            <div class="availability-grid">
                                <?php foreach ($days as $day): ?>
                                    <div class="card availability-card border-0 shadow-sm h-100">
                                        <div class="card-header day-header">
                                            <h6 class="mb-0 fw-bold text-capitalize">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                <?php echo ucfirst($day); ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (isset($availability[$day])): ?>
                                                <div class="time-slot">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-clock text-success me-2"></i>
                                                        <div>
                                                            <strong>Available:</strong><br>
                                                            <?php echo date('g:i A', strtotime($availability[$day]['start_time'])); ?> - 
                                                            <?php echo date('g:i A', strtotime($availability[$day]['end_time'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-availability">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                                        <span>Not available</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-bolt me-2 text-warning"></i>
                                Quick Actions
                            </h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="/messages.php?mentor=<?php echo $mentorId; ?>" class="btn btn-primary">
                                    <i class="fas fa-comments me-2"></i>Send Message
                                </a>
                                <a href="/schedule-session.php?mentor=<?php echo $mentorId; ?>" class="btn btn-success">
                                    <i class="fas fa-calendar-plus me-2"></i>Schedule Session
                                </a>
                                <a href="/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- No Mentor Found -->
            <div class="text-center py-5">
                <i class="fas fa-user-times fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No Mentor Found</h3>
                <p class="text-muted mb-4">
                    You don't have an active mentorship yet. Browse mentors to find someone who can help you.
                </p>
                <a href="/browse-mentors.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Mentors
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>