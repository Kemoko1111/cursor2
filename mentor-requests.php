<?php
require_once 'config/app.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Only mentors can access this page
if ($userRole !== 'mentor') {
    redirect('/dashboard.php');
}

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

// Get pending requests for this mentor
function getMentorRequests($mentorId) {
    global $db_host, $db_name, $db_user, $db_pass;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "SELECT mr.*, 
                         mentee.first_name as mentee_first_name,
                         mentee.last_name as mentee_last_name,
                         mentee.email as mentee_email,
                         mentee.profile_image as mentee_image,
                         mentee.department as mentee_department,
                         mentee.year_of_study as mentee_year
                  FROM mentorship_requests mr
                  JOIN users mentee ON mr.mentee_id = mentee.id
                  WHERE mr.mentor_id = ? AND mr.status = 'pending'
                  ORDER BY mr.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$mentorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting mentor requests: " . $e->getMessage());
        return [];
    }
}

$currentUser = getCurrentUser($userId);
$requests = getMentorRequests($userId);

$pageTitle = 'Mentorship Requests - Menteego';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
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
                    <li class="nav-item">
                        <a class="nav-link active" href="/mentor-requests.php">
                            <i class="fas fa-paper-plane me-1"></i>Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/messages.php">
                            <i class="fas fa-comments me-1"></i>Messages
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($currentUser['first_name'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-3">
                    <i class="fas fa-paper-plane me-2"></i>Mentorship Requests
                </h1>
                <p class="text-muted">Review and respond to mentorship requests from mentees.</p>
            </div>
        </div>

        <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No pending requests</h4>
                <p class="text-muted">You have no new mentorship requests at this time.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($requests as $request): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 shadow-hover">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if ($request['mentee_image']): ?>
                                            <img src="<?php echo htmlspecialchars($request['mentee_image']); ?>" 
                                                 class="rounded-circle" width="60" height="60" alt="Profile">
                                        <?php else: ?>
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-user text-white fa-lg"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($request['mentee_first_name'] . ' ' . $request['mentee_last_name']); ?>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-graduation-cap me-1"></i>
                                            <?php echo htmlspecialchars($request['mentee_department']); ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-calendar me-1"></i>
                                            Year <?php echo htmlspecialchars($request['mentee_year']); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if ($request['message']): ?>
                                    <p class="card-text mb-3">
                                        <strong>Message:</strong><br>
                                        <?php echo htmlspecialchars($request['message']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <strong>Duration:</strong> <?php echo $request['duration_weeks']; ?> weeks
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-video me-1"></i>
                                            <strong>Meeting Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $request['preferred_meeting_type'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success btn-sm" onclick="respondRequest(<?php echo $request['id']; ?>, 'accepted')">
                                        <i class="fas fa-check me-1"></i>Accept
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="respondRequest(<?php echo $request['id']; ?>, 'rejected')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple alert function
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        async function respondRequest(requestId, action) {
            if (!confirm('Are you sure you want to ' + action + ' this request?')) return;
            
            try {
                console.log('Sending request:', { request_id: requestId, action: action });
                
                const response = await fetch('/api/mentor/respond-request.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ request_id: requestId, action: action })
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response from server: ' + responseText);
                }
                
                console.log('Parsed result:', result);
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    showAlert(result.message || 'Failed to process request', 'danger');
                }
            } catch (error) {
                console.error('Error details:', error);
                showAlert('Network error: ' + error.message, 'danger');
            }
        }
    </script>
</body>
</html>