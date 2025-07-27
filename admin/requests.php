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

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = $_POST['request_id'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?");
                $stmt->execute([$request_id]);
                
                // Create mentorship
                $requestStmt = $pdo->prepare("SELECT * FROM mentorship_requests WHERE id = ?");
                $requestStmt->execute([$request_id]);
                $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request) {
                    $mentorshipStmt = $pdo->prepare("INSERT INTO mentorships (request_id, mentee_id, mentor_id, start_date, status, meeting_frequency) VALUES (?, ?, ?, CURRENT_DATE, 'active', ?)");
                    $mentorshipStmt->execute([$request_id, $request['mentee_id'], $request['mentor_id'], $request['preferred_meeting_type']]);
                }
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'approve_request', 'mentorship_requests', ?, 'Request approved and mentorship created', ?)");
                $logStmt->execute([$userId, $request_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Request approved and mentorship created successfully";
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'rejected', responded_at = NOW() WHERE id = ?");
                $stmt->execute([$request_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'reject_request', 'mentorship_requests', ?, 'Request rejected', ?)");
                $logStmt->execute([$userId, $request_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Request rejected successfully";
                break;
        }
    } catch (Exception $e) {
        error_log("Error in request action: " . $e->getMessage());
        $error = "Failed to perform action";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Admin functions
function getAllRequests($status = '', $department = '', $search = '', $limit = 15, $offset = 0) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($status)) {
            $where_conditions[] = "mr.status = ?";
            $params[] = $status;
        }
        
        if (!empty($department)) {
            $where_conditions[] = "(mentor.department = ? OR mentee.department = ?)";
            $params[] = $department;
            $params[] = $department;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(mentor.first_name LIKE ? OR mentor.last_name LIKE ? OR mentee.first_name LIKE ? OR mentee.last_name LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT mr.*, 
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name, mentor.email as mentor_email, mentor.department as mentor_department,
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name, mentee.email as mentee_email, mentee.department as mentee_department
                  FROM mentorship_requests mr
                  JOIN users mentor ON mr.mentor_id = mentor.id
                  JOIN users mentee ON mr.mentee_id = mentee.id
                  $where_clause
                  ORDER BY mr.created_at DESC
                  LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting requests: " . $e->getMessage());
        return [];
    }
}

function getTotalRequests($status = '', $department = '', $search = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($status)) {
            $where_conditions[] = "mr.status = ?";
            $params[] = $status;
        }
        
        if (!empty($department)) {
            $where_conditions[] = "(mentor.department = ? OR mentee.department = ?)";
            $params[] = $department;
            $params[] = $department;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(mentor.first_name LIKE ? OR mentor.last_name LIKE ? OR mentee.first_name LIKE ? OR mentee.last_name LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total 
                  FROM mentorship_requests mr
                  JOIN users mentor ON mr.mentor_id = mentor.id
                  JOIN users mentee ON mr.mentee_id = mentee.id
                  $where_clause";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error getting total requests: " . $e->getMessage());
        return 0;
    }
}

function getRequestStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        
        // Total requests
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM mentorship_requests");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM mentorship_requests WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // Accepted requests
        $stmt = $pdo->query("SELECT COUNT(*) as accepted FROM mentorship_requests WHERE status = 'accepted'");
        $stats['accepted'] = $stmt->fetch(PDO::FETCH_ASSOC)['accepted'];
        
        // Rejected requests
        $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM mentorship_requests WHERE status = 'rejected'");
        $stats['rejected'] = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting request stats: " . $e->getMessage());
        return [];
    }
}

function getDepartments() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error getting departments: " . $e->getMessage());
        return [];
    }
}

// Get data
$requests = getAllRequests($status_filter, $department_filter, $search, $per_page, $offset);
$total_requests = getTotalRequests($status_filter, $department_filter, $search);
$request_stats = getRequestStats();
$departments = getDepartments();
$total_pages = ceil($total_requests / $per_page);

$pageTitle = 'Request Management - Admin Dashboard';
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
        .request-card {
            transition: transform 0.2s ease;
        }
        .request-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.75rem;
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
                        <a class="nav-link active" href="/admin/requests.php">
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
                        <h2 class="mb-1">Request Management</h2>
                        <p class="text-muted mb-0">Monitor and manage mentorship requests</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo number_format($total_requests); ?> Total Requests</span>
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
                                        <h3 class="mb-1"><?php echo number_format($request_stats['total'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Total Requests</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clipboard-list"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($request_stats['pending'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Pending</p>
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
                                        <h3 class="mb-1"><?php echo number_format($request_stats['accepted'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Accepted</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($request_stats['rejected'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Rejected</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-times-circle"></i>
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
                                       placeholder="Search by mentor or mentee name">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                                <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="/admin/requests.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Requests List -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Requests List
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No requests found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Request</th>
                                            <th>Participants</th>
                                            <th>Message</th>
                                            <th>Goals</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr class="request-card">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-clipboard-list text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="mb-1">Request #<?php echo $request['id']; ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo ucfirst(str_replace('_', ' ', $request['preferred_meeting_type'])); ?> meeting
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($request['mentee_first_name'] . ' ' . $request['mentee_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['mentee_department']); ?></small>
                                                    </div>
                                                    <hr class="my-1">
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <i class="fas fa-user-tie me-1"></i>
                                                            <?php echo htmlspecialchars($request['mentor_first_name'] . ' ' . $request['mentor_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['mentor_department']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($request['message'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars(substr($request['message'] ?? 'No message', 0, 50)) . '...'; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($request['goals'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars(substr($request['goals'] ?? 'No goals specified', 0, 30)) . '...'; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'accepted' ? 'success' : 'danger'); ?> status-badge">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    onclick="approveRequest(<?php echo $request['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
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
                    <nav aria-label="Requests pagination" class="mt-4">
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
        function viewRequest(requestId) {
            // Implement request view modal
            alert('View request details for ID: ' + requestId);
        }
        
        function approveRequest(requestId) {
            if (confirm('Are you sure you want to approve this request? This will create a new mentorship.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="request_id" value="${requestId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectRequest(requestId) {
            if (confirm('Are you sure you want to reject this request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" value="${requestId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>