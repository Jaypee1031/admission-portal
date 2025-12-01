<?php
// Simple router for PHP built-in server with clean URLs
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$path = parse_url($uri, PHP_URL_PATH);

// Normalize leading slash
$path = ltrim($path, '/');

// Default to index when no path specified
if ($path === '' || $path === '/') {
    $path = 'index';
}

// If there is no extension, try to resolve to a PHP script
if (pathinfo($path, PATHINFO_EXTENSION) === '') {
    if (file_exists($path) && is_file($path)) {
        // e.g. public assets without extension
        $target = $path;
    } elseif (file_exists($path . '.php') && is_file($path . '.php')) {
        $target = $path . '.php';
    } else {
        $target = null;
    }
} else {
    $target = $path;
}

if ($target !== null && file_exists($target) && is_file($target)) {
    // Handle PHP scripts
    if (pathinfo($target, PATHINFO_EXTENSION) === 'php') {
        include $target;
    } else {
        // Serve static files
        $mimeType = function_exists('mime_content_type') ? mime_content_type($target) : 'application/octet-stream';
        header("Content-Type: $mimeType");
        readfile($target);
    }
} else {
    // 404 Not Found
    http_response_code(404);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>404 - Page Not Found</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #d32f2f; font-size: 72px; margin-bottom: 20px; }
            .message { font-size: 24px; margin-bottom: 30px; }
            .home-link { 
                display: inline-block; 
                padding: 12px 24px; 
                background: #1976d2; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                font-size: 18px;
            }
            .home-link:hover { background: #1565c0; }
        </style>
    </head>
    <body>
        <div class='error'>404</div>
        <div class='message'>Page Not Found</div>
        <a href='/' class='home-link'>Go Home</a>
    </body>
    </html>";
}
?>