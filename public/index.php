<?php
require_once __DIR__ . '/../src/bootstrap.php';

$path = $_GET['route'] ?? 'home';

switch ($path) {
    case 'register':
        include __DIR__ . '/views/register.php';
        break;
    case 'login':
        include __DIR__ . '/views/login.php';
        break;
    default:
        include __DIR__ . '/views/home.php';
        break;
}