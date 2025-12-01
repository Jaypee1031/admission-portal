<?php
/**
 * Setup script to create uploads directory with proper permissions
 * Run this script once to ensure the uploads directory exists
 */

$uploadDir = 'uploads/profile_photos/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "✅ Created uploads directory: " . $uploadDir . "\n";
    } else {
        echo "❌ Failed to create uploads directory: " . $uploadDir . "\n";
        exit(1);
    }
} else {
    echo "✅ Uploads directory already exists: " . $uploadDir . "\n";
}

// Create .htaccess file to prevent direct access to uploaded files
$htaccessContent = "# Prevent direct access to uploaded files\n";
$htaccessContent .= "Order Deny,Allow\n";
$htaccessContent .= "Deny from all\n";
$htaccessContent .= "\n";
$htaccessContent .= "# Allow access to images only\n";
$htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
$htaccessContent .= "    Order Allow,Deny\n";
$htaccessContent .= "    Allow from all\n";
$htaccessContent .= "</FilesMatch>\n";

$htaccessFile = $uploadDir . '.htaccess';
if (!file_exists($htaccessFile)) {
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "✅ Created .htaccess file for security\n";
    } else {
        echo "⚠️  Warning: Could not create .htaccess file\n";
    }
} else {
    echo "✅ .htaccess file already exists\n";
}

// Check directory permissions
if (is_writable($uploadDir)) {
    echo "✅ Uploads directory is writable\n";
} else {
    echo "❌ Uploads directory is not writable. Please check permissions.\n";
    echo "   Run: chmod 755 " . $uploadDir . "\n";
}

echo "\n🎉 Setup complete! Profile photo uploads should now work properly.\n";
?>
