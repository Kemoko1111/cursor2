// Menteego Main JavaScript

// Utility Functions
const MenteegoApp = {
    // Initialize the application
    init() {
        this.setupGlobalEventListeners();
        this.initializeTooltips();
        this.setupNotificationHandlers();
        this.initializeModals();
    },

    // Setup global event listeners
    setupGlobalEventListeners() {
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Form submission handlers
        this.setupFormHandlers();
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                if (alert.classList.contains('show') || !alert.classList.contains('fade')) {
                    this.fadeOut(alert);
                }
            });
        }, 5000);
    },

    // Initialize Bootstrap tooltips
    initializeTooltips() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },

    // Setup notification handlers
    setupNotificationHandlers() {
        // Mark notification as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                if (this.classList.contains('notification-unread')) {
                    this.classList.remove('notification-unread');
                    // Send AJAX request to mark as read
                    const notificationId = this.dataset.notificationId;
                    if (notificationId) {
                        MenteegoApp.markNotificationRead(notificationId);
                    }
                }
            });
        });
    },

    // Initialize modals
    initializeModals() {
        // Auto-focus first input in modals
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const firstInput = this.querySelector('input:not([type="hidden"]), textarea, select');
                if (firstInput) {
                    firstInput.focus();
                }
            });
        });
    },

    // Setup form handlers
    setupFormHandlers() {
        // Add loading state to form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    MenteegoApp.setButtonLoading(submitBtn, true);
                }
            });
        });

        // Real-time form validation
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !MenteegoApp.isValidEmail(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    },

    // Utility Functions
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.dataset.originalText = originalText;
            button.innerHTML = '<span class="loading-spinner me-2"></span>Loading...';
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    },

    fadeOut(element) {
        element.style.opacity = '1';
        const fade = () => {
            if ((element.style.opacity -= .1) < 0) {
                element.style.display = 'none';
            } else {
                requestAnimationFrame(fade);
            }
        };
        fade();
    },

    showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const alertContainer = document.getElementById('alert-container') || document.body;
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
    },

    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    // AJAX Functions
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    },

    async markNotificationRead(notificationId) {
        try {
            await this.makeRequest('/api/notifications/mark-read.php', {
                method: 'POST',
                body: JSON.stringify({ notification_id: notificationId })
            });
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    },

    // Search functionality
    initializeSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        if (searchInput && searchResults) {
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        MenteegoApp.performSearch(query);
                    }, 300);
                } else {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                }
            });
        }
    },

    async performSearch(query) {
        try {
            const data = await this.makeRequest(`/api/search.php?q=${encodeURIComponent(query)}`);
            this.displaySearchResults(data.results || []);
        } catch (error) {
            console.error('Search failed:', error);
        }
    },

    displaySearchResults(results) {
        const searchResults = document.getElementById('searchResults');
        
        if (results.length === 0) {
            searchResults.innerHTML = '<div class="p-3 text-muted">No results found</div>';
        } else {
            const resultsHtml = results.map(result => `
                <div class="search-result-item p-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="${result.profile_image || '/assets/images/default-avatar.png'}" 
                             class="rounded-circle me-3" width="40" height="40" alt="">
                        <div>
                            <h6 class="mb-1">${result.name}</h6>
                            <small class="text-muted">${result.department} • ${result.year_of_study}</small>
                        </div>
                    </div>
                </div>
            `).join('');
            
            searchResults.innerHTML = resultsHtml;
        }
        
        searchResults.style.display = 'block';
    },

    // Message functionality
    initializeMessaging() {
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const messagesContainer = document.getElementById('messagesContainer');
        
        if (messageForm) {
            messageForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message) return;
                
                const mentorshipId = this.dataset.mentorshipId;
                
                try {
                    const response = await MenteegoApp.makeRequest('/api/messages/send.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            mentorship_id: mentorshipId,
                            message: message
                        })
                    });
                    
                    if (response.success) {
                        messageInput.value = '';
                        MenteegoApp.loadMessages(mentorshipId);
                    } else {
                        MenteegoApp.showAlert(response.message, 'danger');
                    }
                } catch (error) {
                    MenteegoApp.showAlert('Failed to send message', 'danger');
                }
            });
        }
        
        // Auto-scroll to bottom of messages
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    },

    async loadMessages(mentorshipId) {
        try {
            const data = await this.makeRequest(`/api/messages/get.php?mentorship_id=${mentorshipId}`);
            if (data.success) {
                this.displayMessages(data.messages);
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
        }
    },

    displayMessages(messages) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        const messagesHtml = messages.map(message => `
            <div class="message-bubble ${message.sender_id == currentUserId ? 'message-sent' : 'message-received'}">
                <div class="message-content">${this.escapeHtml(message.message)}</div>
                <div class="message-time">${this.formatDateTime(message.created_at)}</div>
            </div>
        `).join('');
        
        messagesContainer.innerHTML = messagesHtml;
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    },

    // Utility functions for display
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    },

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }
};

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    MenteegoApp.init();
    
    // Initialize specific features based on page
    if (document.getElementById('searchInput')) {
        MenteegoApp.initializeSearch();
    }
    
    if (document.getElementById('messageForm')) {
        MenteegoApp.initializeMessaging();
    }
});

// Export for global use
window.MenteegoApp = MenteegoApp;