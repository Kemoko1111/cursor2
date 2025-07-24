<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$mentorshipModel = new Mentorship();

// Check if viewing another user's profile
$profileId = $_GET['id'] ?? $userId;
$isOwnProfile = ($profileId == $userId);

// Get profile user data
$profileUser = $userModel->getUserById($profileId);
if (!$profileUser) {
    redirect('/dashboard.php');
}

// Get current user data for navigation
$currentUser = $userModel->getUserById($userId);

// Get user's mentorship history and stats
$mentorshipStats = $userModel->getUserStats($profileId);
$mentorshipHistory = $mentorshipModel->getUserMentorships($profileId);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    $updateData = [
        'first_name' => sanitizeInput($_POST['first_name']),
        'last_name' => sanitizeInput($_POST['last_name']),
        'bio' => sanitizeInput($_POST['bio']),
        'department' => sanitizeInput($_POST['department']),
        'year_of_study' => sanitizeInput($_POST['year_of_study']),
        'skills' => sanitizeInput($_POST['skills']),
        'interests' => sanitizeInput($_POST['interests']),
        'linkedin_url' => sanitizeInput($_POST['linkedin_url']),
        'github_url' => sanitizeInput($_POST['github_url'])
    ];
    
    if ($userModel->updateProfile($profileId, $updateData)) {
        $success = "Profile updated successfully!";
        $profileUser = $userModel->getUserById($profileId); // Refresh data
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

$pageTitle = $isOwnProfile ? 'My Profile - Menteego' : $profileUser['first_name'] . ' ' . $profileUser['last_name'] . ' - Menteego';
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
                        <i class="fas fa-user me-2"></i>
                        <?php echo $isOwnProfile ? 'My Profile' : 'Profile'; ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <?php echo $isOwnProfile ? 'Manage your profile information' : 'View user profile'; ?>
                    </p>
                </div>
                <?php if ($isOwnProfile): ?>
                    <div class="col-auto">
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#editModal">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                    </div>
                <?php endif; ?>
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

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="<?php echo $profileUser['profile_image'] ? 'uploads/profiles/' . $profileUser['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                     class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                                
                                <?php if ($isOwnProfile): ?>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profileImageInput').click()">
                                            <i class="fas fa-camera me-1"></i>Change Photo
                                        </button>
                                        <input type="file" id="profileImageInput" style="display: none;" accept="image/*">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-8">
                                <h3 class="fw-bold mb-2">
                                    <?php echo htmlspecialchars($profileUser['first_name'] . ' ' . $profileUser['last_name']); ?>
                                    <span class="badge bg-<?php echo $profileUser['role'] === 'mentor' ? 'success' : 'primary'; ?> ms-2">
                                        <?php echo ucfirst($profileUser['role']); ?>
                                    </span>
                                </h3>
                                
                                <p class="text-muted mb-2">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    <?php echo htmlspecialchars($profileUser['department']); ?> • 
                                    <?php echo ucfirst($profileUser['year_of_study']); ?> Year
                                </p>
                                
                                <p class="text-muted mb-3">
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo htmlspecialchars($profileUser['email']); ?>
                                </p>

                                <?php if (!empty($profileUser['bio'])): ?>
                                    <div class="mb-3">
                                        <h6 class="fw-bold">About</h6>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Social Links -->
                                <div class="d-flex gap-2">
                                    <?php if (!empty($profileUser['phone'])): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo htmlspecialchars($profileUser['phone']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Interests -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lightbulb me-2"></i>Skills & Interests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($profileUser['skills'])): ?>
                            <div class="mb-3">
                                <h6 class="fw-bold">Skills</h6>
                                <?php 
                                $skills = explode(',', $profileUser['skills']);
                                foreach ($skills as $skill): 
                                ?>
                                    <span class="badge bg-primary me-1 mb-1">
                                        <?php echo htmlspecialchars(trim($skill)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($profileUser['interests'])): ?>
                            <div>
                                <h6 class="fw-bold">Interests</h6>
                                <?php 
                                $interests = explode(',', $profileUser['interests']);
                                foreach ($interests as $interest): 
                                ?>
                                    <span class="badge bg-secondary me-1 mb-1">
                                        <?php echo htmlspecialchars(trim($interest)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($profileUser['skills']) && empty($profileUser['interests'])): ?>
                            <p class="text-muted text-center py-3 mb-0">
                                <?php echo $isOwnProfile ? 'Add your skills and interests to your profile.' : 'No skills or interests listed.'; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistics & Activity -->
            <div class="col-lg-4">
                <!-- Stats Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary fw-bold mb-0">
                                        <?php echo $profileUser['role'] === 'mentor' ? $mentorshipStats['active_mentees'] : $mentorshipStats['active_mentors']; ?>
                                    </h4>
                                    <small class="text-muted">
                                        Active <?php echo $profileUser['role'] === 'mentor' ? 'Mentees' : 'Mentors'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success fw-bold mb-0">
                                    <?php echo $mentorshipStats['completed_mentorships'] ?? 0; ?>
                                </h4>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        
                        <?php if ($profileUser['role'] === 'mentor' && isset($mentorshipStats['rating'])): ?>
                            <hr>
                            <div class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    <span class="fw-bold me-1"><?php echo number_format($mentorshipStats['rating'], 1); ?></span>
                                    <small class="text-muted">
                                        (<?php echo $mentorshipStats['review_count'] ?? 0; ?> reviews)
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions Card -->
                <?php if (!$isOwnProfile): ?>
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($profileUser['role'] === 'mentor' && $userRole === 'mentee'): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
                                        <i class="fas fa-paper-plane me-2"></i>Request Mentorship
                                    </button>
                                <?php endif; ?>
                                
                                <a href="/messages.php?user=<?php echo $profileUser['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-comments me-2"></i>Send Message
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <?php if ($isOwnProfile): ?>
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($profileUser['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($profileUser['last_name']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3" 
                                          placeholder="Tell others about yourself..."><?php echo htmlspecialchars($profileUser['bio']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" 
                                               value="<?php echo htmlspecialchars($profileUser['department']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="year_of_study" class="form-label">Year of Study</label>
                                        <select class="form-select" id="year_of_study" name="year_of_study" required>
                                            <option value="1st" <?php echo $profileUser['year_of_study'] === '1st' ? 'selected' : ''; ?>>1st Year</option>
                                            <option value="2nd" <?php echo $profileUser['year_of_study'] === '2nd' ? 'selected' : ''; ?>>2nd Year</option>
                                            <option value="3rd" <?php echo $profileUser['year_of_study'] === '3rd' ? 'selected' : ''; ?>>3rd Year</option>
                                            <option value="4th" <?php echo $profileUser['year_of_study'] === '4th' ? 'selected' : ''; ?>>4th Year</option>
                                            <option value="graduate" <?php echo $profileUser['year_of_study'] === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills</label>
                                <input type="text" class="form-control" id="skills" name="skills" 
                                       value="<?php echo htmlspecialchars($profileUser['skills']); ?>"
                                       placeholder="e.g., Python, Web Development, Data Analysis (comma separated)">
                            </div>
                            
                            <div class="mb-3">
                                <label for="interests" class="form-label">Interests</label>
                                <input type="text" class="form-control" id="interests" name="interests" 
                                       value="<?php echo htmlspecialchars($profileUser['interests']); ?>"
                                       placeholder="e.g., Machine Learning, Mobile Apps, Game Development (comma separated)">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                               value="<?php echo htmlspecialchars($profileUser['linkedin_url']); ?>"
                                               placeholder="https://linkedin.com/in/username">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="github_url" class="form-label">GitHub URL</label>
                                        <input type="url" class="form-control" id="github_url" name="github_url" 
                                               value="<?php echo htmlspecialchars($profileUser['github_url']); ?>"
                                               placeholder="https://github.com/username">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mentorship Request Modal -->
    <?php if (!$isOwnProfile && $profileUser['role'] === 'mentor' && $userRole === 'mentee'): ?>
        <div class="modal fade" id="requestModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Request Mentorship</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="requestForm">
                        <div class="modal-body">
                            <input type="hidden" name="mentor_id" value="<?php echo $profileUser['id']; ?>">
                            <div class="mb-3">
                                <label for="message" class="form-label">Personal Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" 
                                          placeholder="Tell the mentor why you'd like their guidance..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="goals" class="form-label">Learning Goals</label>
                                <textarea class="form-control" id="goals" name="goals" rows="3" 
                                          placeholder="What specific skills would you like to improve?"></textarea>
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
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Handle profile image upload
        document.getElementById('profileImageInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('profile_image', file);
                
                fetch('/api/upload-profile-image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error uploading image: ' + data.message);
                    }
                });
            }
        });

        // Handle mentorship request
        document.getElementById('requestForm')?.addEventListener('submit', function(e) {
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
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>