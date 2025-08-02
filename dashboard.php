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
$messageModel = new Message();
$notificationModel = new Notification();

// Get current user data
$currentUser = $userModel->getUserById($userId);
if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

// Get user statistics
$stats = $userModel->getUserStats($userId);

// Get active mentorships
$activeMentorships = $mentorshipModel->getActiveMentorships($userId, $userRole);

// Get recent requests
if ($userRole === 'mentor') {
    $recentRequests = $mentorshipModel->getMentorRequests($userId, 'pending');
} else {
    $recentRequests = $mentorshipModel->getMenteeRequests($userId);
}

// Get recent conversations
$conversations = $messageModel->getConversations($userId);

// Get notifications
$unreadNotifications = $notificationModel->getUnreadNotifications($userId, 5);
$unreadCount = $notificationModel->getUnreadCount($userId);

$pageTitle = 'Dashboard - Menteego';
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
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                <?php if ($unreadCount > 0): ?>
                                    <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                                        Mark all read
                                    </button>
                                <?php endif; ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notificationsList">
                                <?php if (empty($unreadNotifications)): ?>
                                    <li class="dropdown-item text-center text-muted py-3">
                                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                        <p class="mb-0">No new notifications</p>
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($unreadNotifications as $notification): ?>
                                        <li class="dropdown-item notification-item" data-notification-id="<?php echo $notification['id']; ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <i class="<?php echo $notificationModel->getNotificationIcon($notification['type']); ?> text-<?php echo $notificationModel->getNotificationColor($notification['type']); ?>"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <small class="text-muted"><?php echo $notificationModel->formatTimeAgo($notification['created_at']); ?></small>
                                                </div>
                                                <div class="flex-shrink-0 ms-2">
                                                    <button class="btn btn-sm btn-outline-danger delete-notification" data-notification-id="<?php echo $notification['id']; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-item text-center">
                                <a href="/notifications.php" class="text-decoration-none">
                                    View all notifications
                                </a>
                            </li>
                        </ul>
                    </li>
                    
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
                        <?php echo $unreadCount; ?>
                    </div>
                    <div class="fw-semibold">
                        New Notifications
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
                                            <?php if ($userRole === 'mentee'): ?>
                                                <a href="/view-mentor-availability.php?mentor_id=<?php echo $mentorship['mentor_id']; ?>" 
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-calendar-alt"></i> Availability
                                                </a>
                                            <?php endif; ?>
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
                                <?php if (!empty($activeMentorships)): ?>
                                    <a href="/view-mentor-availability.php" class="btn btn-outline-success">
                                        <i class="fas fa-calendar-alt me-2"></i>View Mentor Availability
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($userRole === 'mentor'): ?>
                                <a href="/mentor-request.php" class="btn btn-outline-primary">
                                    <i class="fas fa-inbox me-2"></i>View Requests
                                </a>
                                <a href="/availability.php" class="btn btn-outline-success">
                                    <i class="fas fa-calendar me-2"></i>Set Availability
                                </a>
                            <?php elseif ($userRole === 'admin'): ?>
                                <a href="/admin/reports.php" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-bar me-2"></i>System Reports
                                </a>
                                <a href="/reports.php" class="btn btn-outline-warning">
                                    <i class="fas fa-file-alt me-2"></i>View Reports
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
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Notification JavaScript -->
    <script>
        // Set current user ID for JavaScript
        window.currentUserId = <?php echo $userId; ?>;
        
        // Notification functionality
        class NotificationManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.startPolling();
            }
            
            bindEvents() {
                // Mark all as read
                document.getElementById('markAllRead')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
                
                // Mark individual notification as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        if (!e.target.closest('.delete-notification')) {
                            const notificationId = item.dataset.notificationId;
                            this.markAsRead(notificationId);
                        }
                    });
                });
                
                // Delete notification
                document.querySelectorAll('.delete-notification').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const notificationId = btn.dataset.notificationId;
                        this.deleteNotification(notificationId);
                    });
                });
            }
            
            async markAsRead(notificationId) {
                try {
                    const response = await fetch('/api/notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'mark_read',
                            notification_id: notificationId
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Remove the notification from the list
                        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.remove();
                        }
                        this.updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }
            
            async markAllAsRead() {
                try {
                    const response = await fetch('/api/notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'mark_all_read'
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Clear all notifications from the list
                        const notificationsList = document.getElementById('notificationsList');
                        notificationsList.innerHTML = `
                            <li class="dropdown-item text-center text-muted py-3">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0">No new notifications</p>
                            </li>
                        `;
                        this.updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Error marking all notifications as read:', error);
                }
            }
            
            async deleteNotification(notificationId) {
                try {
                    const response = await fetch('/api/notifications.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            notification_id: notificationId
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Remove the notification from the list
                        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.remove();
                        }
                        this.updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Error deleting notification:', error);
                }
            }
            
            async updateNotificationCount() {
                try {
                    const response = await fetch('/api/notifications.php?action=count');
                    const data = await response.json();
                    
                    if (data.success) {
                        const badge = document.querySelector('#notificationsDropdown .badge');
                        if (data.count > 0) {
                            if (badge) {
                                badge.textContent = data.count;
                            } else {
                                // Create badge if it doesn't exist
                                const dropdown = document.getElementById('notificationsDropdown');
                                const newBadge = document.createElement('span');
                                newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                                newBadge.textContent = data.count;
                                dropdown.appendChild(newBadge);
                            }
                        } else {
                            // Remove badge if count is 0
                            if (badge) {
                                badge.remove();
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error updating notification count:', error);
                }
            }
            
            startPolling() {
                // Poll for new notifications every 30 seconds
                setInterval(() => {
                    this.updateNotificationCount();
                }, 30000);
            }
        }
        
        // Initialize notification manager
        document.addEventListener('DOMContentLoaded', () => {
            new NotificationManager();
        });
        
        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            // This would make an AJAX call to refresh stats
            // Implementation depends on your API structure
        }, 30000);
    </script>
</body>
</html>