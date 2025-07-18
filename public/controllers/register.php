<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use App\Models\User;
use App\Models\Role;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?route=register');
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$roleSlug  = $_POST['role'] ?? 'mentee';

$errors = [];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}
if (strlen($password) < 6) {
    $errors[] = 'Password should be at least 6 characters';
}
if ($errors) {
    $_SESSION['errors'] = $errors;
    header('Location: /?route=register');
    exit;
}

if (User::where('email', $email)->exists()) {
    $_SESSION['errors'] = ['Email already registered'];
    header('Location: /?route=register');
    exit;
}

$role = Role::where('name', $roleSlug)->first();

$user = User::create([
    'first_name' => $firstName,
    'last_name'  => $lastName,
    'email'      => $email,
    'password'   => password_hash($password, PASSWORD_DEFAULT),
    'role_id'    => $role->id,
]);

// Send verification email (simplified)
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USER'];
    $mail->Password   = $_ENV['MAIL_PASS'];
    $mail->Port       = $_ENV['MAIL_PORT'];

    $mail->setFrom($_ENV['MAIL_USER'], 'MenteeGo');
    $mail->addAddress($user->email, $user->first_name);
    $mail->Subject = 'Welcome to MenteeGo';
    $mail->Body    = "Hi {$user->first_name},\n\nThank you for registering at MenteeGo.";

    $mail->send();
} catch (Exception $e) {
    // Log error or ignore
}

$_SESSION['user_id'] = $user->id;
header('Location: /dashboard.php');