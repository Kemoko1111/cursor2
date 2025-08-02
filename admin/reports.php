<?php
require_once '../config/app.php';
require_once '../models/User.php';
require_once '../models/Report.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

if (!$currentUser || $currentUser['role'] !== 'admin') {
    redirect('/dashboard.php');
}

$reportModel = new Report();
$error = '';
$success = '';

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    $parameters = [];
    
    switch ($reportType) {
        case 'mentorship_summary':
            $reportId = $reportModel->generateMentorshipSummary($parameters);
            break;
        case 'user_activity':
            $reportId = $reportModel->generateUserActivity($parameters);
            break;
        case 'resource_usage':
            $reportId = $reportModel->generateResourceUsage($parameters);
            break;
        case 'system_stats':
            $reportId = $reportModel->generateSystemStats($parameters);
            break;
        default:
            $error = "Invalid report type.";
            break;
    }
    
    if (isset($reportId) && $reportId) {
        $success = "Report generated successfully! Report ID: " . $reportId;
    } elseif (!isset($error)) {
        $error = "Failed to generate report.";
    }
}

// Get existing reports
$reports = $reportModel->getAllReports(50, 0);
$reportCounts = $reportModel->getReportCountByStatus();

$pageTitle = 'Reports - Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .report-card {
            transition: transform 0.2s ease;
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Menteego Admin
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
                        <a class="nav-link active" href="/admin/reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo $currentUser['profile_image'] ? '../uploads/profiles/' . $currentUser['profile_image'] : '../assets/images/default-avatar.png'; ?>" 
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
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </nav>
                <h1 class="fw-bold">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>
                    System Reports
                </h1>
                <p class="text-muted">Generate and manage system reports</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Report Generation -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="fas fa-plus-circle me-2 text-success"></i>
                            Generate New Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="report_type" class="form-label fw-semibold">
                                        <i class="fas fa-chart-line me-2"></i>Report Type
                                    </label>
                                    <select class="form-select" id="report_type" name="report_type" required>
                                        <option value="">Select a report type...</option>
                                        <option value="mentorship_summary">Mentorship Summary Report</option>
                                        <option value="user_activity">User Activity Report</option>
                                        <option value="resource_usage">Resource Usage Report</option>
                                        <option value="system_stats">System Statistics Report</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-cog me-2"></i>Generate Report
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-chart-pie me-2 text-info"></i>
                    Report Statistics
                </h5>
                <div class="row">
                    <?php foreach ($reportCounts as $count): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm text-center">
                                <div class="card-body">
                                    <h3 class="text-primary mb-1"><?php echo $count['count']; ?></h3>
                                    <p class="text-muted mb-0 text-capitalize"><?php echo $count['status']; ?> Reports</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="card-title fw-bold mb-0">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Recent Reports
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reports)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No reports generated yet</h6>
                                <p class="text-muted mb-0">Generate your first report using the form above.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Generated By</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                                        <?php if ($report['description']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($report['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary text-capitalize">
                                                        <?php echo str_replace('_', ' ', $report['report_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = match($report['status']) {
                                                        'completed' => 'success',
                                                        'generating' => 'warning',
                                                        'failed' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                                        <?php echo ucfirst($report['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($report['first_name']): ?>
                                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">System</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($report['status'] === 'completed' && $report['file_path']): ?>
                                                            <a href="/<?php echo $report['file_path']; ?>" 
                                                               class="btn btn-outline-primary btn-sm" 
                                                               target="_blank" 
                                                               title="Download Report">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteReport(<?php echo $report['id']; ?>)"
                                                                title="Delete Report">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteReport(reportId) {
            if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
                // You can implement AJAX deletion here
                console.log('Delete report:', reportId);
                alert('Delete functionality would be implemented here.');
            }
        }
    </script>
</body>
</html>