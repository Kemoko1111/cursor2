<?php
require_once 'config/app.php';

// Only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Only mentees should browse mentors – redirect others
if ($userRole !== 'mentee') {
    redirect('/dashboard.php');
}

// Initialise models
$userModel       = new User();
$mentorshipModel = new Mentorship();

// ---------------------------------------------------------------------
// Handle incoming POST (mentorship-request form submission)
// ---------------------------------------------------------------------
$alert = null; // success / error message to show the user

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mentor_id'])) {
    $mentorId = (int) $_POST['mentor_id'];

    // Sanitise & build payload expected by Mentorship::sendRequest()
    $requestPayload = [
        'message'         => sanitizeInput($_POST['message']         ?? ''),
        'meeting_type'    => sanitizeInput($_POST['meeting_type']    ?? 'online'),
        'goals'           => sanitizeInput($_POST['goals']           ?? ''),
        'duration_weeks'  => (int) ($_POST['duration_weeks'] ?? 12),
    ];

    $result = $mentorshipModel->sendRequest($userId, $mentorId, $requestPayload);
    $alert  = [
        'type'    => $result['success'] ? 'success' : 'danger',
        'message' => $result['message'] ?? ($result['success'] ? 'Request sent!' : 'Unable to send request'),
    ];
}

// ---------------------------------------------------------------------
// Fetch mentors list based on search filters
// ---------------------------------------------------------------------
$searchTerm = trim($_GET['search'] ?? '');
$mentors    = $searchTerm === ''
    ? $userModel->getAllMentors(100, 0)
    : $userModel->searchMentors($searchTerm);

// Fetch current mentee pending requests + active mentorships to avoid duplicates
$pendingRequests   = $mentorshipModel->getMenteeRequests($userId);
$pendingMentorIds  = array_column($pendingRequests, 'mentor_id');
$activeMentorships = $mentorshipModel->getActiveMentorships($userId, 'mentee');
$activeMentorIds   = array_column($activeMentorships, 'id');

function parseSkills(string $skillsStr): array {
    if (empty($skillsStr)) return [];
    $items = explode('|', $skillsStr);
    $parsed = [];
    foreach ($items as $item) {
        [$name, $level] = array_pad(explode(':', $item, 2), 2, null);
        $parsed[] = trim($name);
    }
    return $parsed;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Mentors - Menteego</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation (same as dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard.php"><i class="fas fa-graduation-cap me-2"></i>Menteego</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/browse-mentors.php"><i class="fas fa-search me-1"></i>Find Mentors</a></li>
                    <li class="nav-item"><a class="nav-link" href="/messages.php"><i class="fas fa-comments me-1"></i>Messages</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/profile.php"><i class="fas fa-user me-1"></i>Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="/auth/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="fw-bold mb-4">Browse Mentors</h1>

        <!-- Alert after sending mentorship request -->
        <?php if ($alert): ?>
            <div class="alert alert-<?= $alert['type']; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($alert['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Search form -->
        <form class="row g-2 mb-4" method="get" action="">
            <div class="col-sm-10">
                <input type="text" name="search" value="<?= htmlspecialchars($searchTerm); ?>" class="form-control" placeholder="Search mentors by name, skill, or bio...">
            </div>
            <div class="col-sm-2 d-grid">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Search</button>
            </div>
        </form>

        <?php if (empty($mentors)): ?>
            <p class="text-muted">No mentors found. Try adjusting your search.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($mentors as $mentor): ?>
                    <?php
                        $mentorId = $mentor['id'];
                        $status   = in_array($mentorId, $activeMentorIds)  ? 'active'
                                 : (in_array($mentorId, $pendingMentorIds) ? 'pending' : 'none');
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex gap-3">
                                <img src="<?= $mentor['profile_image'] ? 'uploads/profiles/' . $mentor['profile_image'] : 'assets/images/default-avatar.png'; ?>" class="rounded-circle" width="64" height="64" alt="">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h5>
                                    <p class="text-muted mb-0 small"><?= htmlspecialchars($mentor['department']); ?></p>
                                    <?php $skillsArr = parseSkills($mentor['skills']); ?>
                                    <?php if ($skillsArr): ?>
                                        <div class="mt-2">
                                            <?php foreach (array_slice($skillsArr, 0, 5) as $skill): ?>
                                                <span class="badge bg-secondary me-1 mb-1 small"><?= htmlspecialchars($skill); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0">
                                <?php if ($status === 'active'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active Mentor</span>
                                <?php elseif ($status === 'pending'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>Request Pending</span>
                                <?php else: ?>
                                    <!-- Request button triggers hidden form submission -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="mentor_id" value="<?= $mentorId; ?>">
                                        <input type="hidden" name="message" value="Hi <?= htmlspecialchars($mentor['first_name']); ?>, I would like to learn from you!">
                                        <input type="hidden" name="meeting_type" value="online">
                                        <input type="hidden" name="goals" value="General mentorship">
                                        <input type="hidden" name="duration_weeks" value="12">
                                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-paper-plane me-1"></i>Request Mentorship</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>