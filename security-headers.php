<?php
/**
 * Security Headers Configuration
 * Add these headers to prevent security warnings and improve security
 */

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Content Security Policy (CSP)
$csp = "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com; " .
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
        "img-src 'self' data: https:; " .
        "font-src 'self' https://cdnjs.cloudflare.com; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self';";

header("Content-Security-Policy: " . $csp);

// HTTP Strict Transport Security (HSTS)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// CORS Headers (if needed for API)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowedOrigins = [
        'https://yourdomain.com',
        'https://www.yourdomain.com',
        'http://localhost:3000', // For development
        'http://localhost:8080'  // For development
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
    }
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>