<?php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Application Configuration
define('APP_ROOT', dirname(__DIR__));
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'false') === 'true');

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'menteego_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Email Configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Menteego Platform');
define('MAIL_FROM_EMAIL', $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@menteego.com');

// Security Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default-secret-change-this');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-this-32-chars');
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 7200);

// Upload Configuration
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? 5242880); // 5MB
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Organization Settings
define('ORG_NAME', $_ENV['ORG_NAME'] ?? 'ACES - Academic Computing Excellence Society');
define('ORG_EMAIL', $_ENV['ORG_EMAIL'] ?? 'admin@aces.org');
define('ORG_DOMAIN', $_ENV['ORG_DOMAIN'] ?? 'aces.org');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Timezone
date_default_timezone_set('UTC');

// Autoloader
spl_autoload_register(function ($className) {
    $directories = [
        APP_ROOT . '/classes/',
        APP_ROOT . '/models/',
        APP_ROOT . '/controllers/',
        APP_ROOT . '/services/',
        APP_ROOT . '/middleware/',
        APP_ROOT . '/utils/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper Functions
function loadView($view, $data = []) {
    extract($data);
    $viewFile = APP_ROOT . '/views/' . $view . '.php';
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        throw new Exception("View {$view} not found");
    }
}

function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isAcesEmail($email) {
    return strpos($email, '@' . ORG_DOMAIN) !== false;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>