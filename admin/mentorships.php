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

// Handle mentorship actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mentorship_id = $_POST['mentorship_id'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($action) {
            case 'complete':
                $stmt = $pdo->prepare("UPDATE mentorships SET status = 'completed', end_date = CURRENT_DATE WHERE id = ?");
                $stmt->execute([$mentorship_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'complete_mentorship', 'mentorships', ?, 'Mentorship completed', ?)");
                $logStmt->execute([$userId, $mentorship_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Mentorship completed successfully";
                break;
                
            case 'cancel':
                $stmt = $pdo->prepare("UPDATE mentorships SET status = 'cancelled', end_date = CURRENT_DATE WHERE id = ?");
                $stmt->execute([$mentorship_id]);
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'cancel_mentorship', 'mentorships', ?, 'Mentorship cancelled', ?)");
                $logStmt->execute([$userId, $mentorship_id, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Mentorship cancelled successfully";
                break;
        }
    } catch (Exception $e) {
        error_log("Error in mentorship action: " . $e->getMessage());
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
function getAllMentorships($status = '', $department = '', $search = '', $limit = 15, $offset = 0) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($status)) {
            $where_conditions[] = "m.status = ?";
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
        
        $query = "SELECT m.*, 
                         mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name, mentor.email as mentor_email, mentor.department as mentor_department,
                         mentee.first_name as mentee_first_name, mentee.last_name as mentee_last_name, mentee.email as mentee_email, mentee.department as mentee_department,
                         mr.message as request_message, mr.goals as mentorship_goals
                  FROM mentorships m
                  JOIN users mentor ON m.mentor_id = mentor.id
                  JOIN users mentee ON m.mentee_id = mentee.id
                  JOIN mentorship_requests mr ON m.request_id = mr.id
                  $where_clause
                  ORDER BY m.created_at DESC
                  LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting mentorships: " . $e->getMessage());
        return [];
    }
}

function getTotalMentorships($status = '', $department = '', $search = '') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($status)) {
            $where_conditions[] = "m.status = ?";
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
                  FROM mentorships m
                  JOIN users mentor ON m.mentor_id = mentor.id
                  JOIN users mentee ON m.mentee_id = mentee.id
                  $where_clause";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error getting total mentorships: " . $e->getMessage());
        return 0;
    }
}

function getMentorshipStats() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        
        // Total mentorships
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM mentorships");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active mentorships
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM mentorships WHERE status = 'active'");
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // Completed mentorships
        $stmt = $pdo->query("SELECT COUNT(*) as completed FROM mentorships WHERE status = 'completed'");
        $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        // Cancelled mentorships
        $stmt = $pdo->query("SELECT COUNT(*) as cancelled FROM mentorships WHERE status = 'cancelled'");
        $stats['cancelled'] = $stmt->fetch(PDO::FETCH_ASSOC)['cancelled'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting mentorship stats: " . $e->getMessage());
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
$mentorships = getAllMentorships($status_filter, $department_filter, $search, $per_page, $offset);
$total_mentorships = getTotalMentorships($status_filter, $department_filter, $search);
$mentorship_stats = getMentorshipStats();
$departments = getDepartments();
$total_pages = ceil($total_mentorships / $per_page);

$pageTitle = 'Mentorship Management - Admin Dashboard';
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
        .mentorship-card {
            transition: transform 0.2s ease;
        }
        .mentorship-card:hover {
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
                        <a class="nav-link active" href="/admin/mentorships.php">
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
                        <h2 class="mb-1">Mentorship Management</h2>
                        <p class="text-muted mb-0">Monitor and manage active mentorship relationships</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary"><?php echo number_format($total_mentorships); ?> Total Mentorships</span>
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
                                        <h3 class="mb-1"><?php echo number_format($mentorship_stats['total'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Total Mentorships</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-handshake"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($mentorship_stats['active'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Active</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-play-circle"></i>
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
                                        <h3 class="mb-1"><?php echo number_format($mentorship_stats['completed'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Completed</p>
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
                                        <h3 class="mb-1"><?php echo number_format($mentorship_stats['cancelled'] ?? 0); ?></h3>
                                        <p class="mb-0 opacity-75">Cancelled</p>
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
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
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
                                <a href="/admin/mentorships.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Mentorships List -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-handshake me-2"></i>Mentorships List
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($mentorships)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No mentorships found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mentorship</th>
                                            <th>Participants</th>
                                            <th>Goals</th>
                                            <th>Status</th>
                                            <th>Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mentorships as $mentorship): ?>
                                            <tr class="mentorship-card">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <i class="fas fa-handshake text-white"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="mb-1">Mentorship #<?php echo $mentorship['id']; ?></h6>
                                                            <small class="text-muted">
                                                                Started <?php echo date('M j, Y', strtotime($mentorship['start_date'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <i class="fas fa-user-tie me-1"></i>
                                                            <?php echo htmlspecialchars($mentorship['mentor_first_name'] . ' ' . $mentorship['mentor_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($mentorship['mentor_department']); ?></small>
                                                    </div>
                                                    <hr class="my-1">
                                                    <div>
                                                        <div class="fw-semibold">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($mentorship['mentee_first_name'] . ' ' . $mentorship['mentee_last_name']); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($mentorship['mentee_department']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($mentorship['mentorship_goals'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars(substr($mentorship['mentorship_goals'] ?? 'No goals specified', 0, 50)) . '...'; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $mentorship['status'] === 'active' ? 'success' : ($mentorship['status'] === 'completed' ? 'info' : 'danger'); ?> status-badge">
                                                        <?php echo ucfirst($mentorship['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $start_date = new DateTime($mentorship['start_date']);
                                                            $end_date = $mentorship['end_date'] ? new DateTime($mentorship['end_date']) : new DateTime();
                                                            $duration = $start_date->diff($end_date);
                                                            echo $duration->days . ' days';
                                                            ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo ucfirst(str_replace('_', ' ', $mentorship['meeting_frequency'])); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewMentorship(<?php echo $mentorship['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($mentorship['status'] === 'active'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    onclick="completeMentorship(<?php echo $mentorship['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="cancelMentorship(<?php echo $mentorship['id']; ?>)">
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
                    <nav aria-label="Mentorships pagination" class="mt-4">
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
        function viewMentorship(mentorshipId) {
            // Implement mentorship view modal
            alert('View mentorship details for ID: ' + mentorshipId);
        }
        
        function completeMentorship(mentorshipId) {
            if (confirm('Are you sure you want to mark this mentorship as completed?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="mentorship_id" value="${mentorshipId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function cancelMentorship(mentorshipId) {
            if (confirm('Are you sure you want to cancel this mentorship?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="mentorship_id" value="${mentorshipId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>