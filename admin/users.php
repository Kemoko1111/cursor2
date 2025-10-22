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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($action) {
            case 'suspend':
                $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'suspend_user', 'users', ?, 'User suspended', ?)");
                $logStmt->execute([$userId, $user_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "User suspended successfully";
                break;
                
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'activate_user', 'users', ?, 'User activated', ?)");
                $logStmt->execute([$userId, $user_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "User activated successfully";
                break;
        }
    } catch (Exception $e) {
        error_log("Error in user action: " . $e->getMessage());
        $error = "Failed to perform action";
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Admin functions
function getAllUsers($search = '', $role = '', $status = '', $department = '', $limit = 20, $offset = 0) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($role)) {
            $where_conditions[] = "role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (!empty($department)) {
            $where_conditions[] = "department = ?";
            $params[] = $department;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting users: " . $e->getMessage());
        return [];
    }
}

function getTotalUsers($search = '', $role = '', $status = '', $department = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }
        
        if (!empty($role)) {
            $where_conditions[] = "role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (!empty($department)) {
            $where_conditions[] = "department = ?";
            $params[] = $department;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM users $where_clause";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error getting total users: " . $e->getMessage());
        return 0;
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
$users = getAllUsers($search, $role_filter, $status_filter, $department_filter, $per_page, $offset);
$total_users = getTotalUsers($search, $role_filter, $status_filter, $department_filter);
$departments = getDepartments();
$total_pages = ceil($total_users / $per_page);

$pageTitle = 'User Management - Admin Dashboard';
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
        .user-card {
            transition: transform 0.2s ease;
        }
        .user-card:hover {
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
                        <a class="nav-link active" href="/admin/users.php">
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
                        <h2 class="mb-1">User Management</h2>
                        <p class="text-muted mb-0">Manage platform users and their accounts</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo number_format($total_users); ?> Total Users</span>
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

                <!-- Search and Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body search-filters">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, email, or student ID">
                            </div>
                            <div class="col-md-2">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <option value="mentee" <?php echo $role_filter === 'mentee' ? 'selected' : ''; ?>>Mentee</option>
                                    <option value="mentor" <?php echo $role_filter === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
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
                                <a href="/admin/users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Users List
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($users)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No users found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Department</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr class="user-card">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-user text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($user['student_id']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($user['email']); ?></div>
                                                        <?php if (!empty($user['phone'])): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($user['department']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'mentor' ? 'success' : ($user['role'] === 'admin' ? 'danger' : 'info'); ?> status-badge">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'suspended' ? 'danger' : 'warning'); ?> status-badge">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['status'] === 'active'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                    onclick="suspendUser(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    onclick="activateUser(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-check"></i>
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
                    <nav aria-label="Users pagination" class="mt-4">
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
        function viewUser(userId) {
            // Implement user view modal
            alert('View user details for ID: ' + userId);
        }
        
        function editUser(userId) {
            // Implement user edit modal
            alert('Edit user for ID: ' + userId);
        }
        
        function suspendUser(userId) {
            if (confirm('Are you sure you want to suspend this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function activateUser(userId) {
            if (confirm('Are you sure you want to activate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>