<?php
/**
 * Security Checker
 * Run this script to check for common security issues
 */

echo "🔒 Menteego Security Checker\n";
echo "=============================\n\n";

$issues = [];
$warnings = [];
$success = [];

// Check 1: HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $issues[] = "❌ Site is not using HTTPS - This causes major security warnings!";
} else {
    $success[] = "✅ HTTPS is enabled";
}

// Check 2: Security Headers
$headers = getallheaders();
$requiredHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000'
];

foreach ($requiredHeaders as $header => $expectedValue) {
    if (!isset($headers[$header])) {
        $warnings[] = "⚠️  Missing header: $header";
    } else {
        $success[] = "✅ Header present: $header";
    }
}

// Check 3: Mixed Content
$html = file_get_contents('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
if (strpos($html, 'http://') !== false) {
    $issues[] = "❌ Mixed content detected - HTTP resources on HTTPS page";
} else {
    $success[] = "✅ No mixed content detected";
}

// Check 4: SSL Certificate
$host = $_SERVER['HTTP_HOST'];
$context = stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
$socket = stream_socket_client("ssl://$host:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
if ($socket) {
    $cert = stream_context_get_params($socket);
    if (isset($cert['options']['ssl']['peer_certificate'])) {
        $success[] = "✅ SSL certificate is valid";
    } else {
        $issues[] = "❌ SSL certificate issues detected";
    }
    fclose($socket);
} else {
    $issues[] = "❌ Cannot verify SSL certificate";
}

// Check 5: File Permissions
$sensitiveFiles = [
    'config/app.php',
    'config/database.php',
    '.htaccess',
    'security-headers.php'
];

foreach ($sensitiveFiles as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        if (($perms & 0x0177) !== 0) {
            $warnings[] = "⚠️  File permissions too open: $file";
        } else {
            $success[] = "✅ File permissions secure: $file";
        }
    }
}

// Display Results
echo "Security Status:\n";
echo "================\n";

if (!empty($success)) {
    echo "\n✅ PASSED CHECKS:\n";
    foreach ($success as $item) {
        echo "  $item\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS:\n";
    foreach ($warnings as $item) {
        echo "  $item\n";
    }
}

if (!empty($issues)) {
    echo "\n❌ CRITICAL ISSUES:\n";
    foreach ($issues as $item) {
        echo "  $item\n";
    }
}

echo "\n📋 RECOMMENDATIONS:\n";
echo "==================\n";

if (!empty($issues)) {
    echo "1. Enable HTTPS on your server\n";
    echo "2. Install a valid SSL certificate\n";
    echo "3. Fix mixed content issues\n";
} else {
    echo "1. ✅ Your site appears secure!\n";
    echo "2. Consider adding rate limiting\n";
    echo "3. Implement two-factor authentication\n";
    echo "4. Regular security audits\n";
}

echo "\n🔧 QUICK FIXES:\n";
echo "===============\n";
echo "1. Ensure your hosting provider supports HTTPS\n";
echo "2. Update your domain's DNS to use HTTPS\n";
echo "3. Check that all external resources use HTTPS\n";
echo "4. Verify your SSL certificate is valid\n";
echo "5. Test your site with: https://www.ssllabs.com/ssltest/\n";

?>