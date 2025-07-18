<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\User;

if (!isset($_SESSION['user_id'])) {
    header('Location: /?route=login');
    exit;
}

$user = User::find($_SESSION['user_id']);

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard – MenteeGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/dashboard.php">MenteeGo</a>
    <span class="navbar-text text-light ms-auto">Logged in as <?php echo htmlspecialchars($user->first_name) ?> (<?php echo $user->role->name ?>)</span>
    <a href="/logout.php" class="btn btn-outline-light ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
    <h1 class="mb-4">Dashboard</h1>

    <?php if ($user->role->name === 'admin'): ?>
        <p>Admin panel coming soon…</p>
    <?php elseif ($user->role->name === 'mentor'): ?>
        <p>Mentor workspace coming soon…</p>
    <?php else: ?>
        <p>Mentee workspace coming soon…</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>