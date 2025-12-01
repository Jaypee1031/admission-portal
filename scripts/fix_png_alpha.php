<?php
/**
 * Fix PNG Alpha Channel Issues for TCPDF
 * This script converts PNG images with alpha channels to JPEG format
 */

require_once 'config/config.php';

echo "<h2>PNG Alpha Channel Fix for TCPDF</h2>";

// Function to convert PNG with alpha to JPEG
function convertPngToJpeg($sourcePath, $destinationPath) {
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    // Check if GD is available
    if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
        echo "<p style='color: red;'>GD extension not available for image conversion</p>";
        return false;
    }
    
    try {
        // Create image from PNG
        $image = imagecreatefrompng($sourcePath);
        if ($image === false) {
            echo "<p style='color: red;'>Failed to create image from PNG: $sourcePath</p>";
            return false;
        }
        
        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create a new image with white background
        $jpegImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($jpegImage, 255, 255, 255);
        imagefill($jpegImage, 0, 0, $white);
        
        // Copy the PNG image onto the white background
        imagecopy($jpegImage, $image, 0, 0, 0, 0, $width, $height);
        
        // Save as JPEG
        $result = imagejpeg($jpegImage, $destinationPath, 90);
        
        // Clean up memory
        imagedestroy($image);
        imagedestroy($jpegImage);
        
        return $result;
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error converting image: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Function to process all PNG files in a directory
function processPngFiles($directory) {
    $files = glob($directory . '/*.png');
    $converted = 0;
    $errors = 0;
    
    echo "<h3>Processing PNG files in: $directory</h3>";
    
    foreach ($files as $file) {
        $filename = basename($file);
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $jpegPath = dirname($file) . '/' . $nameWithoutExt . '.jpg';
        
        echo "<p>Converting: $filename → " . basename($jpegPath) . "</p>";
        
        if (convertPngToJpeg($file, $jpegPath)) {
            echo "<p style='color: green;'>✓ Successfully converted</p>";
            $converted++;
        } else {
            echo "<p style='color: red;'>✗ Failed to convert</p>";
            $errors++;
        }
    }
    
    return ['converted' => $converted, 'errors' => $errors];
}

// Process uploads directory
$uploadsDir = 'uploads';
if (is_dir($uploadsDir)) {
    $result = processPngFiles($uploadsDir);
    echo "<h3>Summary:</h3>";
    echo "<p>Converted: {$result['converted']} files</p>";
    echo "<p>Errors: {$result['errors']} files</p>";
} else {
    echo "<p style='color: orange;'>Uploads directory not found</p>";
}

// Process assets directory
$assetsDir = 'assets/images';
if (is_dir($assetsDir)) {
    $result = processPngFiles($assetsDir);
    echo "<h3>Assets Summary:</h3>";
    echo "<p>Converted: {$result['converted']} files</p>";
    echo "<p>Errors: {$result['errors']} files</p>";
} else {
    echo "<p style='color: orange;'>Assets directory not found</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Update your PDF generation code to use .jpg files instead of .png files</li>";
echo "<li>Or modify the image paths in your PDF generation to use the converted JPEG files</li>";
echo "<li>Test the PDF generation again</li>";
echo "</ol>";

echo "<p><a href='check_extensions.php'>Check Extensions</a> | <a href='index.php'>Back to Application</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>
