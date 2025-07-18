<?php
require_once 'config/app.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$pageTitle = 'Welcome to Menteego';
$pageDescription = 'Connect with mentors and mentees in the ACES community';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-graduation-cap me-2"></i>Menteego
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2 px-3" href="auth/register.php">Sign Up</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-gradient text-white py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Connect. Learn. Grow.
                    </h1>
                    <p class="lead mb-4">
                        Join <?php echo ORG_NAME; ?>'s premier mentorship platform. 
                        Connect with experienced mentors or become a mentor yourself.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="auth/register.php" class="btn btn-warning btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-play me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-illustration">
                        <i class="fas fa-users display-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">Why Choose Menteego?</h2>
                    <p class="lead text-muted">
                        Our platform makes mentorship accessible, organized, and effective for everyone in the ACES community.
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-search-plus fa-3x text-primary"></i>
                        </div>
                        <h4>Intelligent Matching</h4>
                        <p class="text-muted">
                            Our smart algorithm matches mentees with mentors based on skills, interests, and availability.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-comments fa-3x text-success"></i>
                        </div>
                        <h4>Seamless Communication</h4>
                        <p class="text-muted">
                            Built-in messaging system with notifications to keep your mentorship conversations flowing.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-chart-line fa-3x text-warning"></i>
                        </div>
                        <h4>Progress Tracking</h4>
                        <p class="text-muted">
                            Monitor your mentorship journey with built-in progress tracking and goal setting tools.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-shield-alt fa-3x text-info"></i>
                        </div>
                        <h4>Secure & Private</h4>
                        <p class="text-muted">
                            Your data is protected with enterprise-grade security and privacy controls.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-calendar-alt fa-3x text-danger"></i>
                        </div>
                        <h4>Flexible Scheduling</h4>
                        <p class="text-muted">
                            Set your availability and find mentors who match your schedule preferences.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card h-100 p-4 text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-graduation-cap fa-3x text-purple"></i>
                        </div>
                        <h4>ACES Community</h4>
                        <p class="text-muted">
                            Connect exclusively with verified members of the ACES academic community.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">How It Works</h2>
                    <p class="lead text-muted">
                        Getting started with mentorship is simple and straightforward.
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center">
                        <div class="step-number">1</div>
                        <h4>Sign Up</h4>
                        <p class="text-muted">
                            Create your account with your ACES email and complete your profile.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center">
                        <div class="step-number">2</div>
                        <h4>Set Preferences</h4>
                        <p class="text-muted">
                            Define your skills, goals, and availability to get matched effectively.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center">
                        <div class="step-number">3</div>
                        <h4>Connect</h4>
                        <p class="text-muted">
                            Browse mentors, send requests, and start building valuable relationships.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center">
                        <div class="step-number">4</div>
                        <h4>Grow</h4>
                        <p class="text-muted">
                            Engage in meaningful conversations and achieve your learning goals.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4">Ready to Start Your Journey?</h2>
                    <p class="lead mb-4">
                        Join hundreds of ACES students and faculty already using Menteego to accelerate their growth.
                    </p>
                    <a href="auth/register.php" class="btn btn-warning btn-lg px-5">
                        <i class="fas fa-rocket me-2"></i>Join Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>Menteego</h5>
                    <p class="text-muted">Empowering the ACES community through mentorship.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo ORG_NAME; ?>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
