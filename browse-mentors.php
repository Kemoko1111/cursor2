<?php
require_once 'config/app.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'mentee';

// Only mentees can browse mentors
if ($userRole !== 'mentee') {
    header('Location: /dashboard.php');
    exit();
}

// Initialize models
try {
    $userModel = new User();
} catch (Exception $e) {
    die('Database connection error. Please try again later.');
}

// Get current user data
try {
    $currentUser = $userModel->getUserById($userId);
    if (!$currentUser) {
        session_destroy();
        header('Location: /auth/login.php');
        exit();
    }
} catch (Exception $e) {
    die('Error loading user data. Please try again later.');
}

// Get filter parameters
$department = $_GET['department'] ?? '';
$yearOfStudy = $_GET['year_of_study'] ?? '';
$search = $_GET['search'] ?? '';

// Get mentors - Simple approach
$mentors = [];
try {
    // Simple query to get all mentors
    $query = "SELECT * FROM users WHERE role = 'mentor' AND status = 'active'";
    $params = [];
    
    // Add simple filters
    if (!empty($department)) {
        $query .= " AND department = ?";
        $params[] = $department;
    }
    
    if (!empty($yearOfStudy)) {
        $query .= " AND year_of_study = ?";
        $params[] = $yearOfStudy;
    }
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR bio LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY created_at DESC LIMIT 50";
    
    // Execute query directly
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $mentors = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log('Browse mentors error: ' . $e->getMessage());
    $mentors = [];
}

// Get departments for filter - Simple approach
$departments = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $stmt->execute();
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $departments[] = $row['department'];
    }
} catch (Exception $e) {
    error_log('Get departments error: ' . $e->getMessage());
    $departments = ['Computer Science', 'Engineering', 'Business', 'Mathematics', 'Other'];
}

$pageTitle = 'Browse Mentors - Menteego';
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
                    <li class="nav-item">
                        <a class="nav-link active" href="/browse-mentors.php">
                            <i class="fas fa-search me-1"></i>Find Mentors
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
                        <i class="fas fa-search me-2"></i>Find Mentors
                    </h1>
                    <p class="mb-0 opacity-75">Discover experienced mentors to guide your academic journey</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Search and Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="/browse-mentors.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, skills, or expertise..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                            <?php echo $department === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year_of_study" class="form-label">Year of Study</label>
                            <select class="form-select" id="year_of_study" name="year_of_study">
                                <option value="">All Years</option>
                                <option value="2nd" <?php echo $yearOfStudy === '2nd' ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd" <?php echo $yearOfStudy === '3rd' ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th" <?php echo $yearOfStudy === '4th' ? 'selected' : ''; ?>>4th Year</option>
                                <option value="graduate" <?php echo $yearOfStudy === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="row">
            <?php if (empty($mentors)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No mentors found</h5>
                        <p class="text-muted">Try adjusting your search criteria or check back later.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($mentors as $mentor): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm mentor-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo !empty($mentor['profile_image']) ? 'uploads/profiles/' . $mentor['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle me-3" width="60" height="60" alt=""
                                         onerror="this.src='assets/images/default-avatar.png'">
                                    <div>
                                        <h6 class="card-title mb-1 fw-bold">
                                            <?php echo htmlspecialchars(($mentor['first_name'] ?? '') . ' ' . ($mentor['last_name'] ?? '')); ?>
                                        </h6>
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($mentor['department'] ?? 'N/A'); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo ucfirst($mentor['year_of_study'] ?? 'N/A'); ?> Year
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($mentor['bio'])): ?>
                                    <p class="text-muted mb-3">
                                        <?php 
                                        $bioText = $mentor['bio'];
                                        echo htmlspecialchars(substr($bioText, 0, 100)); 
                                        if (strlen($bioText) > 100) echo '...';
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($mentor['skills'])): ?>
                                    <div class="mb-3">
                                        <?php 
                                        $skills = explode(',', $mentor['skills']);
                                        $displaySkills = array_slice($skills, 0, 3);
                                        foreach ($displaySkills as $skill): 
                                            $skillName = trim($skill);
                                            if (!empty($skillName)):
                                        ?>
                                            <span class="badge bg-light text-dark me-1">
                                                <?php echo htmlspecialchars($skillName); ?>
                                            </span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        if (count($skills) > 3): 
                                        ?>
                                            <span class="badge bg-secondary">+<?php echo count($skills) - 3; ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user text-primary"></i>
                                        Mentor
                                    </small>
                                    
                                    <div>
                                        <a href="/profile.php?id=<?php echo $mentor['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button class="btn btn-sm btn-primary request-mentor-btn" 
                                                data-mentor-id="<?php echo $mentor['id']; ?>">
                                            <i class="fas fa-paper-plane"></i> Request
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Mentorship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="requestForm">
                    <div class="modal-body">
                        <input type="hidden" id="mentorId" name="mentor_id">
                        <div class="mb-3">
                            <label for="message" class="form-label">Personal Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      placeholder="Tell the mentor why you'd like their guidance and what you hope to achieve..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="goals" class="form-label">Learning Goals</label>
                            <textarea class="form-control" id="goals" name="goals" rows="3" 
                                      placeholder="What specific skills or areas would you like to improve?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Handle mentor request
        document.querySelectorAll('.request-mentor-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const mentorId = this.dataset.mentorId;
                document.getElementById('mentorId').value = mentorId;
                new bootstrap.Modal(document.getElementById('requestModal')).show();
            });
        });

        // Handle form submission
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/mentorship-request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mentorship request sent successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    </script>
</body>
</html>