<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$messageModel = new Message();
$mentorshipModel = new Mentorship();

// Get current user data
$currentUser = $userModel->getUserById($userId);

// Get selected conversation
$selectedMentorshipId = $_GET['mentorship'] ?? null;
$selectedUserId = $_GET['user'] ?? null;

// Get all conversations for the user
$conversations = $messageModel->getConversations($userId);

// Get messages for selected conversation
$messages = [];
$otherUser = null;
$mentorship = null;

if ($selectedMentorshipId) {
    $mentorship = $mentorshipModel->getMentorshipById($selectedMentorshipId);
    if ($mentorship && ($mentorship['mentor_id'] == $userId || $mentorship['mentee_id'] == $userId)) {
        $messages = $messageModel->getMentorshipMessages($selectedMentorshipId);
        $otherUserId = $mentorship['mentor_id'] == $userId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
        $otherUser = $userModel->getUserById($otherUserId);
        // Mark messages as read
        $messageModel->markMentorshipMessagesAsRead($selectedMentorshipId, $userId);
    }
} elseif ($selectedUserId) {
    $otherUser = $userModel->getUserById($selectedUserId);
    if ($otherUser) {
        $messages = $messageModel->getDirectMessages($userId, $selectedUserId);
        // Mark messages as read
        $messageModel->markDirectMessagesAsRead($userId, $selectedUserId);
    }
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_content'])) {
    $messageContent = trim($_POST['message_content']);
    $mentorshipId = $_POST['mentorship_id'] ?? null;
    $receiverId = $_POST['receiver_id'] ?? null;
    
    if (!empty($messageContent)) {
        if ($mentorshipId) {
            $success = $messageModel->sendMentorshipMessage($userId, $mentorshipId, $messageContent);
        } elseif ($receiverId) {
            $success = $messageModel->sendDirectMessage($userId, $receiverId, $messageContent);
        }
        
        if ($success) {
            // Refresh messages
            if ($mentorshipId) {
                $messages = $messageModel->getMentorshipMessages($mentorshipId);
            } elseif ($receiverId) {
                $messages = $messageModel->getDirectMessages($userId, $receiverId);
            }
            // Refresh conversations
            $conversations = $messageModel->getConversations($userId);
        }
    }
}

$pageTitle = 'Messages - Menteego';
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
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .chat-container {
            height: 600px;
        }
        .chat-messages {
            height: 480px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem 0.375rem 0 0;
            background: #f8f9fa;
        }
        .message {
            margin-bottom: 1rem;
        }
        .message.own {
            text-align: right;
        }
        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            word-wrap: break-word;
        }
        .message.own .message-bubble {
            background: #007bff;
            color: white;
        }
        .message.other .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #dee2e6;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        .conversation-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #007bff;
        }
        .chat-input {
            border-radius: 0 0 0.375rem 0.375rem;
            border-top: none;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Menteego
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($userRole === 'mentee'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/browse-mentors.php">
                                <i class="fas fa-search me-1"></i>Find Mentors
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="/messages.php">
                            <i class="fas fa-comments me-1"></i>Messages
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $currentUser['profile_image'] ? 'uploads/profiles/' . $currentUser['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                 class="rounded-circle me-2" width="32" height="32" alt="">
                            <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="py-4 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="fw-bold mb-1">
                        <i class="fas fa-comments me-2"></i>Messages
                    </h1>
                    <p class="mb-0 opacity-75">Communicate with your mentors and mentees</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Conversations List -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Conversations
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No conversations yet</p>
                                <small class="text-muted">Start chatting with your mentors or mentees!</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <?php 
                                $isActive = ($selectedMentorshipId && $conversation['mentorship_id'] == $selectedMentorshipId) ||
                                           ($selectedUserId && $conversation['user_id'] == $selectedUserId);
                                ?>
                                <div class="conversation-item p-3 border-bottom <?php echo $isActive ? 'active' : ''; ?>"
                                     onclick="window.location.href='/messages.php?<?php echo $conversation['mentorship_id'] ? 'mentorship=' . $conversation['mentorship_id'] : 'user=' . $conversation['user_id']; ?>'">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $conversation['profile_image'] ? 'uploads/profiles/' . $conversation['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                             class="rounded-circle me-3" width="50" height="50" alt="">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="mb-1 fw-bold">
                                                    <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?>
                                                    <?php if ($conversation['unread_count'] > 0): ?>
                                                        <span class="badge bg-primary ms-1"><?php echo $conversation['unread_count']; ?></span>
                                                    <?php endif; ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo $conversation['last_message_time'] ? date('M j', strtotime($conversation['last_message_time'])) : ''; ?>
                                                </small>
                                            </div>
                                            <p class="mb-0 text-muted small">
                                                <?php echo $conversation['last_message'] ? htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : '') : 'No messages yet'; ?>
                                            </p>
                                            <?php if ($conversation['mentorship_id']): ?>
                                                <small class="text-primary">
                                                    <i class="fas fa-handshake me-1"></i>Mentorship
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <?php if ($otherUser): ?>
                        <!-- Chat Header -->
                        <div class="card-header d-flex align-items-center">
                            <img src="<?php echo $otherUser['profile_image'] ? 'uploads/profiles/' . $otherUser['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                 class="rounded-circle me-3" width="40" height="40" alt="">
                            <div>
                                <h6 class="mb-0 fw-bold">
                                    <?php echo htmlspecialchars($otherUser['first_name'] . ' ' . $otherUser['last_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($otherUser['department']); ?> • 
                                    <?php echo ucfirst($otherUser['year_of_study']); ?> Year
                                    <?php if ($mentorship): ?>
                                        <span class="badge bg-primary ms-2">Mentorship</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="ms-auto">
                                <a href="/profile.php?id=<?php echo $otherUser['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-user me-1"></i>View Profile
                                </a>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['sender_id'] == $userId ? 'own' : 'other'; ?>">
                                        <div class="message-bubble">
                                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Message Input -->
                        <div class="card-footer p-0">
                            <form method="POST" class="d-flex">
                                <?php if ($mentorship): ?>
                                    <input type="hidden" name="mentorship_id" value="<?php echo $mentorship['id']; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="receiver_id" value="<?php echo $otherUser['id']; ?>">
                                <?php endif; ?>
                                
                                <input type="text" class="form-control chat-input border-0" 
                                       name="message_content" placeholder="Type your message..." 
                                       required autocomplete="off">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- No Conversation Selected -->
                        <div class="card-body text-center py-5">
                            <i class="fas fa-comments fa-4x text-muted mb-4"></i>
                            <h5 class="text-muted">Select a conversation</h5>
                            <p class="text-muted">Choose a conversation from the list to start messaging</p>
                            
                            <?php if (empty($conversations)): ?>
                                <div class="mt-4">
                                    <?php if ($userRole === 'mentee'): ?>
                                        <a href="/browse-mentors.php" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Find Mentors
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Wait for mentees to send you requests, then you can start conversations.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });

        // Auto-refresh messages every 10 seconds
        setInterval(function() {
            const currentUrl = window.location.href;
            if (currentUrl.includes('mentorship=') || currentUrl.includes('user=')) {
                // Only refresh if we're in an active conversation
                fetch(currentUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Extract just the messages part and update
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMessages = doc.getElementById('chatMessages');
                    const currentMessages = document.getElementById('chatMessages');
                    
                    if (newMessages && currentMessages) {
                        const wasAtBottom = currentMessages.scrollTop >= (currentMessages.scrollHeight - currentMessages.clientHeight - 10);
                        currentMessages.innerHTML = newMessages.innerHTML;
                        
                        if (wasAtBottom) {
                            scrollToBottom();
                        }
                    }
                })
                .catch(error => console.log('Auto-refresh failed:', error));
            }
        }, 10000);

        // Handle form submission to auto-scroll
        document.querySelector('form')?.addEventListener('submit', function() {
            setTimeout(scrollToBottom, 100);
        });

        // Enter key to send message
        document.querySelector('input[name="message_content"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    </script>
</body>
</html>