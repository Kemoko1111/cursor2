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

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($action) {
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'delete_message', 'messages', ?, 'Message deleted', ?)");
                $logStmt->execute([$userId, $message_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Message deleted successfully";
                break;
        }
    } catch (Exception $e) {
        error_log("Error in message action: " . $e->getMessage());
        $error = "Failed to perform action";
    }
}

// Get filter parameters
$mentorship_filter = $_GET['mentorship'] ?? '';
$sender_filter = $_GET['sender'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Admin functions
function getAllMessages($mentorship = '', $sender = '', $search = '', $limit = 20, $offset = 0) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($mentorship)) {
            $where_conditions[] = "m.mentorship_id = ?";
            $params[] = $mentorship;
        }
        
        if (!empty($sender)) {
            $where_conditions[] = "sender.id = ?";
            $params[] = $sender;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "m.message LIKE ?";
            $params[] = "%$search%";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT m.*, 
                         sender.first_name as sender_first_name, sender.last_name as sender_last_name, sender.email as sender_email,
                         receiver.first_name as receiver_first_name, receiver.last_name as receiver_last_name, receiver.email as receiver_email,
                         ms.mentor_id, ms.mentee_id
                  FROM messages m
                  JOIN users sender ON m.sender_id = sender.id
                  JOIN users receiver ON m.receiver_id = receiver.id
                  JOIN mentorships ms ON m.mentorship_id = ms.id
                  $where_clause
                  ORDER BY m.created_at DESC
                  LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting messages: " . $e->getMessage());
        return [];
    }
}

function getTotalMessages($mentorship = '', $sender = '', $search = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($mentorship)) {
            $where_conditions[] = "m.mentorship_id = ?";
            $params[] = $mentorship;
        }
        
        if (!empty($sender)) {
            $where_conditions[] = "sender.id = ?";
            $params[] = $sender;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "m.message LIKE ?";
            $params[] = "%$search%";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total 
                  FROM messages m
                  JOIN users sender ON m.sender_id = sender.id
                  JOIN users receiver ON m.receiver_id = receiver.id
                  JOIN mentorships ms ON m.mentorship_id = ms.id
                  $where_clause";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error getting total messages: " . $e->getMessage());
        return 0;
    }
}

function getMessageStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        
        // Total messages
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Today's messages
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM messages WHERE DATE(created_at) = CURRENT_DATE");
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['today'];
        
        // This week's messages
        $stmt = $pdo->query("SELECT COUNT(*) as week FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['week'] = $stmt->fetch(PDO::FETCH_ASSOC)['week'];
        
        // Unread messages
        $stmt = $pdo->query("SELECT COUNT(*) as unread FROM messages WHERE is_read = 0");
        $stats['unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting message stats: " . $e->getMessage());
        return [];
    }
}

function getMentorships() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT ms.id, 
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name,
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name
                  FROM mentorships ms
                  JOIN users mentor ON ms.mentor_id = mentor.id
                  JOIN users mentee ON ms.mentee_id = mentee.id
                  WHERE ms.status = 'active'
                  ORDER BY ms.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting mentorships: " . $e->getMessage());
        return [];
    }
}

function getUsers() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT id, first_name, last_name, email FROM users WHERE status = 'active' ORDER BY first_name, last_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting users: " . $e->getMessage());
        return [];
    }
}

// Get data
$messages = getAllMessages($mentorship_filter, $sender_filter, $search, $per_page, $offset);
$total_messages = getTotalMessages($mentorship_filter, $sender_filter, $search);
$message_stats = getMessageStats();
$mentorships = getMentorships();
$users = getUsers();
$total_pages = ceil($total_messages / $per_page);

$pageTitle = 'Message Management - Admin Dashboard';
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
        .message-card {
            transition: transform 0.2s ease;
        }
        .message-card:hover {
            transform: translateY(-2px);
        }
        .search-filters {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 1rem;
            color: white;
        }
        .message-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                        <a class="nav-link active" href="/admin/messages.php">
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
                        <h2 class="mb-1">Message Management</h2>
                        <p class="text-muted mb-0">Monitor and manage platform communications</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo number_format($total_messages); ?> Total Messages</span>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($message_stats['total'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Total Messages</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-comments"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($message_stats['today'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Today</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-day"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($message_stats['week'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">This Week</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-week"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($message_stats['unread'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Unread</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body search-filters">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search message content">
                            </div>
                            <div class="col-md-3">
                                <label for="mentorship" class="form-label">Mentorship</label>
                                <select class="form-select" id="mentorship" name="mentorship">
                                    <option value="">All Mentorships</option>
                                    <?php foreach ($mentorships as $mentorship): ?>
                                        <option value="<?php echo $mentorship['id']; ?>" 
                                                <?php echo $mentorship_filter == $mentorship['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mentorship['mentor_first_name'] . ' ↔ ' . $mentorship['mentee_first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sender" class="form-label">Sender</label>
                                <select class="form-select" id="sender" name="sender">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $sender_filter == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="/admin/messages.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Messages List -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-comments me-2"></i>Messages List
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No messages found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Message</th>
                                            <th>Sender</th>
                                            <th>Receiver</th>
                                            <th>Mentorship</th>
                                            <th>Status</th>
                                            <th>Sent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                            <tr class="message-card">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-comment text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <div class="message-content" title="<?php echo htmlspecialchars($message['message']); ?>">
                                                                <?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <?php echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($message['sender_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <?php echo htmlspecialchars($message['receiver_first_name'] . ' ' . $message['receiver_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($message['receiver_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        Mentorship #<?php echo $message['mentorship_id']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                        <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewMessage(<?php echo $message['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteMessage(<?php echo $message['id']; ?>)">
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Messages pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewMessage(messageId) {
            // Implement message view modal
            alert('View message details for ID: ' + messageId);
        }
        
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="${messageId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>