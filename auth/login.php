<?php
require_once '../config/app.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $userModel = new User();
        $result = $userModel->login($email, $password);
        
        if ($result['success']) {
            redirect('/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login - Menteego';
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
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container d-flex align-items-center justify-content-center p-4">
        <div class="auth-card" style="max-width: 450px; width: 100%;">
            <!-- Header -->
            <div class="auth-header">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Welcome Back
                </h2>
                <p class="mb-0 opacity-75">Sign in to your Menteego account</p>
            </div>
            
            <!-- Body -->
            <div class="auth-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">
                            <i class="fas fa-envelope me-1"></i>Email Address
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="Enter your ACES email"
                               required>
                        <div class="form-text">
                            Use your <?php echo ORG_DOMAIN; ?> email address
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            <i class="fas fa-lock me-1"></i>Password
                        </label>
                        <div class="position-relative">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
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
                        <a href="forgot-password.php" class="text-decoration-none">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        Don't have an account? 
                        <a href="register.php" class="text-decoration-none fw-semibold">
                            Sign up here
                        </a>
                    </p>
                </div>

                <hr class="my-4">

                <div class="text-center">
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Home
                    </a>
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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (!email.includes('@<?php echo ORG_DOMAIN; ?>')) {
                e.preventDefault();
                alert('Please use your <?php echo ORG_DOMAIN; ?> email address');
                return;
            }
        });
    </script>
</body>
</html>