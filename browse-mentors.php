<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Only mentees can browse mentors
if ($userRole !== 'mentee') {
    redirect('/dashboard.php');
}

// Initialize models
$userModel = new User();
// Remove: $mentorshipModel = new Mentorship();

// Get current user data
$currentUser = $userModel->getUserById($userId);

// Get filter parameters
$department = $_GET['department'] ?? '';
$yearOfStudy = $_GET['year_of_study'] ?? '';
$search = $_GET['search'] ?? '';

// Get available mentors
function getAllMentors($menteeId, $filters = []) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $params = [];
        $where = [
            "u.role = 'mentor'"
            // No status or email_verified filter
        ];

        if (!empty($filters['department'])) {
            $where[] = 'u.department = :department';
            $params[':department'] = $filters['department'];
        }
        if (!empty($filters['year_of_study'])) {
            $where[] = 'u.year_of_study = :year_of_study';
            $params[':year_of_study'] = $filters['year_of_study'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search OR u.bio LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $query = "SELECT u.*, 
                                 (SELECT AVG(r.rating) FROM reviews r WHERE r.mentor_id = u.id) as rating,
                                 (SELECT COUNT(*) FROM reviews r WHERE r.mentor_id = u.id) as review_count
                          FROM users u
                          WHERE " . implode(' AND ', $where) . "
                          ORDER BY u.created_at DESC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $mentors = $stmt->fetchAll();

        // For each mentor, check if the mentee already has an active mentorship with them
        foreach ($mentors as &$mentor) {
            $mentor['has_active_mentorship'] = false;
            $checkQuery = "SELECT COUNT(*) FROM mentorships WHERE mentor_id = :mentor_id AND mentee_id = :mentee_id AND status = 'active'";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindValue(':mentor_id', $mentor['id']);
            $checkStmt->bindValue(':mentee_id', $menteeId);
            $checkStmt->execute();
            $mentor['has_active_mentorship'] = $checkStmt->fetchColumn() > 0;
        }
        return $mentors;
    } catch (Exception $e) {
        error_log("Error getting all mentors: " . $e->getMessage());
        return [];
    }
}

$mentors = getAllMentors($userId, [
    'department' => $department,
    'year_of_study' => $yearOfStudy,
    'search' => $search
]);

// Get all departments for filter
$departments = $userModel->getAllDepartments();

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

        <!-- Mentors Grid -->
        <div class="row">
            <?php if (empty($mentors)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No mentors found</h4>
                        <p class="text-muted">Try adjusting your filters or check back later for new mentors.</p>
                        <a href="?department=&year_of_study=&search=" class="btn btn-outline-primary">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($mentors as $mentor): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 profile-card shadow-hover">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if ($mentor['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($mentor['profile_image']); ?>" 
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
                                            <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-graduation-cap me-1"></i>
                                            <?php echo htmlspecialchars($mentor['department']); ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-calendar me-1"></i>
                                            Year <?php echo htmlspecialchars($mentor['year_of_study']); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($mentor['bio']): ?>
                                    <p class="card-text text-truncate-2 mb-3">
                                        <?php echo htmlspecialchars($mentor['bio']); ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Current Mentees -->
                                <?php if ($mentor['active_mentees'] > 0): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-info">
                                            <i class="fas fa-users me-1"></i>
                                            Currently mentoring <?php echo $mentor['active_mentees']; ?> student<?php echo $mentor['active_mentees'] !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Skills -->
                                <?php
                                // Get mentor skills
                                $mentorSkills = [];
                                try {
                                    $database = new Database();
                                    $conn = $database->getConnection();
                                    $skillsQuery = "SELECT s.name, us.proficiency_level 
                                                   FROM user_skills us 
                                                   JOIN skills s ON us.skill_id = s.id 
                                                   WHERE us.user_id = :user_id AND us.is_teaching_skill = 1 
                                                   ORDER BY us.proficiency_level DESC 
                                                   LIMIT 5";
                                    $skillsStmt = $conn->prepare($skillsQuery);
                                    $skillsStmt->bindParam(':user_id', $mentor['id']);
                                    $skillsStmt->execute();
                                    $mentorSkills = $skillsStmt->fetchAll();
                                } catch (Exception $e) {
                                    // Silently handle error
                                }
                                ?>
                                
                                <?php if (!empty($mentorSkills)): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-tools me-1"></i>Skills:
                                        </small>
                                        <?php foreach ($mentorSkills as $skill): ?>
                                            <span class="skill-tag skill-<?php echo htmlspecialchars($skill['proficiency_level']); ?>">
                                                <?php echo htmlspecialchars($skill['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Rating -->
                                <?php if ($mentor['rating']): ?>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="text-warning me-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo ($i <= $mentor['rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo number_format($mentor['rating'], 1); ?> 
                                                (<?php echo $mentor['review_count']; ?> reviews)
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid">
                                    <?php if ($mentor['has_active_mentorship']): ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="fas fa-user-times me-1"></i>Already Your Mentor
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestModal" data-mentor-id="<?php echo $mentor['id']; ?>">
                                            <i class="fas fa-paper-plane me-1"></i>Request Mentorship
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php
        // --- Get all mentors in the system (regardless of current mentorship status) ---
        function getAllMentors($filters = []) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                $params = [];
                $where = [
                    "u.role = 'mentor'",
                    "u.status = 'active'",
                    "u.email_verified = 1"
                ];

                if (!empty($filters['department'])) {
                    $where[] = 'u.department = :department';
                    $params[':department'] = $filters['department'];
                }
                if (!empty($filters['year_of_study'])) {
                    $where[] = 'u.year_of_study = :year_of_study';
                    $params[':year_of_study'] = $filters['year_of_study'];
                }
                if (!empty($filters['search'])) {
                    $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search OR u.bio LIKE :search)';
                    $params[':search'] = '%' . $filters['search'] . '%';
                }

                $query = "SELECT u.*, 
                                 (SELECT AVG(r.rating) FROM reviews r WHERE r.mentor_id = u.id) as rating,
                                 (SELECT COUNT(*) FROM reviews r WHERE r.mentor_id = u.id) as review_count,
                                 (SELECT COUNT(*) FROM mentorships m WHERE m.mentor_id = u.id AND m.status = 'active') as active_mentees
                          FROM users u
                          WHERE " . implode(' AND ', $where) . "
                          ORDER BY u.created_at DESC";
                $stmt = $conn->prepare($query);
                foreach ($params as $key => $val) {
                    $stmt->bindValue($key, $val);
                }
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                error_log("Error getting all mentors: " . $e->getMessage());
                return [];
            }
        }

        $allMentors = getAllMentors([
            'department' => $department,
            'year_of_study' => $yearOfStudy,
            'search' => $search
        ]);
        ?>

        <?php if (!empty($allMentors)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="h4 mb-4">
                        <i class="fas fa-users me-2"></i>All Mentors in the System
                    </h3>
                    <p class="text-muted mb-4">Browse all available mentors in the system. Some may already be mentoring other students.</p>
                </div>
            </div>
            <div class="row">
                <?php foreach ($allMentors as $mentor): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 profile-card shadow-hover">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if ($mentor['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($mentor['profile_image']); ?>" 
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
                                            <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-graduation-cap me-1"></i>
                                            <?php echo htmlspecialchars($mentor['department']); ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-calendar me-1"></i>
                                            Year <?php echo htmlspecialchars($mentor['year_of_study']); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if ($mentor['bio']): ?>
                                    <p class="card-text text-truncate-2 mb-3">
                                        <?php echo htmlspecialchars($mentor['bio']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($mentor['active_mentees'] > 0): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-info">
                                            <i class="fas fa-users me-1"></i>
                                            Currently mentoring <?php echo $mentor['active_mentees']; ?> student<?php echo $mentor['active_mentees'] !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-grid">
                                    <button class="btn btn-primary request-mentor-btn" 
                                            data-mentor-id="<?php echo $mentor['id']; ?>">
                                        <i class="fas fa-paper-plane me-1"></i>Request Mentorship
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
                            <textarea class="form-control" id="message" name="message" rows="4" required
                                      placeholder="Tell the mentor why you'd like their guidance and what you hope to achieve..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="goals" class="form-label">Learning Goals</label>
                            <textarea class="form-control" id="goals" name="goals" rows="3" required
                                      placeholder="What specific skills or areas would you like to improve?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="meeting_type" class="form-label">Preferred Meeting Type</label>
                            <select class="form-select" id="meeting_type" name="meeting_type" required>
                                <option value="online">Online</option>
                                <option value="in_person">In Person</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="duration_weeks" class="form-label">Duration (weeks)</label>
                            <input type="number" class="form-control" id="duration_weeks" name="duration_weeks" 
                                   min="1" max="52" value="12" required>
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
                document.getElementById('requestForm').reset(); // Reset form fields
                const mentorId = this.dataset.mentorId;
                document.getElementById('mentorId').value = mentorId;
                new bootstrap.Modal(document.getElementById('requestModal')).show();
            });
        });

        // Handle form submission with client-side validation
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const message = document.getElementById('message').value.trim();
            const goals = document.getElementById('goals').value.trim();
            if (!message || !goals) {
                alert('Please fill in both the personal message and your learning goals.');
                return;
            }
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
                    alert('Error: ' + data.message); // Show the real backend error message
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