<?php
/**
 * HTTPS Server Starter for University Admission Portal
 * This script starts a PHP development server with HTTPS support
 * for testing on local network devices
 */

echo "🚀 Starting HTTPS Server for University Admission Portal\n";
echo "======================================================\n\n";

// Get current IP address
$ip = getLocalIP();
echo "📡 Detected IP Address: $ip\n";
echo "🔒 Starting HTTPS Server on port 8443...\n";
echo "🌐 Access URL: https://$ip:8443/Admission Portal\n\n";

echo "📱 Other devices can access using:\n";
echo "   https://$ip:8443/Admission Portal\n\n";

echo "⚠️  Note: Browsers will show security warning for self-signed certificate\n";
echo "   Click 'Advanced' → 'Proceed to site' to continue\n\n";

echo "🛑 Press Ctrl+C to stop the server\n";
echo "======================================================\n\n";

// Start HTTPS server
$command = "php -S 0.0.0.0:8443 -t . router.php";
passthru($command);

function getLocalIP() {
    // Try to get IP from ipconfig (Windows)
    $output = shell_exec('ipconfig');
    if (preg_match('/Wireless LAN adapter Wi-Fi:.*?IPv4 Address[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/s', $output, $matches)) {
        return $matches[1];
    }
    
    // Fallback method
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($sock, "8.8.8.8", 53);
    socket_getsockname($sock, $name);
    socket_close($sock);
    return $name;
}
?>
