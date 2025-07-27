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
function getAllUsers($page = 1, $limit = 20, $search = '', $role = '', $department = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($role)) {
            $whereConditions[] = "user_role = ?";
            $params[] = $role;
        }
        
        if (!empty($department)) {
            $whereConditions[] = "department = ?";
            $params[] = $department;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT u.*, 
                         (SELECT COUNT(*) FROM mentorships WHERE mentor_id = u.id AND status = 'active') as active_mentorships,
                         (SELECT COUNT(*) FROM mentorship_requests WHERE mentee_id = u.id AND status = 'pending') as pending_requests
                  FROM users u 
                  $whereClause
                  ORDER BY u.created_at DESC 
                  LIMIT ? OFFSET ?";
        
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

function getTotalUsers($search = '', $role = '', $department = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($role)) {
            $whereConditions[] = "user_role = ?";
            $params[] = $role;
        }
        
        if (!empty($department)) {
            $whereConditions[] = "department = ?";
            $params[] = $department;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM users $whereClause";
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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    if ($action === 'suspend' || $action === 'activate') {
        // Update user status
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $status = ($action === 'suspend') ? 'suspended' : 'active';
            $query = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$status, $userId]);
            
            $message = ($action === 'suspend') ? 'User suspended successfully' : 'User activated successfully';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating user status';
            $messageType = 'danger';
        }
    }
}

// Get filter parameters
$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$department = $_GET['department'] ?? '';

// Get data
$users = getAllUsers($page, 20, $search, $role, $department);
$totalUsers = getTotalUsers($search, $role, $department);
$departments = getDepartments();
$totalPages = ceil($totalUsers / 20);

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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            font-size: 0.75rem;
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
                        <p class="text-muted mb-0">Manage all platform users</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo $totalUsers; ?> Total Users</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name or email">
                            </div>
                            <div class="col-md-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="">All Roles</option>
                                    <option value="mentor" <?php echo $role === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                                    <option value="mentee" <?php echo $role === 'mentee' ? 'selected' : ''; ?>>Mentee</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                                <?php echo $department === $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                </div>
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
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Activity</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo $user['profile_image'] ? 'uploads/profiles/' . $user['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                                             class="user-avatar me-3" alt="">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['user_role'] === 'mentor' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($user['user_role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success status-badge">Active</span>
                                                </td>
                                                <td>
                                                    <?php if ($user['user_role'] === 'mentor'): ?>
                                                        <small class="text-muted">
                                                            <?php echo $user['active_mentorships']; ?> active mentorships
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">
                                                            <?php echo $user['pending_requests']; ?> pending requests
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="viewUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="suspendUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-ban"></i>
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
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&department=<?php echo urlencode($department); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&department=<?php echo urlencode($department); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&department=<?php echo urlencode($department); ?>">
                                        Next
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
            alert('View user ' + userId);
        }
        
        function editUser(userId) {
            // Implement user edit modal
            alert('Edit user ' + userId);
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
    </script>
</body>
</html>