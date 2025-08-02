<?php
require_once 'config/app.php';
require_once 'models/User.php';
require_once 'models/Mentorship.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userModel = new User();
$mentorshipModel = new Mentorship();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

if (!$currentUser) {
    session_destroy();
    redirect('/auth/login.php');
}

if ($currentUser['role'] !== 'mentee') {
    // Only mentees can schedule sessions
    header('HTTP/1.1 403 Forbidden');
    echo "<h2>Access Denied</h2><p>Only mentees can schedule sessions.</p>";
    exit;
}

// Get mentor ID from URL parameter
$mentorId = $_GET['mentor'] ?? null;

if (!$mentorId) {
    // Try to get mentor from active mentorship
    $activeMentorship = $mentorshipModel->getActiveMentorships($_SESSION['user_id'], 'mentee');
    if (!empty($activeMentorship)) {
        $mentorId = $activeMentorship[0]['mentor_id'];
    } else {
        $error = "No mentor found. Please ensure you have an active mentorship.";
    }
}

$mentor = null;
$availability = [];
$error = '';
$success = '';

if ($mentorId) {
    $mentor = $userModel->getUserById($mentorId);
    if ($mentor && $mentor['role'] === 'mentor') {
        // Fetch mentor's availability
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM availability WHERE user_id = :user_id AND is_available = 1 ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
        $stmt->bindParam(':user_id', $mentorId);
        $stmt->execute();
        
        foreach ($stmt->fetchAll() as $row) {
            $availability[$row['day_of_week']] = $row;
        }
    } else {
        $error = "Invalid mentor or mentor not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mentor) {
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $sessionDuration = $_POST['session_duration'] ?? '60';
    $sessionTopic = trim($_POST['session_topic'] ?? '');
    $sessionNotes = trim($_POST['session_notes'] ?? '');
    
    // Validate inputs
    if (empty($sessionDate) || empty($sessionTime) || empty($sessionTopic)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if the selected date and time is within mentor's availability
        $dayOfWeek = strtolower(date('l', strtotime($sessionDate)));
        $selectedTime = date('H:i:s', strtotime($sessionTime));
        
        if (!isset($availability[$dayOfWeek])) {
            $error = "Mentor is not available on " . ucfirst($dayOfWeek) . ".";
        } else {
            $startTime = $availability[$dayOfWeek]['start_time'];
            $endTime = $availability[$dayOfWeek]['end_time'];
            
            if ($selectedTime < $startTime || $selectedTime >= $endTime) {
                $error = "Selected time is outside mentor's availability window (" . date('g:i A', strtotime($startTime)) . " - " . date('g:i A', strtotime($endTime)) . ").";
            } else {
                // Check if session is in the future
                $sessionDateTime = $sessionDate . ' ' . $sessionTime;
                if (strtotime($sessionDateTime) <= time()) {
                    $error = "Session must be scheduled for a future date and time.";
                } else {
                    // Insert session into database
                    try {
                        require_once 'config/database.php';
                        $db = new Database();
                        $conn = $db->getConnection();
                        
                        $stmt = $conn->prepare("INSERT INTO sessions (mentor_id, mentee_id, session_date, session_time, duration, topic, notes, status) VALUES (:mentor_id, :mentee_id, :session_date, :session_time, :duration, :topic, :notes, 'scheduled')");
                        
                        $stmt->bindParam(':mentor_id', $mentorId);
                        $stmt->bindParam(':mentee_id', $_SESSION['user_id']);
                        $stmt->bindParam(':session_date', $sessionDate);
                        $stmt->bindParam(':session_time', $sessionTime);
                        $stmt->bindParam(':duration', $sessionDuration);
                        $stmt->bindParam(':topic', $sessionTopic);
                        $stmt->bindParam(':notes', $sessionNotes);
                        
                        if ($stmt->execute()) {
                            $success = "Session scheduled successfully! Your mentor will be notified.";
                            
                            // Send notification to mentor
                            require_once 'models/Notification.php';
                            $notificationModel = new Notification();
                            $notificationModel->createNotification(
                                $mentorId,
                                'session_scheduled',
                                'New Session Scheduled',
                                $currentUser['first_name'] . ' ' . $currentUser['last_name'] . ' has scheduled a session for ' . date('M j, Y', strtotime($sessionDate)) . ' at ' . date('g:i A', strtotime($sessionTime)) . '.',
                                $conn->lastInsertId()
                            );
                        } else {
                            $error = "Failed to schedule session. Please try again.";
                        }
                    } catch (Exception $e) {
                        $error = "An error occurred while scheduling the session.";
                    }
                }
            }
        }
    }
}

$pageTitle = 'Schedule Session - Menteego';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .availability-highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-card {
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .time-slot {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
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
                    <li class="nav-item">
                        <a class="nav-link" href="/browse-mentors.php">
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
                            <li><a class="dropdown-item" href="/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="/view-mentor-availability.php">Mentor Availability</a></li>
                        <li class="breadcrumb-item active">Schedule Session</li>
                    </ol>
                </nav>
                <h1 class="fw-bold">
                    <i class="fas fa-calendar-plus me-2 text-success"></i>
                    Schedule Session
                </h1>
            </div>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($mentor && !empty($availability)): ?>
            <!-- Mentor Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card form-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <img src="<?php echo $mentor['profile_image'] ? 'uploads/profiles/' . $mentor['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="rounded-circle" width="80" height="80" alt="Mentor">
                                </div>
                                <div class="col-md-10">
                                    <h4 class="mb-2"><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h4>
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <?php echo htmlspecialchars($mentor['department']); ?> • 
                                        <?php echo ucfirst($mentor['year_of_study']); ?> Year
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Availability Summary -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="availability-highlight">
                        <h6 class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            Mentor's Available Times
                        </h6>
                        <div class="row">
                            <?php foreach ($availability as $day => $slot): ?>
                                <div class="col-md-6 col-lg-3 mb-2">
                                    <div class="time-slot">
                                        <strong><?php echo ucfirst($day); ?>:</strong><br>
                                        <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session Scheduling Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card form-card">
                        <div class="card-header bg-white border-0 pt-4">
                            <h5 class="card-title fw-bold mb-0">
                                <i class="fas fa-calendar-plus me-2 text-primary"></i>
                                Schedule Your Session
                            </h5>
                            <p class="text-muted mb-0 mt-2">
                                Choose a date and time within your mentor's availability
                            </p>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="session_date" class="form-label fw-semibold">
                                            <i class="fas fa-calendar me-2"></i>Session Date *
                                        </label>
                                        <input type="date" class="form-control" id="session_date" name="session_date" 
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        <div class="form-text">Select a date in the future</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="session_time" class="form-label fw-semibold">
                                            <i class="fas fa-clock me-2"></i>Session Time *
                                        </label>
                                        <input type="time" class="form-control" id="session_time" name="session_time" required>
                                        <div class="form-text">Choose a time within mentor's availability</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="session_duration" class="form-label fw-semibold">
                                            <i class="fas fa-hourglass-half me-2"></i>Duration
                                        </label>
                                        <select class="form-select" id="session_duration" name="session_duration">
                                            <option value="30">30 minutes</option>
                                            <option value="60" selected>1 hour</option>
                                            <option value="90">1.5 hours</option>
                                            <option value="120">2 hours</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="session_topic" class="form-label fw-semibold">
                                            <i class="fas fa-book me-2"></i>Session Topic *
                                        </label>
                                        <input type="text" class="form-control" id="session_topic" name="session_topic" 
                                               placeholder="e.g., Python Programming, Career Advice" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="session_notes" class="form-label fw-semibold">
                                        <i class="fas fa-sticky-note me-2"></i>Additional Notes
                                    </label>
                                    <textarea class="form-control" id="session_notes" name="session_notes" rows="4" 
                                              placeholder="Any specific topics, questions, or context you'd like to discuss..."></textarea>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-calendar-check me-2"></i>Schedule Session
                                    </button>
                                    <a href="/view-mentor-availability.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tips Sidebar -->
                <div class="col-lg-4">
                    <div class="card form-card">
                        <div class="card-header bg-white border-0 pt-4">
                            <h6 class="card-title fw-bold mb-0">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>
                                Scheduling Tips
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Plan ahead:</strong> Schedule sessions at least 24 hours in advance
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Be specific:</strong> Include clear topics and questions
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Respect time:</strong> Choose appropriate session duration
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>Prepare:</strong> Review materials before the session
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($mentor && empty($availability)): ?>
            <!-- No Availability -->
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No Availability Set</h3>
                <p class="text-muted mb-4">
                    Your mentor hasn't set their availability yet. Please contact them to schedule a session.
                </p>
                <a href="/messages.php?mentor=<?php echo $mentorId; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-comments me-2"></i>Message Mentor
                </a>
            </div>

        <?php else: ?>
            <!-- No Mentor Found -->
            <div class="text-center py-5">
                <i class="fas fa-user-times fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">No Mentor Found</h3>
                <p class="text-muted mb-4">
                    You don't have an active mentorship yet. Browse mentors to find someone who can help you.
                </p>
                <a href="/browse-mentors.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Mentors
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to tomorrow
        document.getElementById('session_date').min = new Date().toISOString().split('T')[0];
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const date = document.getElementById('session_date').value;
            const time = document.getElementById('session_time').value;
            const topic = document.getElementById('session_topic').value.trim();
            
            if (!date || !time || !topic) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Check if date is in the future
            const selectedDateTime = new Date(date + ' ' + time);
            if (selectedDateTime <= new Date()) {
                e.preventDefault();
                alert('Session must be scheduled for a future date and time.');
                return false;
            }
        });
    </script>
</body>
</html>