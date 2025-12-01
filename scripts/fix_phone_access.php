<?php
/**
 * Fix Phone Access Issues
 * This script helps troubleshoot and fix phone access problems
 */

echo "📱 Fixing Phone Access Issues\n";
echo "============================\n\n";

// Get current IP
$ip = getLocalIP();
echo "📡 Your IP Address: $ip\n\n";

// Check if server is running
$serverRunning = false;
$output = shell_exec('netstat -an | findstr :8443');
if (strpos($output, '8443') !== false) {
    $serverRunning = true;
    echo "✅ HTTPS Server is running on port 8443\n";
} else {
    echo "❌ HTTPS Server is NOT running on port 8443\n";
}

echo "\n🔧 Troubleshooting Steps:\n";
echo "========================\n\n";

echo "1. 🚀 START THE SERVER:\n";
echo "   Run this command in Command Prompt (as Administrator):\n";
echo "   php -S 0.0.0.0:8443 -t . router.php\n\n";

echo "2. 🔥 FIREWALL FIX:\n";
echo "   Windows Firewall is blocking port 8443. Run Command Prompt as Administrator and execute:\n";
echo "   netsh advfirewall firewall add rule name=\"PHP HTTPS Server\" dir=in action=allow protocol=TCP localport=8443\n\n";

echo "3. 📱 PHONE SETTINGS:\n";
echo "   Make sure your phone is connected to the SAME WiFi network\n";
echo "   WiFi Network: Check your phone's WiFi settings\n\n";

echo "4. 🌐 ACCESS URLS:\n";
echo "   Try these URLs on your phone:\n";
echo "   - https://$ip:8443/Admission Portal\n";
echo "   - http://$ip:8000/Admission Portal (fallback)\n\n";

echo "5. 🔒 SECURITY WARNING:\n";
echo "   When you see security warning on phone:\n";
echo "   - Click 'Advanced' or 'Details'\n";
echo "   - Click 'Proceed to site' or 'Continue'\n";
echo "   - This is normal for self-signed certificates\n\n";

echo "6. 🔍 TEST CONNECTION:\n";
echo "   Test if your phone can reach your computer:\n";
echo "   - Open phone browser\n";
echo "   - Go to: http://$ip:8000 (simple test)\n";
echo "   - If this works, the issue is with HTTPS\n\n";

echo "📋 QUICK FIX COMMANDS:\n";
echo "=====================\n";
echo "1. Start HTTP server (easier for testing):\n";
echo "   php -S 0.0.0.0:8000\n\n";
echo "2. Start HTTPS server:\n";
echo "   php -S 0.0.0.0:8443 -t . router.php\n\n";
echo "3. Add firewall rule (run as Administrator):\n";
echo "   netsh advfirewall firewall add rule name=\"PHP Server\" dir=in action=allow protocol=TCP localport=8000\n";
echo "   netsh advfirewall firewall add rule name=\"PHP HTTPS Server\" dir=in action=allow protocol=TCP localport=8443\n\n";

echo "🎯 RECOMMENDED SOLUTION:\n";
echo "=======================\n";
echo "1. Start with HTTP first (port 8000) to test basic connectivity\n";
echo "2. Once HTTP works, then try HTTPS (port 8443)\n";
echo "3. Make sure firewall rules are added\n";
echo "4. Use the correct IP address: $ip\n\n";

echo "📞 TEST STEPS:\n";
echo "=============\n";
echo "1. Start server: php -S 0.0.0.0:8000\n";
echo "2. On phone, go to: http://$ip:8000\n";
echo "3. If that works, try: https://$ip:8443/Admission Portal\n";
echo "4. If HTTPS fails, check firewall settings\n\n";

function getLocalIP() {
    $output = shell_exec('ipconfig');
    if (preg_match('/Wireless LAN adapter Wi-Fi:.*?IPv4 Address[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/s', $output, $matches)) {
        return $matches[1];
    }
    return '192.168.106.142'; // fallback
}
?>
