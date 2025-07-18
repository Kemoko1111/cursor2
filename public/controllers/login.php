<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\User;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?route=login');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$user = User::where('email', $email)->first();

if (!$user || !password_verify($password, $user->password)) {
    $_SESSION['errors'] = ['Invalid credentials'];
    header('Location: /?route=login');
    exit;
}

$_SESSION['user_id'] = $user->id;
header('Location: /dashboard.php');