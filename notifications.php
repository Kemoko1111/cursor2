<?php
require_once 'config/app.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userId = $_SESSION['user_id'];
$notificationModel = new Notification();

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get notifications based on filter
switch ($filter) {
    case 'unread':
        $notifications = $notificationModel->getUnreadNotifications($userId, $perPage);
        break;
    case 'mentorship':
        $notifications = $notificationModel->getNotificationsByType($userId, ['mentorship_request', 'request_accepted', 'request_rejected'], $perPage, $offset);
        break;
    case 'messages':
        $notifications = $notificationModel->getNotificationsByType($userId, ['new_message'], $perPage, $offset);
        break;
    case 'system':
        $notifications = $notificationModel->getNotificationsByType($userId, ['system_announcement'], $perPage, $offset);
        break;
    default:
        $notifications = $notificationModel->getAllNotifications($userId, $perPage);
        break;
}

// Get counts for filter tabs
$unreadCount = $notificationModel->getUnreadCount($userId);
$totalCount = $notificationModel->getTotalCount($userId);

$pageTitle = 'Notifications - Menteego';
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

    <!-- Page Header -->
    <section class="py-4 bg-white border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-bell me-2 text-primary"></i>Notifications
                    </h1>
                    <p class="text-muted mb-0">
                        Stay updated with your mentorship activities
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button class="btn btn-outline-primary" id="markAllRead">
                            <i class="fas fa-check-double me-2"></i>Mark All Read
                        </button>
                        <a href="/dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Filter Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="notificationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                           href="?filter=all" role="tab">
                            All Notifications
                            <span class="badge bg-secondary ms-1"><?php echo $totalCount; ?></span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" 
                           href="?filter=unread" role="tab">
                            Unread
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $filter === 'mentorship' ? 'active' : ''; ?>" 
                           href="?filter=mentorship" role="tab">
                            Mentorship
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $filter === 'messages' ? 'active' : ''; ?>" 
                           href="?filter=messages" role="tab">
                            Messages
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $filter === 'system' ? 'active' : ''; ?>" 
                           href="?filter=system" role="tab">
                            System
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="row">
            <div class="col-12">
                <div class="card border-radius-lg shadow-sm">
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No notifications found</h5>
                                <p class="text-muted mb-0">
                                    <?php if ($filter === 'unread'): ?>
                                        You're all caught up! No unread notifications.
                                    <?php elseif ($filter === 'all'): ?>
                                        You haven't received any notifications yet.
                                    <?php else: ?>
                                        No <?php echo $filter; ?> notifications found.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item notification-item <?php echo !$notification['is_read'] ? 'unread-notification' : ''; ?>" 
                                         data-notification-id="<?php echo $notification['id']; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="notification-icon">
                                                    <i class="<?php echo $notificationModel->getNotificationIcon($notification['type']); ?> text-<?php echo $notificationModel->getNotificationColor($notification['type']); ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-1 fw-semibold <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                                        <?php echo htmlspecialchars($notification['title']); ?>
                                                    </h6>
                                                    <div class="d-flex gap-2">
                                                        <?php if (!$notification['is_read']): ?>
                                                            <button class="btn btn-sm btn-outline-primary mark-read-btn" 
                                                                    data-notification-id="<?php echo $notification['id']; ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-outline-danger delete-notification-btn" 
                                                                data-notification-id="<?php echo $notification['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php echo $notificationModel->formatTimeAgo($notification['created_at']); ?>
                                                    </small>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="badge bg-primary">New</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if (count($notifications) >= $perPage): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Notifications pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link"><?php echo $page; ?></span>
                            </li>
                            
                            <li class="page-item">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Notification JavaScript -->
    <script>
        class NotificationPageManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.bindEvents();
            }
            
            bindEvents() {
                // Mark all as read
                document.getElementById('markAllRead')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
                
                // Mark individual notification as read
                document.querySelectorAll('.mark-read-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const notificationId = btn.dataset.notificationId;
                        this.markAsRead(notificationId);
                    });
                });
                
                // Delete notification
                document.querySelectorAll('.delete-notification-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (confirm('Are you sure you want to delete this notification?')) {
                            const notificationId = btn.dataset.notificationId;
                            this.deleteNotification(notificationId);
                        }
                    });
                });
                
                // Click on notification item
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        if (!e.target.closest('.mark-read-btn') && !e.target.closest('.delete-notification-btn')) {
                            const notificationId = item.dataset.notificationId;
                            this.markAsRead(notificationId);
                        }
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
                        // Update the notification item
                        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.classList.remove('unread-notification');
                            notificationItem.querySelector('.fw-bold')?.classList.remove('fw-bold');
                            notificationItem.querySelector('.badge')?.remove();
                            notificationItem.querySelector('.mark-read-btn')?.remove();
                        }
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
                        // Update all notification items
                        document.querySelectorAll('.notification-item').forEach(item => {
                            item.classList.remove('unread-notification');
                            item.querySelector('.fw-bold')?.classList.remove('fw-bold');
                            item.querySelector('.badge')?.remove();
                            item.querySelector('.mark-read-btn')?.remove();
                        });
                        
                        // Show success message
                        this.showAlert('All notifications marked as read!', 'success');
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
                        // Remove the notification item
                        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.remove();
                        }
                        
                        // Show success message
                        this.showAlert('Notification deleted successfully!', 'success');
                    }
                } catch (error) {
                    console.error('Error deleting notification:', error);
                }
            }
            
            showAlert(message, type = 'info') {
                // Create alert element
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(alertDiv);
                
                // Auto-remove after 3 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            }
        }
        
        // Initialize notification page manager
        document.addEventListener('DOMContentLoaded', () => {
            new NotificationPageManager();
        });
    </script>
    
    <style>
        .notification-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .unread-notification {
            background-color: #f0f8ff;
            border-left: 4px solid #0066cc;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mark-read-btn, .delete-notification-btn {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .notification-item:hover .mark-read-btn,
        .notification-item:hover .delete-notification-btn {
            opacity: 1;
        }
    </style>
</body>
</html>