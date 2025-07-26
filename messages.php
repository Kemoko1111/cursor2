<?php
require_once 'config/app.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Database configuration
$db_host = 'sql103.infinityfree.com';
$db_name = 'if0_39537447_menteego_db';
$db_user = 'if0_39537447';
$db_pass = 'AeFe44u4EAs';

// Get current user data
function getCurrentUser($userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

// Get conversations for user
function getConversations($userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get mentorship conversations
        $query = "SELECT DISTINCT m.id as mentorship_id, m.mentor_id, m.mentee_id, m.status,
                         u.id as user_id, u.first_name, u.last_name, u.profile_image, u.department,
                         (SELECT COUNT(*) FROM messages WHERE mentorship_id = m.id AND receiver_id = ? AND is_read = 0) as unread_count,
                         (SELECT content FROM messages WHERE mentorship_id = m.id ORDER BY created_at DESC LIMIT 1) as last_message,
                         (SELECT created_at FROM messages WHERE mentorship_id = m.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                  FROM mentorships m
                  JOIN users u ON (m.mentor_id = ? AND u.id = m.mentee_id) OR (m.mentee_id = ? AND u.id = m.mentor_id)
                  WHERE (m.mentor_id = ? OR m.mentee_id = ?) AND m.status = 'active'
                  ORDER BY last_message_time DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting conversations: " . $e->getMessage());
        return [];
    }
}

// Get messages for mentorship
function getMentorshipMessages($mentorshipId, $userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT m.*, u.first_name, u.last_name, u.profile_image
                  FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.mentorship_id = ?
                  ORDER BY m.created_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mentorshipId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting mentorship messages: " . $e->getMessage());
        return [];
    }
}

// Send message
function sendMessage($senderId, $mentorshipId, $content, $messageType = 'text') {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get mentorship to determine receiver
        $mentorshipStmt = $pdo->prepare("SELECT mentor_id, mentee_id FROM mentorships WHERE id = ?");
        $mentorshipStmt->execute([$mentorshipId]);
        $mentorship = $mentorshipStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mentorship) {
            return false;
        }
        
        $receiverId = $mentorship['mentor_id'] == $senderId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
        
        $query = "INSERT INTO messages (sender_id, receiver_id, mentorship_id, content, message_type, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([$senderId, $receiverId, $mentorshipId, $content, $messageType]);
    } catch (Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        return false;
    }
}

// Mark messages as read
function markMessagesAsRead($mentorshipId, $userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "UPDATE messages SET is_read = 1 WHERE mentorship_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([$mentorshipId, $userId]);
    } catch (Exception $e) {
        error_log("Error marking messages as read: " . $e->getMessage());
        return false;
    }
}

// Get mentorship by ID
function getMentorshipById($mentorshipId, $userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT * FROM mentorships WHERE id = ? AND (mentor_id = ? OR mentee_id = ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mentorshipId, $userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting mentorship: " . $e->getMessage());
        return null;
    }
}

// Get user by ID
function getUserById($userId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user: " . $e->getMessage());
        return null;
    }
}

// Handle sending new message
$messageSent = false;
$messageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_content'])) {
    $messageContent = trim($_POST['message_content']);
    $mentorshipId = $_POST['mentorship_id'] ?? null;
    $messageType = $_POST['message_type'] ?? 'text';
    
    if (!empty($messageContent) && $mentorshipId) {
        $messageSent = sendMessage($userId, $mentorshipId, $messageContent, $messageType);
        if (!$messageSent) {
            $messageError = 'Failed to send message. Please try again.';
        }
    }
}

// Get current user data
$currentUser = getCurrentUser($userId);

// Get selected conversation
$selectedMentorshipId = $_GET['mentorship'] ?? null;

// Get all conversations for the user
$conversations = getConversations($userId);

// Get messages for selected conversation
$messages = [];
$otherUser = null;
$mentorship = null;

if ($selectedMentorshipId) {
    $mentorship = getMentorshipById($selectedMentorshipId, $userId);
    if ($mentorship) {
        $messages = getMentorshipMessages($selectedMentorshipId, $userId);
        $otherUserId = $mentorship['mentor_id'] == $userId ? $mentorship['mentee_id'] : $mentorship['mentor_id'];
        $otherUser = getUserById($otherUserId);
        // Mark messages as read
        markMessagesAsRead($selectedMentorshipId, $userId);
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
        .resource-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .resource-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: border-color 0.3s;
        }
        .file-upload-area:hover {
            border-color: #007bff;
        }
        .file-upload-area.dragover {
            border-color: #007bff;
            background: #e3f2fd;
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
        <?php if ($messageError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($messageError); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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
                                $isActive = ($selectedMentorshipId && $conversation['mentorship_id'] == $selectedMentorshipId);
                                ?>
                                <div class="conversation-item p-3 border-bottom <?php echo $isActive ? 'active' : ''; ?>"
                                     onclick="window.location.href='/messages.php?mentorship=<?php echo $conversation['mentorship_id']; ?>'">
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
                                            <small class="text-primary">
                                                <i class="fas fa-handshake me-1"></i>Mentorship
                                            </small>
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
                                    <span class="badge bg-primary ms-2">Mentorship</span>
                                </small>
                            </div>
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="toggleResources()">
                                    <i class="fas fa-share-alt me-1"></i>Share Resources
                                </button>
                                <a href="/profile.php?id=<?php echo $otherUser['id']; ?>" class="btn btn-sm btn-outline-secondary">
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
                                            <?php if ($message['message_type'] === 'resource'): ?>
                                                <div class="resource-item">
                                                    <i class="fas fa-file-alt resource-icon"></i>
                                                    <strong><?php echo htmlspecialchars($message['content']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Shared resource</small>
                                                </div>
                                            <?php else: ?>
                                                <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Resource Sharing Modal -->
                        <div class="modal fade" id="resourceModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Share Resources</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="file-upload-area" id="fileUploadArea">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <h6>Drag and drop files here</h6>
                                            <p class="text-muted">or click to browse</p>
                                            <input type="file" id="fileInput" multiple style="display: none;">
                                        </div>
                                        <div id="selectedFiles" class="mt-3"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" onclick="shareResources()">Share</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="card-footer p-0">
                            <form method="POST" class="d-flex">
                                <input type="hidden" name="mentorship_id" value="<?php echo $mentorship['id']; ?>">
                                <input type="hidden" name="message_type" value="text" id="messageType">
                                
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

        // Resource sharing functionality
        let selectedFiles = [];

        function toggleResources() {
            const modal = new bootstrap.Modal(document.getElementById('resourceModal'));
            modal.show();
        }

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');

        if (fileUploadArea && fileInput) {
            fileUploadArea.addEventListener('click', () => fileInput.click());

            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                const files = Array.from(e.dataTransfer.files);
                handleFiles(files);
            });

            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                handleFiles(files);
            });
        }

        function handleFiles(files) {
            selectedFiles = files;
            displaySelectedFiles();
        }

        function displaySelectedFiles() {
            const container = document.getElementById('selectedFiles');
            container.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'd-flex align-items-center justify-content-between p-2 bg-light rounded mb-2';
                fileDiv.innerHTML = `
                    <div>
                        <i class="fas fa-file me-2"></i>
                        <span>${file.name}</span>
                        <small class="text-muted">(${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(fileDiv);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displaySelectedFiles();
        }

        async function shareResources() {
            if (selectedFiles.length === 0) {
                alert('Please select files to share');
                return;
            }

            const formData = new FormData();
            formData.append('mentorship_id', '<?php echo $mentorship['id'] ?? ''; ?>');
            formData.append('message_type', 'resource');

            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });

            try {
                const response = await fetch('/api/messages/send-resource.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Close modal and refresh page
                    bootstrap.Modal.getInstance(document.getElementById('resourceModal')).hide();
                    window.location.reload();
                } else {
                    alert('Failed to share resources: ' + result.message);
                }
            } catch (error) {
                console.error('Error sharing resources:', error);
                alert('Failed to share resources. Please try again.');
            }
        }
    </script>
</body>
</html>