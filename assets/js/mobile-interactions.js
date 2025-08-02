/**
 * Mobile Interactions & Touch Gestures
 * Enhances mobile user experience with touch interactions
 */

class MobileInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileMenu();
        this.setupTouchGestures();
        this.setupPullToRefresh();
        this.setupSwipeGestures();
        this.setupTouchFeedback();
        this.setupMobileOptimizations();
    }

    /**
     * Mobile Menu Toggle
     */
    setupMobileMenu() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (menuToggle && navbarCollapse) {
            menuToggle.addEventListener('click', () => {
                menuToggle.classList.toggle('active');
                navbarCollapse.classList.toggle('show');
                document.body.classList.toggle('menu-open');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!menuToggle.contains(e.target) && !navbarCollapse.contains(e.target)) {
                    menuToggle.classList.remove('active');
                    navbarCollapse.classList.remove('show');
                    document.body.classList.remove('menu-open');
                }
            });

            // Close menu when clicking on nav links
            const navLinks = navbarCollapse.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menuToggle.classList.remove('active');
                    navbarCollapse.classList.remove('show');
                    document.body.classList.remove('menu-open');
                });
            });
        }
    }

    /**
     * Touch Gestures
     */
    setupTouchGestures() {
        // Add touch feedback to interactive elements
        const touchElements = document.querySelectorAll('.btn, .nav-link, .feature-card, .stat-card');
        
        touchElements.forEach(element => {
            element.classList.add('touch-feedback');
            
            element.addEventListener('touchstart', (e) => {
                element.style.transform = 'scale(0.98)';
            });
            
            element.addEventListener('touchend', (e) => {
                setTimeout(() => {
                    element.style.transform = '';
                }, 150);
            });
        });
    }

    /**
     * Pull to Refresh
     */
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let pullDistance = 0;
        const threshold = 80;
        let isPulling = false;

        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            currentY = e.touches[0].clientY;
            pullDistance = currentY - startY;
            
            if (pullDistance > 0 && window.scrollY === 0) {
                e.preventDefault();
                this.showPullIndicator(pullDistance);
            }
        });

        document.addEventListener('touchend', (e) => {
            if (isPulling && pullDistance > threshold) {
                this.refreshPage();
            }
            this.hidePullIndicator();
            isPulling = false;
            pullDistance = 0;
        });
    }

    showPullIndicator(distance) {
        let indicator = document.querySelector('.pull-to-refresh');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'pull-to-refresh';
            indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Pull to refresh';
            document.body.insertBefore(indicator, document.body.firstChild);
        }
        
        indicator.style.transform = `translateY(${Math.min(distance, 60)}px)`;
        indicator.style.opacity = Math.min(distance / threshold, 1);
    }

    hidePullIndicator() {
        const indicator = document.querySelector('.pull-to-refresh');
        if (indicator) {
            indicator.style.transform = 'translateY(-60px)';
            indicator.style.opacity = '0';
        }
    }

    refreshPage() {
        window.location.reload();
    }

    /**
     * Swipe Gestures
     */
    setupSwipeGestures() {
        const swipeableElements = document.querySelectorAll('.swipeable');
        
        swipeableElements.forEach(element => {
            let startX = 0;
            let startY = 0;
            let currentX = 0;
            let currentY = 0;
            let isSwiping = false;

            element.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                isSwiping = true;
            });

            element.addEventListener('touchmove', (e) => {
                if (!isSwiping) return;
                
                currentX = e.touches[0].clientX;
                currentY = e.touches[0].clientY;
                
                const deltaX = currentX - startX;
                const deltaY = currentY - startY;
                
                // Only handle horizontal swipes
                if (Math.abs(deltaX) > Math.abs(deltaY)) {
                    e.preventDefault();
                    element.style.transform = `translateX(${deltaX * 0.3}px)`;
                }
            });

            element.addEventListener('touchend', (e) => {
                if (!isSwiping) return;
                
                const deltaX = currentX - startX;
                const threshold = 100;
                
                if (Math.abs(deltaX) > threshold) {
                    if (deltaX > 0) {
                        this.handleSwipeRight(element);
                    } else {
                        this.handleSwipeLeft(element);
                    }
                }
                
                element.style.transform = '';
                isSwiping = false;
            });
        });
    }

    handleSwipeLeft(element) {
        element.classList.add('swipe-left');
        setTimeout(() => {
            element.classList.remove('swipe-left');
        }, 300);
    }

    handleSwipeRight(element) {
        element.classList.add('swipe-right');
        setTimeout(() => {
            element.classList.remove('swipe-right');
        }, 300);
    }

    /**
     * Touch Feedback
     */
    setupTouchFeedback() {
        // Add ripple effect to buttons
        const buttons = document.querySelectorAll('.btn');
        
        buttons.forEach(button => {
            button.addEventListener('touchstart', (e) => {
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.touches[0].clientX - rect.left - size / 2;
                const y = e.touches[0].clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                button.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    /**
     * Mobile Optimizations
     */
    setupMobileOptimizations() {
        // Optimize scrolling performance
        let ticking = false;
        
        function updateScroll() {
            const scrolled = window.pageYOffset;
            const navbar = document.querySelector('.navbar');
            
            if (navbar) {
                if (scrolled > 50) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }
            }
            
            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateScroll);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestTick);

        // Lazy load images for better performance
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Prevent double-tap zoom on buttons
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }

    /**
     * Mobile-specific utilities
     */
    static isMobile() {
        return window.innerWidth <= 768;
    }

    static isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    static getOrientation() {
        return window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
    }
}

/**
 * Mobile-specific CSS animations
 */
const mobileStyles = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    @keyframes slideInFromBottom {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes fadeInScale {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .mobile-enter {
        animation: slideInFromBottom 0.3s ease-out;
    }

    .mobile-scale-enter {
        animation: fadeInScale 0.3s ease-out;
    }

    .navbar-scrolled {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    }

    .menu-open {
        overflow: hidden;
    }

    .menu-open .navbar-collapse {
        left: 0;
    }
`;

// Inject mobile styles
const styleSheet = document.createElement('style');
styleSheet.textContent = mobileStyles;
document.head.appendChild(styleSheet);

// Initialize mobile interactions
document.addEventListener('DOMContentLoaded', () => {
    new MobileInteractions();
});

// Export for use in other scripts
window.MobileInteractions = MobileInteractions;