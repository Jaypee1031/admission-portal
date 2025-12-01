<?php

function getFaviconBaseUrl() {
    // Get current script directory
    $currentDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Calculate relative path to root
    if (strpos($currentDir, '/admin') !== false || strpos($currentDir, '/student') !== false) {
        // We're in a subdirectory, go up one level
        return '../assets/images/favicon_io/';
    } else {
        // We're in root directory
        return 'assets/images/favicon_io/';
    }
}

/**
 * Generate complete favicon HTML tags
 * Includes all modern favicon formats and best practices
 */
function generateFaviconTags() {
    $baseUrl = getFaviconBaseUrl();
    
    $html = '';
    
    // Standard favicon (ICO format) - for older browsers
    $html .= '<link rel="icon" type="image/x-icon" href="' . $baseUrl . 'favicon.ico">' . "\n";
    
    // PNG favicons for different sizes - modern browsers
    $html .= '<link rel="icon" type="image/png" sizes="16x16" href="' . $baseUrl . 'favicon-16x16.png">' . "\n";
    $html .= '<link rel="icon" type="image/png" sizes="32x32" href="' . $baseUrl . 'favicon-32x32.png">' . "\n";
    
    // Apple Touch Icon - for iOS devices
    $html .= '<link rel="apple-touch-icon" sizes="180x180" href="' . $baseUrl . 'apple-touch-icon.png">' . "\n";
    
    // Android Chrome icons
    $html .= '<link rel="icon" type="image/png" sizes="192x192" href="' . $baseUrl . 'android-chrome-192x192.png">' . "\n";
    $html .= '<link rel="icon" type="image/png" sizes="512x512" href="' . $baseUrl . 'android-chrome-512x512.png">' . "\n";
    
    // Web App Manifest - for PWA support
    $html .= '<link rel="manifest" href="' . $baseUrl . 'site.webmanifest">' . "\n";
    
    // Theme color for mobile browsers
    $html .= '<meta name="theme-color" content="#1e40af">' . "\n";
    
    // Additional meta tags for better SEO and mobile support
    $html .= '<meta name="msapplication-TileColor" content="#1e40af">' . "\n";
    $html .= '<meta name="msapplication-config" content="' . $baseUrl . 'browserconfig.xml">' . "\n";
    
    return $html;
}

/**
 * Echo favicon tags directly
 * Use this function in HTML head sections
 */
function includeFavicon() {
    echo generateFaviconTags();
}
?>
