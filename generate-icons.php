<?php
/**
 * Generate PWA Icons
 * This script creates the required icon sizes for PWA
 * You'll need to place a base icon (512x512) in assets/images/icon-base.png
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$baseImage = 'assets/images/icon-base.png';
$outputDir = 'assets/images/';

// Check if base image exists
if (!file_exists($baseImage)) {
    echo "❌ Base image not found: $baseImage\n";
    echo "Please add a 512x512 PNG image as $baseImage\n";
    exit;
}

// Create output directory if it doesn't exist
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Load base image
$source = imagecreatefrompng($baseImage);

foreach ($sizes as $size) {
    // Create new image
    $dest = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($dest, false);
    imagesavealpha($dest, true);
    $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
    imagefill($dest, 0, 0, $transparent);
    
    // Resize
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $size, $size, 512, 512);
    
    // Save
    $filename = $outputDir . "icon-{$size}x{$size}.png";
    imagepng($dest, $filename);
    imagedestroy($dest);
    
    echo "✅ Generated: $filename\n";
}

imagedestroy($source);
echo "\n🎉 All PWA icons generated successfully!\n";
echo "Your web app is now ready to be installed as a mobile app.\n";
?>