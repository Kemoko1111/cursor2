<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$mentorshipModel = new Mentorship();

// Get current user data
$currentUser = $userModel->getUserById($userId);

// Get requests based on user role
if ($userRole === 'mentor') {
    // Mentors see requests TO them
    $pendingRequests = $mentorshipModel->getMentorRequests($userId, 'pending');
    $allRequests = $mentorshipModel->getMentorRequests($userId);
} else {
    // Mentees see requests FROM them
    $pendingRequests = $mentorshipModel->getMenteeRequests($userId, 'pending');
    $allRequests = $mentorshipModel->getMenteeRequests($userId);
}

// Handle request actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    if ($userRole === 'mentor' && in_array($action, ['accept', 'reject'])) {
        if ($mentorshipModel->updateRequestStatus($requestId, $action, $userId)) {
            $success = "Request " . ($action === 'accept' ? 'accepted' : 'rejected') . " successfully!";
            // Refresh data
            $pendingRequests = $mentorshipModel->getMentorRequests($userId, 'pending');
            $allRequests = $mentorshipModel->getMentorRequests($userId);
        } else {
            $error = "Failed to update request. Please try again.";
        }
    }
}

$pageTitle = 'Requests - Menteego';
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
                        <a class="nav-link" href="/messages.php">
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
                        <i class="fas fa-paper-plane me-2"></i>
                        <?php echo $userRole === 'mentor' ? 'Mentorship Requests' : 'My Requests'; ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <?php echo $userRole === 'mentor' 
                            ? 'Manage incoming mentorship requests' 
                            : 'Track your mentorship requests'; ?>
                    </p>
                </div>
                <div class="col-auto">
                    <span class="badge bg-warning fs-6">
                        <?php echo count($pendingRequests); ?> Pending
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Requests -->
        <?php if (!empty($pendingRequests)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        Pending Requests (<?php echo count($pendingRequests); ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($pendingRequests as $request): ?>
                        <div class="border-bottom p-4">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <img src="<?php echo $request['profile_image'] ? 'uploads/profiles/' . $request['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle" width="60" height="60" alt="">
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                    </h6>
                                    <p class="text-muted mb-2">
                                        <?php echo htmlspecialchars($request['department']); ?> • 
                                        <?php echo ucfirst($request['year_of_study']); ?> Year
                                    </p>
                                    
                                    <?php if (!empty($request['message'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted fw-bold">Message:</small>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['message'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['goals'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted fw-bold">Goals:</small>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['goals'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Requested: <?php echo date('M j, Y \a\t g:i A', strtotime($request['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-4 text-end">
                                    <?php if ($userRole === 'mentor'): ?>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="action" value="accept">
                                                <button type="submit" class="btn btn-success btn-sm" 
                                                        onclick="return confirm('Accept this mentorship request?')">
                                                    <i class="fas fa-check me-1"></i>Accept
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Reject this mentorship request?')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <a href="/profile.php?id=<?php echo $request[$userRole === 'mentor' ? 'mentee_id' : 'mentor_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Profile
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                        <div class="mt-2">
                                            <a href="/profile.php?id=<?php echo $request['mentor_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Mentor
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- All Requests History -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    All Requests
                </h5>
                <span class="text-muted"><?php echo count($allRequests); ?> total</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($allRequests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No requests yet</h6>
                        <p class="text-muted mb-0">
                            <?php if ($userRole === 'mentee'): ?>
                                Start by browsing and requesting mentors.
                            <?php else: ?>
                                Wait for mentorship requests from mentees.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allRequests as $request): ?>
                        <div class="border-bottom p-4 <?php echo $request['status'] === 'pending' ? 'bg-light' : ''; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <img src="<?php echo $request['profile_image'] ? 'uploads/profiles/' . $request['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle" width="50" height="50" alt="">
                                </div>
                                
                                <div class="col-md-7">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 me-2">
                                            <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                        </h6>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] === 'pending' ? 'warning' : 
                                                ($request['status'] === 'accepted' ? 'success' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted mb-1">
                                        <?php echo htmlspecialchars($request['department']); ?> • 
                                        <?php echo ucfirst($request['year_of_study']); ?> Year
                                    </p>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                        
                                        <?php if ($request['status'] !== 'pending'): ?>
                                            • Updated: <?php echo date('M j, Y', strtotime($request['updated_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <a href="/profile.php?id=<?php echo $request[$userRole === 'mentor' ? 'mentee_id' : 'mentor_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Profile
                                    </a>
                                    
                                    <?php if ($request['status'] === 'accepted'): ?>
                                        <div class="mt-2">
                                            <a href="/messages.php?mentorship=<?php echo $request['mentorship_id'] ?? ''; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-comments me-1"></i>Message
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Collapsible message content -->
                            <?php if (!empty($request['message']) || !empty($request['goals'])): ?>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#request-<?php echo $request['id']; ?>" 
                                            aria-expanded="false">
                                        <i class="fas fa-chevron-down me-1"></i>View Details
                                    </button>
                                    
                                    <div class="collapse mt-2" id="request-<?php echo $request['id']; ?>">
                                        <div class="card card-body">
                                            <?php if (!empty($request['message'])): ?>
                                                <div class="mb-2">
                                                    <strong>Message:</strong>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['message'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($request['goals'])): ?>
                                                <div>
                                                    <strong>Goals:</strong>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['goals'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>