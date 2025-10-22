<?php
require_once 'config/app.php';
require_once 'models/User.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userModel = new User();
$viewUserId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$viewUser = $userModel->getUserById($viewUserId);

if (!$viewUser) {
    http_response_code(404);
    echo "<div class='container my-5'><div class='alert alert-danger'>User not found.</div></div>";
    exit;
}

$isOwnProfile = ($viewUserId == $_SESSION['user_id']);
$error = '';
$success = '';

$years = [
    '1' => '1st Year',
    '2' => '2nd Year',
    '3' => '3rd Year',
    '4' => '4th Year',
    'graduate' => 'Graduate Student',
    'faculty' => 'Faculty/Staff'
];

// Handle profile update (only for own profile)
if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profileData = [
        'first_name'     => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name'      => sanitizeInput($_POST['last_name'] ?? ''),
        'phone'          => sanitizeInput($_POST['phone'] ?? ''),
        'bio'            => sanitizeInput($_POST['bio'] ?? ''),
        'department'     => sanitizeInput($_POST['department'] ?? ''),
        'year_of_study'  => $_POST['year_of_study'] ?? '',
    ];

    // Validate
    $errors = [];
    if (empty($profileData['first_name']) || empty($profileData['last_name'])) {
        $errors[] = 'First and last name are required.';
    }
    if (empty($profileData['department'])) {
        $errors[] = 'Department is required.';
    }
    if (empty($profileData['year_of_study'])) {
        $errors[] = 'Year of study is required.';
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $userModel->uploadProfileImage($viewUserId, $_FILES['profile_image']);
        if ($uploadResult['success']) {
            $success .= ' Profile image updated.';
        } else {
            $errors[] = $uploadResult['message'];
        }
    }

    if (empty($errors)) {
        $updateResult = $userModel->updateProfile($viewUserId, $profileData);
        if ($updateResult['success']) {
            $success = 'Profile updated successfully.' . $success;
            // Refresh user data
            $viewUser = $userModel->getUserById($viewUserId);
        } else {
            $error = $updateResult['message'];
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = $isOwnProfile ? 'Your Profile' : 'User Profile - ' . htmlspecialchars($viewUser['first_name'] . ' ' . $viewUser['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5" style="max-width: 700px;">
        <h2 class="mb-4"><i class="fas fa-user me-2"></i><?php echo $pageTitle; ?></h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body d-flex align-items-center">
                <img src="<?php echo $viewUser['profile_image'] ? 'uploads/profiles/' . $viewUser['profile_image'] : 'assets/images/default-avatar.png'; ?>"
                     class="rounded-circle me-4" width="100" height="100" alt="Profile Image">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($viewUser['first_name'] . ' ' . $viewUser['last_name']); ?></h4>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($viewUser['email']); ?></p>
                    <span class="badge bg-<?php echo $viewUser['role'] === 'mentor' ? 'success' : 'primary'; ?>">
                        <?php echo ucfirst($viewUser['role']); ?>
                    </span>
                    <?php if ($viewUser['email_verified']): ?>
                        <span class="badge bg-info">Email Verified</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Email Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($isOwnProfile): ?>
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <input type="hidden" name="update_profile" value="1">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">First Name</label>
                        <input type="text" class="form-control" name="first_name"
                               value="<?php echo htmlspecialchars($viewUser['first_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" class="form-control" name="last_name"
                               value="<?php echo htmlspecialchars($viewUser['last_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone"
                               value="<?php echo htmlspecialchars($viewUser['phone']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Profile Image</label>
                        <input type="file" class="form-control" name="profile_image" accept="image/*">
                        <div class="form-text">JPG, PNG, GIF. Max 5MB.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department/Major</label>
                        <input type="text" class="form-control" name="department"
                               value="<?php echo htmlspecialchars($viewUser['department']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Year of Study</label>
                        <select class="form-select" name="year_of_study" required>
                            <option value="">Select…</option>
                            <?php foreach ($years as $k => $label): ?>
                                <option value="<?php echo $k; ?>" <?php if($viewUser['year_of_study'] == $k) echo 'selected'; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bio</label>
                        <textarea class="form-control" name="bio" rows="4"><?php echo htmlspecialchars($viewUser['bio']); ?></textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
            <a href="dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </form>
        <?php else: ?>
        <!-- Read-only profile for other users -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>Department:</strong> <?php echo htmlspecialchars($viewUser['department']); ?><br>
                <strong>Year of Study:</strong> <?php echo htmlspecialchars($viewUser['year_of_study']); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($viewUser['phone']); ?>
            </div>
            <div class="col-md-6 mb-3">
                <strong>Bio:</strong><br>
                <div><?php echo nl2br(htmlspecialchars($viewUser['bio'])); ?></div>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>