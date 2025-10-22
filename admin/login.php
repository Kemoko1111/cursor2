<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header('Location: /admin/dashboard.php');
    exit();
}

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if user exists and is admin (using your existing schema)
            $query = "SELECT * FROM users WHERE email = ? AND role = 'admin' AND status = 'active'";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password (using your existing password_hash field)
                if (password_verify($password, $user['password_hash'])) {
                    // Create admin session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['last_activity'] = time();
                    
                    // Log admin login (using your admin_logs table)
                    $logQuery = "INSERT INTO admin_logs (admin_id, action, target_type, details, ip_address) 
                                VALUES (?, 'login', 'system', 'Admin login successful', ?)";
                    $logStmt = $pdo->prepare($logQuery);
                    $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                    
                    // Redirect to admin dashboard
                    header('Location: /admin/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

$pageTitle = 'Admin Login - Menteego';
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .admin-login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        .admin-login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .admin-login-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .admin-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <!-- Header -->
            <div class="admin-login-header">
                <div class="admin-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 class="fw-bold mb-2">Admin Access</h2>
                <p class="mb-0 opacity-75">Menteego Platform Administration</p>
            </div>
            
            <!-- Body -->
            <div class="admin-login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="adminLoginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">
                            <i class="fas fa-envelope me-1"></i>Admin Email
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="Enter admin email address"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            <i class="fas fa-lock me-1"></i>Admin Password
                        </label>
                        <div class="position-relative">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter admin password"
                                   required>
                            <button type="button" 
                                    class="btn btn-outline-secondary position-absolute end-0 top-0 h-100 px-3" 
                                    id="togglePassword"
                                    style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-decoration-none" onclick="alert('Contact system administrator')">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Access Admin Panel
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        <a href="/auth/login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to User Login
                        </a>
                    </p>
                </div>

                <hr class="my-4">

                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure Admin Access
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
        });
    </script>
</body>
</html>