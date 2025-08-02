<?php
/**
 * Assets Management
 * Centralized asset loading and versioning
 */

class Assets {
    private static $version = '1.0.0';
    
    /**
     * Get CSS files with versioning
     */
    public static function getCSS() {
        $css = [
            'bootstrap' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'fontawesome' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            'custom' => '/assets/css/style.css'
        ];
        
        $output = '';
        foreach ($css as $name => $path) {
            $version = $name === 'custom' ? '?v=' . self::$version : '';
            $output .= "<link href=\"{$path}{$version}\" rel=\"stylesheet\">\n";
        }
        
        return $output;
    }
    
    /**
     * Get JavaScript files with versioning
     */
    public static function getJS() {
        $js = [
            'bootstrap' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            'custom' => '/assets/js/main.js'
        ];
        
        $output = '';
        foreach ($js as $name => $path) {
            $version = $name === 'custom' ? '?v=' . self::$version : '';
            $output .= "<script src=\"{$path}{$version}\"></script>\n";
        }
        
        return $output;
    }
    
    /**
     * Get image path with fallback
     */
    public static function getImage($path, $fallback = '/assets/images/default-avatar.png') {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
        return file_exists($fullPath) ? $path : $fallback;
    }
    
    /**
     * Get PWA manifest
     */
    public static function getPWA() {
        return [
            'manifest' => '/manifest.json',
            'theme-color' => '#0d6efd',
            'apple-touch-icon' => '/assets/images/icon-152x152.png'
        ];
    }
    
    /**
     * Get security headers
     */
    public static function getSecurityHeaders() {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin'
        ];
    }
}

// Usage example:
// echo Assets::getCSS();
// echo Assets::getJS();
// echo Assets::getImage('/uploads/profiles/user.jpg');
?>