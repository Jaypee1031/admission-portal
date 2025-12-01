<?php
/**
 * Test TCPDF Image Processing
 */

echo "<h2>TCPDF Image Processing Test</h2>";

// Test 1: Check extensions
echo "<h3>1. Extension Check</h3>";
echo "<p>GD Extension: " . (extension_loaded('gd') ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>') . "</p>";
echo "<p>ImageMagick Extension: " . (extension_loaded('imagick') ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>') . "</p>";

// Test 2: Check specific functions
echo "<h3>2. Function Check</h3>";
$functions = ['imagecreatefrompng', 'imagecreatefromjpeg', 'imagepng', 'imagejpeg'];
foreach ($functions as $func) {
    echo "<p>$func: " . (function_exists($func) ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</p>";
}

// Test 3: Try to process a PNG image
echo "<h3>3. PNG Image Test</h3>";
$testImage = 'assets/images/line.png';
if (file_exists($testImage)) {
    echo "<p>Testing with: $testImage</p>";
    
    if (extension_loaded('gd') && function_exists('imagecreatefrompng')) {
        try {
            $img = imagecreatefrompng($testImage);
            if ($img !== false) {
                echo "<p style='color: green;'>✓ Successfully loaded PNG with GD</p>";
                
                // Get image info
                $width = imagesx($img);
                $height = imagesy($img);
                echo "<p>Image dimensions: {$width}x{$height}</p>";
                
                // Check if it's a true color image (might have alpha)
                $isTrueColor = imageistruecolor($img);
                echo "<p>True color image: " . ($isTrueColor ? 'Yes' : 'No') . "</p>";
                
                imagedestroy($img);
            } else {
                echo "<p style='color: red;'>✗ Failed to load PNG</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ GD not available for PNG processing</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Test image not found: $testImage</p>";
}

// Test 4: Simple TCPDF test
echo "<h3>4. TCPDF Test</h3>";
try {
    require_once 'vendor/autoload.php';
    
    // Create a simple PDF with an image
    $pdf = new TCPDF();
    $pdf->AddPage();
    
    // Try to add the test image
    if (file_exists($testImage)) {
        try {
            $pdf->Image($testImage, 10, 10, 50, 0, 'PNG');
            echo "<p style='color: green;'>✓ TCPDF can process the PNG image</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ TCPDF error: " . $e->getMessage() . "</p>";
            
            // Try converting to JPEG
            echo "<p>Attempting to convert PNG to JPEG...</p>";
            if (extension_loaded('gd')) {
                $img = imagecreatefrompng($testImage);
                if ($img !== false) {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    
                    // Create white background
                    $jpegImg = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($jpegImg, 255, 255, 255);
                    imagefill($jpegImg, 0, 0, $white);
                    imagecopy($jpegImg, $img, 0, 0, 0, 0, $width, $height);
                    
                    $jpegFile = 'temp_test.jpg';
                    if (imagejpeg($jpegImg, $jpegFile, 90)) {
                        echo "<p style='color: green;'>✓ Successfully converted to JPEG</p>";
                        
                        // Try TCPDF with JPEG
                        try {
                            $pdf2 = new TCPDF();
                            $pdf2->AddPage();
                            $pdf2->Image($jpegFile, 10, 10, 50, 0, 'JPEG');
                            echo "<p style='color: green;'>✓ TCPDF can process the converted JPEG</p>";
                        } catch (Exception $e2) {
                            echo "<p style='color: red;'>✗ TCPDF still fails with JPEG: " . $e2->getMessage() . "</p>";
                        }
                        
                        // Clean up
                        unlink($jpegFile);
                    }
                    
                    imagedestroy($img);
                    imagedestroy($jpegImg);
                }
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ TCPDF initialization error: " . $e->getMessage() . "</p>";
}

echo "<h3>Solutions:</h3>";
echo "<ol>";
echo "<li><strong>Install GD Extension:</strong> Uncomment <code>extension=gd</code> in php.ini and restart Apache</li>";
echo "<li><strong>Install ImageMagick:</strong> For better PNG alpha support</li>";
echo "<li><strong>Convert PNG to JPEG:</strong> Use the conversion script to handle alpha channels</li>";
echo "<li><strong>Use the wrapper:</strong> Implement the TCPDF image wrapper for automatic conversion</li>";
echo "</ol>";

echo "<p><a href='tcpdf_config_fix.php'>Run Configuration Fix</a> | <a href='fix_png_alpha.php'>Convert PNG Files</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>
