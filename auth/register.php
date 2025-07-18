<?php
require_once '../config/app.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';

// Get all skills for the form
$skillsQuery = "SELECT * FROM skills ORDER BY category, name";
$database = new Database();
$conn = $database->getConnection();
$skillsStmt = $conn->prepare($skillsQuery);
$skillsStmt->execute();
$allSkills = $skillsStmt->fetchAll();

// Group skills by category
$skillsByCategory = [];
foreach ($allSkills as $skill) {
    $skillsByCategory[$skill['category']][] = $skill;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'student_id' => sanitizeInput($_POST['student_id'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'year_of_study' => $_POST['year_of_study'] ?? '',
        'department' => sanitizeInput($_POST['department'] ?? ''),
        'role' => $_POST['role'] ?? 'mentee'
    ];
    
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (empty($userData['email']) || !validateEmail($userData['email'])) {
        $errors[] = 'Valid email address is required';
    }
    
    if (!isAcesEmail($userData['email'])) {
        $errors[] = 'Only ' . ORG_DOMAIN . ' email addresses are allowed';
    }
    
    if (empty($userData['password']) || strlen($userData['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($userData['password'] !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($userData['first_name']) || empty($userData['last_name'])) {
        $errors[] = 'First name and last name are required';
    }
    
    if (empty($userData['student_id'])) {
        $errors[] = 'Student ID is required';
    }
    
    if (empty($userData['year_of_study'])) {
        $errors[] = 'Year of study is required';
    }
    
    if (empty($userData['department'])) {
        $errors[] = 'Department is required';
    }
    
    if (!isset($_POST['terms']) || $_POST['terms'] !== '1') {
        $errors[] = 'You must agree to the terms and conditions';
    }
    
    if (empty($errors)) {
        $userModel = new User();
        $result = $userModel->register($userData);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = 'Sign Up - Menteego';
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
        <div class="auth-card" style="max-width: 600px; width: 100%;">
            <!-- Header -->
            <div class="auth-header">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Join Menteego
                </h2>
                <p class="mb-0 opacity-75">Create your account and start your mentorship journey</p>
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
                        <hr>
                        <div class="d-flex justify-content-center">
                            <a href="login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Go to Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>

                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </h5>
                            
                            <div class="mb-3">
                                <label for="first_name" class="form-label fw-semibold">First Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label fw-semibold">Last Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"
                                       placeholder="yourname@<?php echo ORG_DOMAIN; ?>"
                                       required>
                                <div class="form-text">Must be a valid <?php echo ORG_DOMAIN; ?> email</div>
                            </div>

                            <div class="mb-3">
                                <label for="student_id" class="form-label fw-semibold">Student/Staff ID</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="student_id" 
                                       name="student_id" 
                                       value="<?php echo htmlspecialchars($userData['student_id'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-graduation-cap me-2"></i>Academic Information
                            </h5>

                            <div class="mb-3">
                                <label for="year_of_study" class="form-label fw-semibold">Year of Study</label>
                                <select class="form-select" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select your year</option>
                                    <option value="1" <?php echo ($userData['year_of_study'] ?? '') === '1' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo ($userData['year_of_study'] ?? '') === '2' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo ($userData['year_of_study'] ?? '') === '3' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo ($userData['year_of_study'] ?? '') === '4' ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="graduate" <?php echo ($userData['year_of_study'] ?? '') === 'graduate' ? 'selected' : ''; ?>>Graduate Student</option>
                                    <option value="faculty" <?php echo ($userData['year_of_study'] ?? '') === 'faculty' ? 'selected' : ''; ?>>Faculty/Staff</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="department" class="form-label fw-semibold">Department/Major</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="department" 
                                       name="department" 
                                       value="<?php echo htmlspecialchars($userData['department'] ?? ''); ?>"
                                       placeholder="e.g., Computer Science, Mathematics"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label fw-semibold">I want to join as</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="mentee" <?php echo ($userData['role'] ?? 'mentee') === 'mentee' ? 'selected' : ''; ?>>
                                        Mentee (Looking for guidance)
                                    </option>
                                    <option value="mentor" <?php echo ($userData['role'] ?? '') === 'mentor' ? 'selected' : ''; ?>>
                                        Mentor (Want to help others)
                                    </option>
                                </select>
                                <div class="form-text">You can change this later in your profile</div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="position-relative">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           minlength="8"
                                           required>
                                    <button type="button" 
                                            class="btn btn-outline-secondary position-absolute end-0 top-0 h-100 px-3" 
                                            id="togglePassword"
                                            style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                <a href="#" class="text-decoration-none">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus me-2"></i>
                        Create Account
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        Already have an account? 
                        <a href="login.php" class="text-decoration-none fw-semibold">
                            Sign in here
                        </a>
                    </p>
                </div>

                <?php endif; ?>

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
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!email.includes('@<?php echo ORG_DOMAIN; ?>')) {
                e.preventDefault();
                alert('Please use your <?php echo ORG_DOMAIN; ?> email address');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
        });

        // Real-time password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>