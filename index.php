<?php
require_once 'config/app.php';
require_once 'security-headers.php';

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
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Menteego">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icon-152x152.png">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="mainNav">
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
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#stats">Statistics</a>
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
    <section class="hero-section bg-gradient text-white py-5" id="hero">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4">
                            Connect. Learn. <span class="text-warning">Grow.</span>
                        </h1>
                        <p class="lead mb-4">
                            Join <?php echo ORG_NAME; ?>'s premier mentorship platform. 
                            Connect with experienced mentors or become a mentor yourself.
                        </p>
                        <div class="hero-stats mb-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="text-warning fw-bold">500+</h3>
                                        <small>Active Mentors</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="text-warning fw-bold">1200+</h3>
                                        <small>Happy Mentees</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <h3 class="text-warning fw-bold">95%</h3>
                                        <small>Success Rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="auth/register.php" class="btn btn-warning btn-lg px-4 shadow-hover">
                                <i class="fas fa-user-plus me-2"></i>Get Started
                            </a>
                            <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4 shadow-hover">
                                <i class="fas fa-play me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-illustration">
                        <i class="fas fa-users display-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Floating Elements -->
        <div class="floating-elements">
            <div class="floating-icon" style="top: 20%; left: 10%; animation-delay: 0s;">
                <i class="fas fa-lightbulb text-warning"></i>
            </div>
            <div class="floating-icon" style="top: 60%; right: 15%; animation-delay: 1s;">
                <i class="fas fa-heart text-danger"></i>
            </div>
            <div class="floating-icon" style="top: 30%; right: 25%; animation-delay: 2s;">
                <i class="fas fa-star text-warning"></i>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light" id="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card text-center">
                        <div class="stat-number" data-target="500">0</div>
                        <h5 class="text-muted">Active Mentors</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card text-center">
                        <div class="stat-number" data-target="1200">0</div>
                        <h5 class="text-muted">Happy Mentees</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card text-center">
                        <div class="stat-number" data-target="2500">0</div>
                        <h5 class="text-muted">Connections Made</h5>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-card text-center">
                        <div class="stat-number" data-target="95">0</div>
                        <h5 class="text-muted">Success Rate %</h5>
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="100">
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="200">
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="300">
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="400">
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="500">
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
                    <div class="feature-card h-100 p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="600">
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
                    <div class="step-card text-center" data-aos="fade-up" data-aos-delay="100">
                        <div class="step-number">1</div>
                        <h4>Sign Up</h4>
                        <p class="text-muted">
                            Create your account with your ACES email and complete your profile.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center" data-aos="fade-up" data-aos-delay="200">
                        <div class="step-number">2</div>
                        <h4>Set Preferences</h4>
                        <p class="text-muted">
                            Define your skills, goals, and availability to get matched effectively.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center" data-aos="fade-up" data-aos-delay="300">
                        <div class="step-number">3</div>
                        <h4>Connect</h4>
                        <p class="text-muted">
                            Browse mentors, send requests, and start building valuable relationships.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card text-center" data-aos="fade-up" data-aos-delay="400">
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

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">What Our Users Say</h2>
                    <p class="lead text-muted">
                        Hear from students and faculty who have experienced the power of mentorship.
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="testimonial-card p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="100">
                        <div class="testimonial-avatar mb-3">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                        </div>
                        <p class="text-muted mb-3">
                            "Menteego helped me find the perfect mentor for my research project. The platform is intuitive and the matching algorithm is spot-on!"
                        </p>
                        <h6 class="fw-bold">Sarah Johnson</h6>
                        <small class="text-muted">Graduate Student, Computer Science</small>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="testimonial-card p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="200">
                        <div class="testimonial-avatar mb-3">
                            <i class="fas fa-user-circle fa-3x text-success"></i>
                        </div>
                        <p class="text-muted mb-3">
                            "As a faculty member, I love how easy it is to connect with students who share my research interests. The communication tools are excellent."
                        </p>
                        <h6 class="fw-bold">Dr. Michael Chen</h6>
                        <small class="text-muted">Associate Professor, Engineering</small>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="testimonial-card p-4 text-center shadow-hover" data-aos="fade-up" data-aos-delay="300">
                        <div class="testimonial-avatar mb-3">
                            <i class="fas fa-user-circle fa-3x text-warning"></i>
                        </div>
                        <p class="text-muted mb-3">
                            "The progress tracking feature helped me stay accountable to my goals. I've grown so much through this mentorship program!"
                        </p>
                        <h6 class="fw-bold">Alex Rodriguez</h6>
                        <small class="text-muted">Undergraduate Student, Business</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Demo Section -->
    <section class="py-5 bg-light" id="demo">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">Try It Out</h2>
                    <p class="lead text-muted">
                        Experience how easy it is to find your perfect mentor match.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="demo-card p-4 shadow-hover border-radius-lg">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Quick Mentor Search</h5>
                                <div class="mb-3">
                                    <label class="form-label">I'm looking for help with:</label>
                                    <select class="form-select" id="demoField">
                                        <option value="">Select a field...</option>
                                        <option value="computer-science">Computer Science</option>
                                        <option value="engineering">Engineering</option>
                                        <option value="business">Business</option>
                                        <option value="research">Research Methods</option>
                                        <option value="career">Career Development</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">I'm a:</label>
                                    <select class="form-select" id="demoRole">
                                        <option value="">Select your role...</option>
                                        <option value="student">Student</option>
                                        <option value="faculty">Faculty</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary" id="demoSearchBtn">
                                    <i class="fas fa-search me-2"></i>Find Mentors
                                </button>
                            </div>
                            <div class="col-md-6">
                                <div id="demoResults" class="demo-results">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i>
                                        <p>Select your preferences to see matching mentors</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white" id="cta">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4">Ready to Start Your Journey?</h2>
                    <p class="lead mb-4">
                        Join hundreds of ACES students and faculty already using Menteego to accelerate their growth.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="auth/register.php" class="btn btn-warning btn-lg px-5 shadow-hover">
                            <i class="fas fa-rocket me-2"></i>Join Now
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-light btn-lg px-5 shadow-hover">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                    </div>
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
                    <div class="social-links">
                        <a href="#" class="text-muted me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo ORG_NAME; ?>. All rights reserved.
                    </p>
                    <div class="mt-2">
                        <a href="#" class="text-muted me-3">Privacy Policy</a>
                        <a href="#" class="text-muted me-3">Terms of Service</a>
                        <a href="#" class="text-muted">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Alert Container -->
    <div id="alert-container" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Enhanced Interactive Scripts -->
    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // Statistics counter animation
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 20);
        }

        // Intersection Observer for statistics
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const target = parseInt(stat.dataset.target);
                        animateCounter(stat, target);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const statsSection = document.getElementById('stats');
        if (statsSection) {
            observer.observe(statsSection);
        }

        // Demo search functionality
        document.getElementById('demoSearchBtn')?.addEventListener('click', function() {
            const field = document.getElementById('demoField').value;
            const role = document.getElementById('demoRole').value;
            
            if (!field || !role) {
                MenteegoApp.showAlert('Please select both field and role', 'warning');
                return;
            }

            // Simulate search results
            const results = [
                { name: 'Dr. Sarah Wilson', field: 'Computer Science', rating: 4.8, students: 12 },
                { name: 'Prof. James Brown', field: 'Engineering', rating: 4.9, students: 8 },
                { name: 'Dr. Maria Garcia', field: 'Research Methods', rating: 4.7, students: 15 }
            ];

            displayDemoResults(results);
        });

        function displayDemoResults(results) {
            const resultsContainer = document.getElementById('demoResults');
            const resultsHtml = results.map(result => `
                <div class="demo-result-item p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${result.name}</h6>
                            <small class="text-muted">${result.field} • ${result.students} mentees</small>
                        </div>
                        <div class="text-end">
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <span>${result.rating}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            resultsContainer.innerHTML = resultsHtml;
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add loading animation to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.classList.contains('btn-loading')) {
                    this.classList.add('btn-loading');
                    setTimeout(() => {
                        this.classList.remove('btn-loading');
                    }, 2000);
                }
            });
        });
    </script>

    <!-- Additional CSS for enhanced interactivity -->
    <style>
        .navbar-scrolled {
            background: rgba(0, 102, 204, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.6;
            animation: float 6s ease-in-out infinite;
        }

        .hero-stats {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .stat-item {
            padding: 0.5rem;
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .demo-card {
            background: white;
            border-radius: 15px;
        }

        .demo-results {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .demo-result-item {
            transition: background-color 0.3s ease;
        }

        .demo-result-item:hover {
            background-color: #f8f9fa;
        }

        .btn-loading {
            position: relative;
            overflow: hidden;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .social-links a {
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--primary-color) !important;
        }
    </style>
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>
