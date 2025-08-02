<?php
/**
 * Menteego Assets Management
 * 
 * This file manages all CSS, JavaScript, and other assets
 * for the Menteego mentorship platform.
 */

// Prevent direct access
if (!defined('MENTEEGO_APP')) {
    die('Direct access not allowed');
}

// Asset version for cache busting
$assetVersion = '1.0.0';

// Determine if we're in development or production
$isDevelopment = defined('APP_DEBUG') && APP_DEBUG;

// Asset base URL
$assetBaseUrl = '/assets/';

/**
 * Output CSS assets
 */
function outputCssAssets() {
    global $assetVersion, $isDevelopment, $assetBaseUrl;
    
    // External CSS libraries
    $externalCss = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        'https://unpkg.com/aos@2.3.1/dist/aos.css'
    ];
    
    // Internal CSS files
    $internalCss = [
        'css/style.css'
    ];
    
    // Output external CSS
    foreach ($externalCss as $css) {
        echo '<link href="' . $css . '" rel="stylesheet">' . "\n";
    }
    
    // Output internal CSS with version
    foreach ($internalCss as $css) {
        $version = $isDevelopment ? '?v=' . time() : '?v=' . $assetVersion;
        echo '<link href="' . $assetBaseUrl . $css . $version . '" rel="stylesheet">' . "\n";
    }
}

/**
 * Output JavaScript assets
 */
function outputJsAssets() {
    global $assetVersion, $isDevelopment, $assetBaseUrl;
    
    // External JS libraries
    $externalJs = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        'https://unpkg.com/aos@2.3.1/dist/aos.js'
    ];
    
    // Internal JS files
    $internalJs = [
        'js/main.js'
    ];
    
    // Output external JS
    foreach ($externalJs as $js) {
        echo '<script src="' . $js . '"></script>' . "\n";
    }
    
    // Output internal JS with version
    foreach ($internalJs as $js) {
        $version = $isDevelopment ? '?v=' . time() : '?v=' . $assetVersion;
        echo '<script src="' . $assetBaseUrl . $js . $version . '"></script>' . "\n";
    }
}

/**
 * Output meta tags
 */
function outputMetaTags($title = '', $description = '', $keywords = '') {
    $defaultTitle = 'Menteego - ACES Mentorship Platform';
    $defaultDescription = 'Connect with mentors and mentees in the ACES community';
    $defaultKeywords = 'mentorship, ACES, students, faculty, learning, growth, education';
    
    $title = $title ?: $defaultTitle;
    $description = $description ?: $defaultDescription;
    $keywords = $keywords ?: $defaultKeywords;
    
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    
    // Open Graph tags
    echo '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:url" content="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . '">' . "\n";
    
    // Twitter Card tags
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    
    // Favicon
    echo '<link rel="icon" type="image/x-icon" href="' . $assetBaseUrl . 'images/favicon.ico">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . $assetBaseUrl . 'images/apple-touch-icon.png">' . "\n";
}

/**
 * Output navigation bar
 */
function outputNavigation($currentPage = '') {
    $navItems = [
        'home' => ['url' => '/', 'text' => 'Home', 'icon' => 'fas fa-home'],
        'features' => ['url' => '#features', 'text' => 'Features', 'icon' => 'fas fa-star'],
        'how-it-works' => ['url' => '#how-it-works', 'text' => 'How It Works', 'icon' => 'fas fa-info-circle'],
        'testimonials' => ['url' => '#testimonials', 'text' => 'Testimonials', 'icon' => 'fas fa-comments'],
        'login' => ['url' => '/auth/login.php', 'text' => 'Login', 'icon' => 'fas fa-sign-in-alt'],
        'register' => ['url' => '/auth/register.php', 'text' => 'Sign Up', 'icon' => 'fas fa-user-plus']
    ];
    
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="mainNav">' . "\n";
    echo '    <div class="container">' . "\n";
    echo '        <a class="navbar-brand fw-bold" href="/">' . "\n";
    echo '            <i class="fas fa-graduation-cap me-2"></i>Menteego' . "\n";
    echo '        </a>' . "\n";
    echo '        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">' . "\n";
    echo '            <span class="navbar-toggler-icon"></span>' . "\n";
    echo '        </button>' . "\n";
    echo '        <div class="collapse navbar-collapse" id="navbarNav">' . "\n";
    echo '            <ul class="navbar-nav ms-auto">' . "\n";
    
    foreach ($navItems as $key => $item) {
        if ($key === 'register') {
            echo '                <li class="nav-item">' . "\n";
            echo '                    <a class="nav-link btn btn-outline-light ms-2 px-3" href="' . $item['url'] . '">' . "\n";
            echo '                        <i class="' . $item['icon'] . ' me-2"></i>' . $item['text'] . "\n";
            echo '                    </a>' . "\n";
            echo '                </li>' . "\n";
        } else {
            $activeClass = ($currentPage === $key) ? ' active' : '';
            echo '                <li class="nav-item">' . "\n";
            echo '                    <a class="nav-link' . $activeClass . '" href="' . $item['url'] . '">' . "\n";
            echo '                        <i class="' . $item['icon'] . ' me-2"></i>' . $item['text'] . "\n";
            echo '                    </a>' . "\n";
            echo '                </li>' . "\n";
        }
    }
    
    echo '            </ul>' . "\n";
    echo '        </div>' . "\n";
    echo '    </div>' . "\n";
    echo '</nav>' . "\n";
}

/**
 * Output footer
 */
function outputFooter() {
    $currentYear = date('Y');
    $orgName = defined('ORG_NAME') ? ORG_NAME : 'ACES';
    
    echo '<footer class="bg-dark text-light py-4">' . "\n";
    echo '    <div class="container">' . "\n";
    echo '        <div class="row">' . "\n";
    echo '            <div class="col-md-6">' . "\n";
    echo '                <h5><i class="fas fa-graduation-cap me-2"></i>Menteego</h5>' . "\n";
    echo '                <p class="text-muted">Empowering the ACES community through mentorship.</p>' . "\n";
    echo '                <div class="social-links">' . "\n";
    echo '                    <a href="#" class="text-muted me-3"><i class="fab fa-facebook"></i></a>' . "\n";
    echo '                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>' . "\n";
    echo '                    <a href="#" class="text-muted me-3"><i class="fab fa-linkedin"></i></a>' . "\n";
    echo '                    <a href="#" class="text-muted"><i class="fab fa-instagram"></i></a>' . "\n";
    echo '                </div>' . "\n";
    echo '            </div>' . "\n";
    echo '            <div class="col-md-6 text-md-end">' . "\n";
    echo '                <p class="text-muted mb-0">' . "\n";
    echo '                    &copy; ' . $currentYear . ' ' . $orgName . '. All rights reserved.' . "\n";
    echo '                </p>' . "\n";
    echo '                <div class="mt-2">' . "\n";
    echo '                    <a href="#" class="text-muted me-3">Privacy Policy</a>' . "\n";
    echo '                    <a href="#" class="text-muted me-3">Terms of Service</a>' . "\n";
    echo '                    <a href="#" class="text-muted">Contact Us</a>' . "\n";
    echo '                </div>' . "\n";
    echo '            </div>' . "\n";
    echo '        </div>' . "\n";
    echo '    </div>' . "\n";
    echo '</footer>' . "\n";
}

/**
 * Output alert container
 */
function outputAlertContainer() {
    echo '<div id="alert-container" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050;"></div>' . "\n";
}

/**
 * Output enhanced interactive scripts
 */
function outputEnhancedScripts() {
    echo '<script>' . "\n";
    echo '    // Initialize AOS animations' . "\n";
    echo '    AOS.init({' . "\n";
    echo '        duration: 800,' . "\n";
    echo '        easing: "ease-in-out",' . "\n";
    echo '        once: true' . "\n";
    echo '    });' . "\n\n";
    
    echo '    // Navbar scroll effect' . "\n";
    echo '    window.addEventListener("scroll", function() {' . "\n";
    echo '        const navbar = document.getElementById("mainNav");' . "\n";
    echo '        if (window.scrollY > 50) {' . "\n";
    echo '            navbar.classList.add("navbar-scrolled");' . "\n";
    echo '        } else {' . "\n";
    echo '            navbar.classList.remove("navbar-scrolled");' . "\n";
    echo '        }' . "\n";
    echo '    });' . "\n\n";
    
    echo '    // Statistics counter animation' . "\n";
    echo '    function animateCounter(element, target) {' . "\n";
    echo '        let current = 0;' . "\n";
    echo '        const increment = target / 100;' . "\n";
    echo '        const timer = setInterval(() => {' . "\n";
    echo '            current += increment;' . "\n";
    echo '            if (current >= target) {' . "\n";
    echo '                current = target;' . "\n";
    echo '                clearInterval(timer);' . "\n";
    echo '            }' . "\n";
    echo '            element.textContent = Math.floor(current);' . "\n";
    echo '        }, 20);' . "\n";
    echo '    }' . "\n\n";
    
    echo '    // Intersection Observer for statistics' . "\n";
    echo '    const observerOptions = {' . "\n";
    echo '        threshold: 0.5,' . "\n";
    echo '        rootMargin: "0px 0px -100px 0px"' . "\n";
    echo '    };' . "\n\n";
    
    echo '    const observer = new IntersectionObserver((entries) => {' . "\n";
    echo '        entries.forEach(entry => {' . "\n";
    echo '            if (entry.isIntersecting) {' . "\n";
    echo '                const statNumbers = entry.target.querySelectorAll(".stat-number");' . "\n";
    echo '                statNumbers.forEach(stat => {' . "\n";
    echo '                    const target = parseInt(stat.dataset.target);' . "\n";
    echo '                    animateCounter(stat, target);' . "\n";
    echo '                });' . "\n";
    echo '                observer.unobserve(entry.target);' . "\n";
    echo '            }' . "\n";
    echo '        });' . "\n";
    echo '    }, observerOptions);' . "\n\n";
    
    echo '    const statsSection = document.getElementById("stats");' . "\n";
    echo '    if (statsSection) {' . "\n";
    echo '        observer.observe(statsSection);' . "\n";
    echo '    }' . "\n\n";
    
    echo '    // Smooth scrolling for navigation links' . "\n";
    echo '    document.querySelectorAll("a[href^=\\"#\\"]").forEach(anchor => {' . "\n";
    echo '        anchor.addEventListener("click", function (e) {' . "\n";
    echo '            e.preventDefault();' . "\n";
    echo '            const target = document.querySelector(this.getAttribute("href"));' . "\n";
    echo '            if (target) {' . "\n";
    echo '                const offsetTop = target.offsetTop - 80;' . "\n";
    echo '                window.scrollTo({' . "\n";
    echo '                    top: offsetTop,' . "\n";
    echo '                    behavior: "smooth"' . "\n";
    echo '                });' . "\n";
    echo '            }' . "\n";
    echo '        });' . "\n";
    echo '    });' . "\n\n";
    
    echo '    // Add loading animation to buttons' . "\n";
    echo '    document.querySelectorAll(".btn").forEach(btn => {' . "\n";
    echo '        btn.addEventListener("click", function() {' . "\n";
    echo '            if (!this.classList.contains("btn-loading")) {' . "\n";
    echo '                this.classList.add("btn-loading");' . "\n";
    echo '                setTimeout(() => {' . "\n";
    echo '                    this.classList.remove("btn-loading");' . "\n";
    echo '                }, 2000);' . "\n";
    echo '            }' . "\n";
    echo '        });' . "\n";
    echo '    });' . "\n";
    echo '</script>' . "\n";
}

/**
 * Output additional CSS for enhanced interactivity
 */
function outputEnhancedCss() {
    echo '<style>' . "\n";
    echo '    .navbar-scrolled {' . "\n";
    echo '        background: rgba(0, 102, 204, 0.95) !important;' . "\n";
    echo '        backdrop-filter: blur(10px);' . "\n";
    echo '        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .floating-elements {' . "\n";
    echo '        position: absolute;' . "\n";
    echo '        width: 100%;' . "\n";
    echo '        height: 100%;' . "\n";
    echo '        top: 0;' . "\n";
    echo '        left: 0;' . "\n";
    echo '        pointer-events: none;' . "\n";
    echo '        overflow: hidden;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .floating-icon {' . "\n";
    echo '        position: absolute;' . "\n";
    echo '        font-size: 1.5rem;' . "\n";
    echo '        opacity: 0.6;' . "\n";
    echo '        animation: float 6s ease-in-out infinite;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .animate-on-scroll {' . "\n";
    echo '        opacity: 0;' . "\n";
    echo '        transform: translateY(30px);' . "\n";
    echo '        transition: all 0.6s ease;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .animate-on-scroll.animated {' . "\n";
    echo '        opacity: 1;' . "\n";
    echo '        transform: translateY(0);' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .testimonial-card {' . "\n";
    echo '        background: white;' . "\n";
    echo '        border-radius: 15px;' . "\n";
    echo '        transition: all 0.3s ease;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .demo-card {' . "\n";
    echo '        background: white;' . "\n";
    echo '        border-radius: 15px;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .demo-results {' . "\n";
    echo '        min-height: 200px;' . "\n";
    echo '        display: flex;' . "\n";
    echo '        align-items: center;' . "\n";
    echo '        justify-content: center;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .demo-result-item {' . "\n";
    echo '        transition: background-color 0.3s ease;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .demo-result-item:hover {' . "\n";
    echo '        background-color: #f8f9fa;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .btn-loading {' . "\n";
    echo '        position: relative;' . "\n";
    echo '        overflow: hidden;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .btn-loading::after {' . "\n";
    echo '        content: "";' . "\n";
    echo '        position: absolute;' . "\n";
    echo '        top: 0;' . "\n";
    echo '        left: -100%;' . "\n";
    echo '        width: 100%;' . "\n";
    echo '        height: 100%;' . "\n";
    echo '        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);' . "\n";
    echo '        animation: loading 1.5s infinite;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    @keyframes loading {' . "\n";
    echo '        0% { left: -100%; }' . "\n";
    echo '        100% { left: 100%; }' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .social-links a {' . "\n";
    echo '        transition: color 0.3s ease;' . "\n";
    echo '    }' . "\n\n";
    
    echo '    .social-links a:hover {' . "\n";
    echo '        color: var(--primary-color) !important;' . "\n";
    echo '    }' . "\n";
    echo '</style>' . "\n";
}

/**
 * Get asset URL with version
 */
function getAssetUrl($path) {
    global $assetBaseUrl, $assetVersion, $isDevelopment;
    $version = $isDevelopment ? '?v=' . time() : '?v=' . $assetVersion;
    return $assetBaseUrl . $path . $version;
}

/**
 * Output complete head section
 */
function outputHead($title = '', $description = '', $keywords = '') {
    outputMetaTags($title, $description, $keywords);
    outputCssAssets();
}

/**
 * Output complete scripts section
 */
function outputScripts() {
    outputJsAssets();
    outputEnhancedScripts();
    outputEnhancedCss();
}
?>