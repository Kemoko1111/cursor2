<?php
require_once 'config/app.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Initialize models
$userModel = new User();
$resourceModel = new Resource();
$mentorshipModel = new Mentorship();

// Get current user data
$currentUser = $userModel->getUserById($userId);
if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

$error = '';
$success = '';

// Handle file upload (mentors only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource']) && $userRole === 'mentor') {
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $resourceData = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'category' => sanitizeInput($_POST['category'] ?? ''),
            'tags' => sanitizeInput($_POST['tags'] ?? ''),
            'is_public' => isset($_POST['is_public']) ? 1 : 0
        ];
        
        if (empty($resourceData['title'])) {
            $error = 'Resource title is required';
        } else {
            $result = $resourceModel->uploadResource($userId, $resourceData, $_FILES['resource_file']);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } else {
        $error = 'Please select a file to upload';
    }
}

// Handle resource sharing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_resource']) && $userRole === 'mentor') {
    $resourceId = intval($_POST['resource_id']);
    $selectedMentees = $_POST['mentee_ids'] ?? [];
    
    if (!empty($selectedMentees)) {
        $result = $resourceModel->shareResource($resourceId, $userId, $selectedMentees);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please select at least one mentee to share with';
    }
}

// Handle resource deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource']) && $userRole === 'mentor') {
    $resourceId = intval($_POST['resource_id']);
    $result = $resourceModel->deleteResource($resourceId, $userId);
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get resources based on user role
if ($userRole === 'mentor') {
    $resources = $resourceModel->getMentorResources($userId);
    $activeMentees = $mentorshipModel->getActiveMentorships($userId, 'mentor');
    $resourceStats = $resourceModel->getResourceStats($userId);
} else {
    $searchTerm = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $resources = $resourceModel->getAvailableResources($userId, $searchTerm, $category);
}

$categories = $resourceModel->getResourceCategories();

$pageTitle = 'Resources - Menteego';

// Helper functions for file icons and size formatting
function getFileIcon($fileType) {
    $icons = [
        'pdf' => 'pdf',
        'doc' => 'word', 'docx' => 'word',
        'ppt' => 'powerpoint', 'pptx' => 'powerpoint',
        'xls' => 'excel', 'xlsx' => 'excel',
        'txt' => 'alt',
        'zip' => 'archive', 'rar' => 'archive'
    ];
    return $icons[$fileType] ?? 'alt';
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
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
                        <a class="nav-link active" href="/resources.php">
                            <i class="fas fa-file-alt me-1"></i>Resources
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
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">
                        <i class="fas fa-file-alt me-2"></i>
                        <?php echo $userRole === 'mentor' ? 'Manage Resources' : 'Browse Resources'; ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <?php if ($userRole === 'mentor'): ?>
                            Upload and share educational resources with your mentees
                        <?php else: ?>
                            Access learning materials shared by your mentors
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if ($userRole === 'mentor'): ?>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Upload Resource
                        </button>
                    <?php else: ?>
                        <a href="/reports.php" class="btn btn-outline-light">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Alert Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($userRole === 'mentor'): ?>
            <!-- Mentor Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary">
                            <?php echo $resourceStats['total_resources']; ?>
                        </div>
                        <div class="fw-semibold">Total Resources</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-success">
                            <?php echo $resourceStats['public_resources']; ?>
                        </div>
                        <div class="fw-semibold">Public Resources</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning">
                            <?php echo $resourceStats['private_resources']; ?>
                        </div>
                        <div class="fw-semibold">Private Resources</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info">
                            <?php echo $resourceStats['total_downloads']; ?>
                        </div>
                        <div class="fw-semibold">Total Downloads</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Search and Filter for Mentees -->
            <div class="card border-radius-lg shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Resources</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                   placeholder="Search by title, description, or tags">
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resources List -->
        <div class="card border-radius-lg shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title fw-bold mb-0">
                    <i class="fas fa-folder-open me-2 text-primary"></i>
                    <?php echo $userRole === 'mentor' ? 'My Resources' : 'Available Resources'; ?>
                </h5>
            </div>
            <div class="card-body px-4">
                <?php if (empty($resources)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No resources found</h6>
                        <p class="text-muted mb-0">
                            <?php if ($userRole === 'mentor'): ?>
                                Start by uploading your first resource to share with mentees.
                            <?php else: ?>
                                Ask your mentors to share some learning materials with you.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Resource</th>
                                    <th>Category</th>
                                    <th>Size</th>
                                    <?php if ($userRole === 'mentee'): ?>
                                        <th>Shared By</th>
                                    <?php else: ?>
                                        <th>Downloads</th>
                                        <th>Visibility</th>
                                    <?php endif; ?>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $resource): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-<?php echo getFileIcon($resource['file_type']); ?> fa-2x text-primary me-3"></i>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">
                                                        <?php echo htmlspecialchars($resource['title']); ?>
                                                    </h6>
                                                    <?php if (!empty($resource['description'])): ?>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars(substr($resource['description'], 0, 100)); ?>
                                                            <?php echo strlen($resource['description']) > 100 ? '...' : ''; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($resource['category'])): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($resource['category']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                                                                         <small class="text-muted">
                                                 <?php echo formatFileSize($resource['file_size']); ?>
                                             </small>
                                        </td>
                                        <?php if ($userRole === 'mentee'): ?>
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']); ?>
                                                </small>
                                            </td>
                                        <?php else: ?>
                                            <td>
                                                <span class="text-primary fw-semibold">
                                                    <?php echo $resource['download_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($resource['is_public']): ?>
                                                    <span class="badge bg-success">Public</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Private</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($resource['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/api/download-resource.php?id=<?php echo $resource['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php if ($userRole === 'mentor'): ?>
                                                    <button class="btn btn-outline-success" 
                                                            onclick="showShareModal(<?php echo $resource['id']; ?>, '<?php echo htmlspecialchars($resource['title']); ?>')">
                                                        <i class="fas fa-share"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteResource(<?php echo $resource['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($userRole === 'mentor'): ?>
        <!-- Upload Resource Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Upload Resource
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label fw-semibold">Resource Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Brief description of the resource"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="category" class="form-label fw-semibold">Category</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           placeholder="e.g., Programming, Design, Research">
                                </div>
                                <div class="col-md-6">
                                    <label for="tags" class="form-label fw-semibold">Tags</label>
                                    <input type="text" class="form-control" id="tags" name="tags" 
                                           placeholder="Comma-separated tags">
                                </div>
                                <div class="col-12">
                                    <label for="resource_file" class="form-label fw-semibold">Select File *</label>
                                    <input type="file" class="form-control" id="resource_file" name="resource_file" 
                                           accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar" required>
                                    <div class="form-text">
                                        Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP, RAR (Max: 10MB)
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public">
                                        <label class="form-check-label" for="is_public">
                                            Make this resource public (visible to all your mentees)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="upload_resource" class="btn btn-primary">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Upload Resource
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Share Resource Modal -->
        <div class="modal fade" id="shareModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-share me-2"></i>Share Resource
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="shareResourceForm">
                        <div class="modal-body">
                            <p class="mb-3">Share "<span id="shareResourceTitle"></span>" with:</p>
                            <input type="hidden" name="resource_id" id="shareResourceId">
                            
                            <?php if (!empty($activeMentees)): ?>
                                <?php foreach ($activeMentees as $mentee): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="mentee_ids[]" 
                                               value="<?php echo $mentee['id']; ?>" id="mentee_<?php echo $mentee['id']; ?>">
                                        <label class="form-check-label d-flex align-items-center" for="mentee_<?php echo $mentee['id']; ?>">
                                            <img src="<?php echo $mentee['profile_image'] ? 'uploads/profiles/' . $mentee['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="">
                                            <div>
                                                <div class="fw-semibold">
                                                    <?php echo htmlspecialchars($mentee['first_name'] . ' ' . $mentee['last_name']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($mentee['department']); ?>
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">You don't have any active mentees to share with.</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="share_resource" class="btn btn-success" 
                                    <?php echo empty($activeMentees) ? 'disabled' : ''; ?>>
                                <i class="fas fa-share me-2"></i>Share Resource
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Form -->
        <form method="POST" id="deleteResourceForm" style="display: none;">
            <input type="hidden" name="resource_id" id="deleteResourceId">
            <input type="hidden" name="delete_resource" value="1">
        </form>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        function showShareModal(resourceId, resourceTitle) {
            document.getElementById('shareResourceId').value = resourceId;
            document.getElementById('shareResourceTitle').textContent = resourceTitle;
            
            // Uncheck all checkboxes
            document.querySelectorAll('#shareModal input[type="checkbox"]').forEach(cb => cb.checked = false);
            
            const modal = new bootstrap.Modal(document.getElementById('shareModal'));
            modal.show();
        }

        function deleteResource(resourceId) {
            if (confirm('Are you sure you want to delete this resource? This action cannot be undone.')) {
                document.getElementById('deleteResourceId').value = resourceId;
                document.getElementById('deleteResourceForm').submit();
            }
        }


    </script>
</body>
</html>