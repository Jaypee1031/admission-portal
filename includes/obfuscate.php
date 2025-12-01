<?php
/**
 * Source Code Obfuscation & Path Hiding
 * Removes all PHP file paths and sensitive information from HTML output
 */

class SourceObfuscator {
    
    /**
     * Remove all PHP file paths from HTML
     */
    public static function obfuscateHTML($html) {
        // Remove PHP file paths from href attributes
        $html = preg_replace('/href=["\']([^"\']*\.php[^"\']*)["\']/', 'href="/app"', $html);
        
        // Remove PHP file paths from action attributes
        $html = preg_replace('/action=["\']([^"\']*\.php[^"\']*)["\']/', 'action="/app"', $html);
        
        // Remove PHP file paths from src attributes
        $html = preg_replace('/src=["\']([^"\']*\.php[^"\']*)["\']/', 'src="/app"', $html);
        
        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Remove any remaining .php references
        $html = preg_replace('/\/[a-zA-Z0-9_\-\/]*\.php/i', '/app', $html);
        
        return $html;
    }
    
    /**
     * Remove sensitive headers
     */
    public static function removeHeaders() {
        // Remove server information
        header_remove('Server');
        header_remove('X-Powered-By');
        header_remove('X-AspNet-Version');
        header_remove('X-Runtime');
        
        // Add fake headers to confuse attackers
        header('Server: nginx/1.18.0');
        header('X-Powered-By: Node.js');
    }
    
    /**
     * Obfuscate error messages
     */
    public static function obfuscateError($message) {
        // Remove file paths from error messages
        $message = preg_replace('/[A-Za-z]:\\\\[a-zA-Z0-9_\-\\\\]*\.php/i', '[REDACTED]', $message);
        $message = preg_replace('/\/[a-zA-Z0-9_\-\/]*\.php/i', '[REDACTED]', $message);
        
        // Remove line numbers
        $message = preg_replace('/line \d+/i', 'line [REDACTED]', $message);
        
        // Remove function names that might reveal structure
        $message = preg_replace('/in (function|method) [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/i', 'in [REDACTED]', $message);
        
        return $message;
    }
    
    /**
     * Start output buffering with obfuscation
     */
    public static function startObfuscation() {
        ob_start(function($buffer) {
            return self::obfuscateHTML($buffer);
        });
    }
    
    /**
     * Get obfuscated file path for display
     */
    public static function getObfuscatedPath($path) {
        // Convert any file path to generic path
        return '/app';
    }
    
    /**
     * Hide form action paths
     */
    public static function getFormAction($action = '') {
        // Return generic action or current page
        return $_SERVER['REQUEST_URI'] ?? '/app';
    }
}

// Custom error handler to obfuscate errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errstr = SourceObfuscator::obfuscateError($errstr);
    $errfile = SourceObfuscator::getObfuscatedPath($errfile);
    
    // Log the real error
    error_log("Error: $errstr in $errfile on line $errline");
    
    // Show generic error to user
    if (ini_get('display_errors')) {
        echo "An error occurred. Please contact support.";
    }
    
    return true;
});

// Custom exception handler
set_exception_handler(function($exception) {
    $message = SourceObfuscator::obfuscateError($exception->getMessage());
    $file = SourceObfuscator::getObfuscatedPath($exception->getFile());
    
    // Log the real exception
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile());
    
    // Show generic error to user
    http_response_code(500);
    echo "An error occurred. Please contact support.";
    exit;
});

?>
