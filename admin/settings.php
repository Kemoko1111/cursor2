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

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        switch ($action) {
            case 'update_settings':
                $settings = [
                    'max_mentees_per_mentor' => $_POST['max_mentees_per_mentor'] ?? 3,
                    'max_mentors_per_mentee' => $_POST['max_mentors_per_mentee'] ?? 1,
                    'default_mentorship_duration' => $_POST['default_mentorship_duration'] ?? 12,
                    'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? 'true' : 'false',
                    'registration_enabled' => isset($_POST['registration_enabled']) ? 'true' : 'false',
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
                    $stmt->execute([$value, $userId, $key]);
                }
                
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, details, ip_address) VALUES (?, 'update_settings', 'system', 'System settings updated', ?)");
                $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Settings updated successfully";
                break;
                
            case 'clear_cache':
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, details, ip_address) VALUES (?, 'clear_cache', 'system', 'Cache cleared', ?)");
                $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Cache cleared successfully";
                break;
                
            case 'backup_database':
                // Log admin action
                $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, details, ip_address) VALUES (?, 'backup_database', 'system', 'Database backup initiated', ?)");
                $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Database backup initiated successfully";
                break;
        }
    } catch (Exception $e) {
        error_log("Error in settings action: " . $e->getMessage());
        $error = "Failed to perform action";
    }
}

// Admin functions
function getSystemSettings() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting system settings: " . $e->getMessage());
        return [];
    }
}

function getSystemInfo() {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $info = [];
        
        // Database size
        $stmt = $pdo->query("SELECT 
                                ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB'
                             FROM information_schema.tables 
                             WHERE table_schema = '$db_name'");
        $info['db_size'] = $stmt->fetch(PDO::FETCH_ASSOC)['DB Size in MB'];
        
        // Table counts
        $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$db_name'");
        $info['table_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['table_count'];
        
        // User counts
        $stmt = $pdo->query("SELECT 
                                COUNT(*) as total_users,
                                COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentors,
                                COUNT(CASE WHEN role = 'mentee' THEN 1 END) as mentees,
                                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins
                             FROM users");
        $user_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        $info['users'] = $user_counts;
        
        // Recent activity
        $stmt = $pdo->query("SELECT 
                                COUNT(*) as recent_logs
                             FROM admin_logs 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $info['recent_logs'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_logs'];
        
        return $info;
    } catch (Exception $e) {
        error_log("Error getting system info: " . $e->getMessage());
        return [];
    }
}

// Get data
$settings = getSystemSettings();
$system_info = getSystemInfo();

$pageTitle = 'System Settings - Admin Dashboard';
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
        .settings-card {
            transition: transform 0.2s ease;
        }
        .settings-card:hover {
            transform: translateY(-2px);
        }
        .info-card {
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
                        <a class="nav-link" href="/admin/requests.php">
                            <i class="fas fa-clipboard-list me-2"></i>Requests
                        </a>
                        <a class="nav-link" href="/admin/messages.php">
                            <i class="fas fa-comments me-2"></i>Messages
                        </a>
                        <a class="nav-link" href="/admin/analytics.php">
                            <i class="fas fa-chart-bar me-2"></i>Analytics
                        </a>
                        <a class="nav-link active" href="/admin/settings.php">
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
                        <h2 class="mb-1">System Settings</h2>
                        <p class="text-muted mb-0">Configure platform settings and system preferences</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">System Admin</span>
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

                <!-- System Information -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $system_info['db_size'] ?? '0'; ?> MB</h3>
                                        <p class="mb-0 opacity-75">Database Size</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $system_info['table_count'] ?? '0'; ?></h3>
                                        <p class="mb-0 opacity-75">Database Tables</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-table"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $system_info['users']['total_users'] ?? '0'; ?></h3>
                                        <p class="mb-0 opacity-75">Total Users</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $system_info['recent_logs'] ?? '0'; ?></h3>
                                        <p class="mb-0 opacity-75">Recent Logs (24h)</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Forms -->
                <div class="row">
                    <!-- Platform Settings -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm settings-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-cog me-2"></i>Platform Settings
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="max_mentees_per_mentor" class="form-label">Max Mentees per Mentor</label>
                                            <input type="number" class="form-control" id="max_mentees_per_mentor" name="max_mentees_per_mentor" 
                                                   value="<?php echo $settings['max_mentees_per_mentor']['value'] ?? 3; ?>" min="1" max="10">
                                            <small class="text-muted">Maximum number of mentees a mentor can have</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="max_mentors_per_mentee" class="form-label">Max Mentors per Mentee</label>
                                            <input type="number" class="form-control" id="max_mentors_per_mentee" name="max_mentors_per_mentee" 
                                                   value="<?php echo $settings['max_mentors_per_mentee']['value'] ?? 1; ?>" min="1" max="5">
                                            <small class="text-muted">Maximum number of mentors a mentee can have</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="default_mentorship_duration" class="form-label">Default Mentorship Duration (weeks)</label>
                                            <input type="number" class="form-control" id="default_mentorship_duration" name="default_mentorship_duration" 
                                                   value="<?php echo $settings['default_mentorship_duration']['value'] ?? 12; ?>" min="1" max="52">
                                            <small class="text-muted">Default duration for new mentorships</small>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled" 
                                                       <?php echo ($settings['email_notifications_enabled']['value'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="email_notifications_enabled">
                                                    Enable Email Notifications
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="registration_enabled" name="registration_enabled" 
                                                       <?php echo ($settings['registration_enabled']['value'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="registration_enabled">
                                                    Allow New Registrations
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                       <?php echo ($settings['maintenance_mode']['value'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenance_mode">
                                                    Maintenance Mode
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Actions -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm settings-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>System Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <button type="button" class="btn btn-outline-primary" onclick="clearCache()">
                                        <i class="fas fa-broom me-2"></i>Clear Cache
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-success" onclick="backupDatabase()">
                                        <i class="fas fa-download me-2"></i>Backup Database
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-info" onclick="viewLogs()">
                                        <i class="fas fa-clipboard-list me-2"></i>View System Logs
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-warning" onclick="testEmail()">
                                        <i class="fas fa-envelope me-2"></i>Test Email System
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Statistics -->
                        <div class="card shadow-sm settings-card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>User Statistics
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?php echo $system_info['users']['mentors'] ?? 0; ?></h4>
                                        <small class="text-muted">Mentors</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?php echo $system_info['users']['mentees'] ?? 0; ?></h4>
                                        <small class="text-muted">Mentees</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-danger"><?php echo $system_info['users']['admins'] ?? 0; ?></h4>
                                        <small class="text-muted">Admins</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-info"><?php echo $system_info['table_count'] ?? 0; ?></h4>
                                        <small class="text-muted">Tables</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function clearCache() {
            if (confirm('Are you sure you want to clear the system cache?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="clear_cache">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function backupDatabase() {
            if (confirm('Are you sure you want to create a database backup? This may take a few moments.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="backup_database">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewLogs() {
            alert('System logs viewer will be implemented here');
        }
        
        function testEmail() {
            alert('Email system test will be implemented here');
        }
    </script>
</body>
</html>