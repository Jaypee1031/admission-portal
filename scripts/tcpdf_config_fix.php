<?php
/**
 * TCPDF Configuration Fix for PNG Alpha Channel Issues
 * This file provides alternative solutions for PNG image processing
 */

// Check if we're in a web context
if (php_sapi_name() !== 'cli') {
    echo "<h2>TCPDF PNG Alpha Channel Fix</h2>";
}

// Solution 1: Check and install required extensions
function checkAndSuggestExtensions() {
    echo "<h3>Solution 1: Install Required Extensions</h3>";
    
    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        echo "<p style='color: red;'><strong>CRITICAL:</strong> You need to install either GD or ImageMagick extension.</p>";
        echo "<h4>For XAMPP (Windows):</h4>";
        echo "<ol>";
        echo "<li>Open XAMPP Control Panel</li>";
        echo "<li>Click 'Config' next to Apache</li>";
        echo "<li>Select 'PHP (php.ini)'</li>";
        echo "<li>Find and uncomment these lines:</li>";
        echo "<ul>";
        echo "<li><code>extension=gd</code></li>";
        echo "<li><code>extension=imagick</code> (if available)</li>";
        echo "</ul>";
        echo "<li>Restart Apache</li>";
        echo "</ol>";
        
        echo "<h4>For Linux/Ubuntu:</h4>";
        echo "<pre>sudo apt-get install php-gd php-imagick</pre>";
        
        echo "<h4>For macOS:</h4>";
        echo "<pre>brew install php-gd php-imagick</pre>";
    } else {
        echo "<p style='color: green;'>✓ Required extensions are available</p>";
    }
}

// Solution 2: Create a wrapper function for TCPDF image handling
function createTcpdfImageWrapper() {
    $wrapperCode = '<?php
/**
 * TCPDF Image Wrapper - Handles PNG Alpha Channel Issues
 */

class TcpdfImageWrapper {
    
    /**
     * Safe image method that handles PNG alpha channels
     */
    public static function addImage($pdf, $file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array()) {
        
        // Check if file exists
        if (!file_exists($file)) {
            throw new Exception("Image file not found: " . $file);
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        // Handle PNG files with potential alpha channel issues
        if ($extension === \'png\') {
            // Try to convert PNG to JPEG if it has alpha channel
            $jpegFile = self::convertPngToJpeg($file);
            if ($jpegFile) {
                $file = $jpegFile;
                $type = \'JPEG\';
            }
        }
        
        // Add image to PDF
        return $pdf->Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
    }
    
    /**
     * Convert PNG to JPEG if it has alpha channel
     */
    private static function convertPngToJpeg($pngFile) {
        // Check if GD is available
        if (!extension_loaded(\'gd\') || !function_exists(\'imagecreatefrompng\')) {
            return false;
        }
        
        try {
            // Create image from PNG
            $image = imagecreatefrompng($pngFile);
            if ($image === false) {
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
            
            // Create temporary JPEG file
            $tempJpegFile = sys_get_temp_dir() . \'/\' . uniqid(\'tcpdf_\') . \'.jpg\';
            
            // Save as JPEG
            $result = imagejpeg($jpegImage, $tempJpegFile, 90);
            
            // Clean up memory
            imagedestroy($image);
            imagedestroy($jpegImage);
            
            return $result ? $tempJpegFile : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clean up temporary files
     */
    public static function cleanupTempFiles() {
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . \'/tcpdf_*.jpg\');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
?>';
    
    file_put_contents('includes/tcpdf_image_wrapper.php', $wrapperCode);
    echo "<p style='color: green;'>✓ Created TCPDF image wrapper at includes/tcpdf_image_wrapper.php</p>";
}

// Solution 3: Modify existing PDF generation files
function suggestCodeModifications() {
    echo "<h3>Solution 3: Code Modifications</h3>";
    echo "<p>To use the wrapper in your existing PDF generation code, replace:</p>";
    echo "<pre>";
    echo htmlspecialchars('$pdf->Image($imagePath, $x, $y, $w, $h);');
    echo "</pre>";
    echo "<p>With:</p>";
    echo "<pre>";
    echo htmlspecialchars('require_once "includes/tcpdf_image_wrapper.php";
TcpdfImageWrapper::addImage($pdf, $imagePath, $x, $y, $w, $h);');
    echo "</pre>";
}

// Run solutions
checkAndSuggestExtensions();
createTcpdfImageWrapper();
suggestCodeModifications();

echo "<h3>Quick Test</h3>";
echo "<p><a href='check_extensions.php'>Check Extensions</a> | <a href='fix_png_alpha.php'>Convert PNG Files</a></p>";

if (php_sapi_name() !== 'cli') {
    echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";
}
?>
