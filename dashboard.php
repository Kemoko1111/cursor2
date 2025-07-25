<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);
if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$mentorshipModel = new Mentorship();
$messageModel = new Message();

// Handle mentorship deletion
$deleteSuccess = '';
$deleteError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mentorship_id'])) {
    $mentorshipId = intval($_POST['delete_mentorship_id']);
    if ($mentorshipModel->deleteMentorship($mentorshipId, $userId)) {
        $deleteSuccess = 'Mentorship deleted successfully.';
        // Refresh active mentorships after deletion
        $activeMentorships = $mentorshipModel->getActiveMentorships($userId, $userRole);
    } else {
        $deleteError = 'Failed to delete mentorship or unauthorized.';
    }
}

// Get user statistics
$stats = $userModel->getUserStats($userId);

// Get active mentorships (if not already refreshed)
if (!isset($activeMentorships)) {
    $activeMentorships = $mentorshipModel->getActiveMentorships($userId, $userRole);
}

// Get recent requests
if ($userRole === 'mentor') {
    $recentRequests = $mentorshipModel->getMentorRequests($userId, 'pending');
} else {
    $recentRequests = $mentorshipModel->getMenteeRequests($userId);
}

// Get recent conversations
$conversations = $messageModel->getConversations($userId);

$pageTitle = 'Dashboard - Menteego';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/browse-mentors.php">
                            <i class="fas fa-search me-1"></i>Find Mentors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="/messages.php">
                            <i class="fas fa-comments me-1"></i>Messages
                            <?php if ($stats['unread_messages'] > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $stats['unread_messages']; ?>
                                </span>
                            <?php endif; ?>
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
                            <li><a class="dropdown-item" href="/settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">
                        Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>! 👋
                    </h1>
                    <p class="mb-0 opacity-75">
                        <?php if ($userRole === 'mentor'): ?>
                            Ready to guide and inspire your mentees today?
                        <?php else: ?>
                            Continue your learning journey with your mentors.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <?php if ($userRole === 'mentee'): ?>
                            <a href="/browse-mentors.php" class="btn btn-warning">
                                <i class="fas fa-search me-2"></i>Find Mentors
                            </a>
                        <?php endif; ?>
                        <a href="/profile.php" class="btn btn-outline-light">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if ($deleteSuccess): ?>
            <div class="alert alert-success"> <?php echo $deleteSuccess; ?> </div>
        <?php elseif ($deleteError): ?>
            <div class="alert alert-danger"> <?php echo $deleteError; ?> </div>
        <?php endif; ?>
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-primary">
                        <?php echo $userRole === 'mentor' ? $stats['active_mentees'] : $stats['active_mentors']; ?>
                    </div>
                    <div class="fw-semibold">
                        Active <?php echo $userRole === 'mentor' ? 'Mentees' : 'Mentors'; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-warning">
                        <?php echo $stats['pending_requests']; ?>
                    </div>
                    <div class="fw-semibold">
                        Pending Requests
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-success">
                        <?php echo count($conversations); ?>
                    </div>
                    <div class="fw-semibold">
                        Active Conversations
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-info">
                        <?php echo $stats['unread_messages']; ?>
                    </div>
                    <div class="fw-semibold">
                        Unread Messages
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Active Mentorships -->
            <div class="col-lg-8">
                <div class="card border-radius-lg shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Active Mentorships
                        </h5>
                    </div>
                    <div class="card-body px-4">
                        <?php if (empty($activeMentorships)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No active mentorships yet</h6>
                                <p class="text-muted mb-0">
                                    <?php if ($userRole === 'mentee'): ?>
                                        Start by browsing and requesting mentors.
                                    <?php else: ?>
                                        Wait for mentorship requests from mentees.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeMentorships as $mentorship): ?>
                                <div class="d-flex align-items-center p-3 border rounded mb-3 shadow-hover">
                                    <img src="<?php echo $mentorship['profile_image'] ? 'uploads/profiles/' . $mentorship['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle me-3" width="60" height="60" alt="">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold">
                                            <?php echo htmlspecialchars($mentorship['first_name'] . ' ' . $mentorship['last_name']); ?>
                                        </h6>
                                        <p class="text-muted mb-1">
                                            <?php echo htmlspecialchars($mentorship['department']); ?> • 
                                            <?php echo ucfirst($mentorship['year_of_study']); ?> Year
                                        </p>
                                        <small class="text-muted">
                                            Started: <?php echo date('M j, Y', strtotime($mentorship['start_date'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($mentorship['unread_messages'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill mb-2">
                                                <?php echo $mentorship['unread_messages']; ?> new
                                            </span>
                                        <?php endif; ?>
                                        <div>
                                            <a href="/messages.php?mentorship=<?php echo $mentorship['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-comments"></i> Message
                                            </a>
                                            <!-- View button for mentor/mentee profile -->
                                            <a href="/profile.php?id=<?php
                                                echo ($userRole === 'mentor') ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
                                            ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <!-- Delete button for mentorship -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this mentorship?');">
                                                <input type="hidden" name="delete_mentorship_id" value="<?php echo $mentorship['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Requests -->
                <div class="card border-radius-lg shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h6 class="card-title fw-bold mb-0">
                            <i class="fas fa-paper-plane me-2 text-warning"></i>
                            Recent Requests
                        </h6>
                    </div>
                    <div class="card-body px-4">
                        <?php if (empty($recentRequests)): ?>
                            <p class="text-muted text-center py-3 mb-0">No recent requests</p>
                        <?php else: ?>
                            <?php foreach (array_slice($recentRequests, 0, 3) as $request): ?>
                                <div class="d-flex align-items-center py-2 border-bottom">
                                    <img src="<?php echo $request['profile_image'] ? 'uploads/profiles/' . $request['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle me-3" width="40" height="40" alt="">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold fs-6">
                                            <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M j', strtotime($request['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'accepted' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card border-radius-lg shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h6 class="card-title fw-bold mb-0">
                            <i class="fas fa-bolt me-2 text-success"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body px-4">
                        <div class="d-grid gap-2">
                            <?php if ($userRole === 'mentee'): ?>
                                <a href="/browse-mentors.php" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-2"></i>Browse Mentors
                                </a>
                                <a href="/requests.php" class="btn btn-outline-warning">
                                    <i class="fas fa-paper-plane me-2"></i>My Requests
                                </a>
                            <?php else: ?>
                                <a href="/requests.php" class="btn btn-outline-primary">
                                    <i class="fas fa-inbox me-2"></i>View Requests
                                </a>
                                <a href="/availability.php" class="btn btn-outline-success">
                                    <i class="fas fa-calendar me-2"></i>Set Availability
                                </a>
                            <?php endif; ?>
                            <a href="/messages.php" class="btn btn-outline-info">
                                <i class="fas fa-comments me-2"></i>All Messages
                            </a>
                            <a href="/profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        window.currentUserId = <?php echo $userId; ?>;
        setInterval(function() {
            // This would make an AJAX call to refresh stats
        }, 30000);
    </script>
</body>
</html>