<?php
/**
 * Check PHP Extensions for TCPDF Image Processing
 */

echo "<h2>PHP Extensions Check for TCPDF Image Processing</h2>";

// Check GD extension
echo "<h3>GD Extension:</h3>";
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✓ GD extension is loaded</p>";
    $gdInfo = gd_info();
    echo "<ul>";
    foreach ($gdInfo as $key => $value) {
        echo "<li><strong>$key:</strong> " . ($value ? 'Yes' : 'No') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ GD extension is NOT loaded</p>";
}

// Check ImageMagick extension
echo "<h3>ImageMagick Extension:</h3>";
if (extension_loaded('imagick')) {
    echo "<p style='color: green;'>✓ ImageMagick extension is loaded</p>";
    try {
        $imagick = new Imagick();
        echo "<p>ImageMagick version: " . $imagick->getVersion()['versionString'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ ImageMagick loaded but error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ ImageMagick extension is NOT loaded</p>";
}

// Check specific functions
echo "<h3>Image Processing Functions:</h3>";
$functions = [
    'imagecreatefrompng' => 'PNG support in GD',
    'imagecreatefromjpeg' => 'JPEG support in GD',
    'imagecreatefromgif' => 'GIF support in GD',
    'imagepng' => 'PNG output in GD',
    'imagejpeg' => 'JPEG output in GD'
];

foreach ($functions as $function => $description) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✓ $function - $description</p>";
    } else {
        echo "<p style='color: red;'>✗ $function - $description</p>";
    }
}

// Test PNG with alpha channel
echo "<h3>PNG Alpha Channel Test:</h3>";
$testImage = 'assets/images/line.png'; // Using the line.png from your assets
if (file_exists($testImage)) {
    echo "<p>Testing with: $testImage</p>";
    
    if (extension_loaded('gd') && function_exists('imagecreatefrompng')) {
        try {
            $img = imagecreatefrompng($testImage);
            if ($img !== false) {
                echo "<p style='color: green;'>✓ GD can read PNG file</p>";
                
                // Check if image has alpha channel
                $hasAlpha = imageistruecolor($img) && (imagecolorstotal($img) == 0);
                echo "<p>Has alpha channel: " . ($hasAlpha ? 'Yes' : 'No') . "</p>";
                
                imagedestroy($img);
            } else {
                echo "<p style='color: red;'>✗ GD cannot read PNG file</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error reading PNG: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠ Test image not found: $testImage</p>";
}

echo "<h3>Recommendations:</h3>";
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    echo "<p style='color: red;'><strong>CRITICAL:</strong> Neither GD nor ImageMagick is available. You need to install at least one of them.</p>";
} elseif (extension_loaded('gd') && !extension_loaded('imagick')) {
    echo "<p style='color: orange;'><strong>WARNING:</strong> Only GD is available. For better PNG alpha channel support, consider installing ImageMagick.</p>";
} elseif (!extension_loaded('gd') && extension_loaded('imagick')) {
    echo "<p style='color: green;'><strong>GOOD:</strong> ImageMagick is available, which provides excellent PNG alpha channel support.</p>";
} else {
    echo "<p style='color: green;'><strong>EXCELLENT:</strong> Both GD and ImageMagick are available.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>
