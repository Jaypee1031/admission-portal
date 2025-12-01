<?php
/**
 * Path Obfuscator - Hide all PHP file paths from HTML output
 * This class provides methods to obfuscate file paths in HTML
 */

class PathObfuscator {
    
    /**
     * Obfuscate all file paths in the HTML
     */
    public static function obfuscateAll($html) {
        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Obfuscate href attributes
        $html = preg_replace('/href=["\']([^"\']*\.php[^"\']*)["\']/', 'href="/app"', $html);
        
        // Obfuscate action attributes
        $html = preg_replace('/action=["\']([^"\']*\.php[^"\']*)["\']/', 'action="/app"', $html);
        
        // Obfuscate src attributes
        $html = preg_replace('/src=["\']([^"\']*\.php[^"\']*)["\']/', 'src="/app"', $html);
        
        // Remove any remaining .php references
        $html = preg_replace('/\/[a-zA-Z0-9_\-\/]*\.php/i', '/app', $html);
        
        return $html;
    }
}

?>
