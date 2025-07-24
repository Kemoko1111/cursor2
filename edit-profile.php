<?php
require_once 'config/app.php';
require_once __DIR__ . '/middleware/auth.php';

if (!isset($_SESSION['user_id'])) redirect('/auth/login.php');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();

// Get current user data
$currentUser = $userModel->getUserById($userId);
if (!$currentUser) {
    redirect('/auth/login.php');
}

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Validate required fields
    if (empty($updateData['first_name']) || empty($updateData['last_name']) || 
        empty($updateData['department']) || empty($updateData['year_of_study'])) {
        $error = "Please fill in all required fields.";
    } else {
        if ($userModel->updateProfile($userId, $updateData)) {
            $success = "Profile updated successfully!";
            $currentUser = $userModel->getUserById($userId); // Refresh data
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

$pageTitle = 'Edit Profile - Menteego';
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
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </h1>
                    <p class="mb-0 opacity-75">Update your profile information</p>
                </div>
                <div class="col-auto">
                    <a href="/profile.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Image Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-camera me-2"></i>Profile Picture
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo $currentUser['profile_image'] ? 'uploads/profiles/' . $currentUser['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                             class="rounded-circle mb-3" width="120" height="120" alt="Profile Picture" id="profilePreview">
                        
                        <div>
                            <button class="btn btn-outline-primary" onclick="document.getElementById('profileImageInput').click()">
                                <i class="fas fa-upload me-2"></i>Change Profile Picture
                            </button>
                            <input type="file" id="profileImageInput" style="display: none;" accept="image/*">
                        </div>
                        <small class="text-muted d-block mt-2">
                            Allowed formats: JPG, JPEG, PNG, GIF. Maximum size: 5MB
                        </small>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Basic Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bio -->
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" 
                                          placeholder="Tell others about yourself, your background, and what you're passionate about..."><?php echo htmlspecialchars($currentUser['bio']); ?></textarea>
                                <div class="form-text">Share your story, achievements, and what makes you unique.</div>
                            </div>
                            
                            <!-- Academic Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="department" name="department" 
                                               value="<?php echo htmlspecialchars($currentUser['department']); ?>" 
                                               placeholder="e.g., Computer Science" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="year_of_study" class="form-label">Year of Study <span class="text-danger">*</span></label>
                                        <select class="form-select" id="year_of_study" name="year_of_study" required>
                                            <option value="">Select Year</option>
                                            <option value="1st" <?php echo $currentUser['year_of_study'] === '1st' ? 'selected' : ''; ?>>1st Year</option>
                                            <option value="2nd" <?php echo $currentUser['year_of_study'] === '2nd' ? 'selected' : ''; ?>>2nd Year</option>
                                            <option value="3rd" <?php echo $currentUser['year_of_study'] === '3rd' ? 'selected' : ''; ?>>3rd Year</option>
                                            <option value="4th" <?php echo $currentUser['year_of_study'] === '4th' ? 'selected' : ''; ?>>4th Year</option>
                                            <option value="graduate" <?php echo $currentUser['year_of_study'] === 'graduate' ? 'selected' : ''; ?>>Graduate</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Skills & Interests -->
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills</label>
                                <input type="text" class="form-control" id="skills" name="skills" 
                                       value="<?php echo htmlspecialchars($currentUser['skills']); ?>"
                                       placeholder="e.g., Python, Web Development, Data Analysis, Project Management">
                                <div class="form-text">Enter your skills separated by commas.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="interests" class="form-label">Interests</label>
                                <input type="text" class="form-control" id="interests" name="interests" 
                                       value="<?php echo htmlspecialchars($currentUser['interests']); ?>"
                                       placeholder="e.g., Machine Learning, Mobile Apps, Game Development, Entrepreneurship">
                                <div class="form-text">Enter your interests separated by commas.</div>
                            </div>
                            
                            <!-- Social Links -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="linkedin_url" class="form-label">
                                            <i class="fab fa-linkedin me-1"></i>LinkedIn URL
                                        </label>
                                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                               value="<?php echo htmlspecialchars($currentUser['linkedin_url']); ?>"
                                               placeholder="https://linkedin.com/in/your-profile">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="github_url" class="form-label">
                                            <i class="fab fa-github me-1"></i>GitHub URL
                                        </label>
                                        <input type="url" class="form-control" id="github_url" name="github_url" 
                                               value="<?php echo htmlspecialchars($currentUser['github_url']); ?>"
                                               placeholder="https://github.com/your-username">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between">
                                <a href="/profile.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Handle profile image upload
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                // Upload image
                const formData = new FormData();
                formData.append('profile_image', file);
                
                fetch('/api/upload-profile-image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.querySelector('.container.my-5 .row .col-lg-8').insertBefore(
                            alertDiv, 
                            document.querySelector('.card')
                        );
                    } else {
                        alert('Error uploading image: ' + data.message);
                        // Reset preview to original
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while uploading the image.');
                    location.reload();
                });
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['first_name', 'last_name', 'department', 'year_of_study'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Remove validation styling on input
        document.querySelectorAll('input[required], select[required]').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>