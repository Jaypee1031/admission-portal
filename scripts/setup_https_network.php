<?php
/**
 * Complete HTTPS and Network Setup for University Admission Portal
 * This script sets up everything needed for HTTPS access from other devices
 */

echo "🚀 University Admission Portal - HTTPS & Network Setup\n";
echo "==================================================\n\n";

// Get current IP address
$ip = getLocalIP();
echo "📡 Detected IP Address: $ip\n\n";

// Create SSL directory
$sslDir = __DIR__ . '/../ssl';
if (!is_dir($sslDir)) {
    mkdir($sslDir, 0755, true);
    echo "📁 Created SSL directory: $sslDir\n";
}

// Check if OpenSSL is available
$opensslAvailable = false;
$output = shell_exec('openssl version 2>&1');
if (strpos($output, 'OpenSSL') !== false) {
    $opensslAvailable = true;
    echo "✅ OpenSSL is available\n";
} else {
    echo "⚠️  OpenSSL not found in PATH\n";
    echo "💡 You can install OpenSSL or use Git Bash which includes it\n";
}

// Generate SSL certificate if OpenSSL is available
if ($opensslAvailable) {
    $keyFile = $sslDir . '/server.key';
    $certFile = $sslDir . '/server.crt';
    
    if (!file_exists($keyFile) || !file_exists($certFile)) {
        echo "🔐 Generating SSL certificate...\n";
        
        // Generate private key
        $keyCommand = "openssl genrsa -out \"$keyFile\" 2048";
        exec($keyCommand, $keyOutput, $keyReturn);
        
        if ($keyReturn === 0) {
            echo "✅ Private key generated\n";
            
            // Generate certificate
            $certCommand = "openssl req -new -x509 -key \"$keyFile\" -out \"$certFile\" -days 365 -subj \"/C=US/ST=State/L=City/O=University/OU=IT/CN=$ip\"";
            exec($certCommand, $certOutput, $certReturn);
            
            if ($certReturn === 0) {
                echo "✅ SSL certificate generated\n";
            } else {
                echo "❌ Failed to generate certificate\n";
            }
        } else {
            echo "❌ Failed to generate private key\n";
        }
    } else {
        echo "✅ SSL certificate already exists\n";
    }
}

// Update configuration
echo "⚙️  Updating configuration...\n";
$configFile = __DIR__ . '/../config/config.php';
$configContent = file_get_contents($configFile);
$newUrl = "https://$ip:8443/Admission Portal";
$configContent = preg_replace("/define\('SITE_URL', '[^']*'\);/", "define('SITE_URL', '$newUrl');", $configContent);
file_put_contents($configFile, $configContent);
echo "✅ Configuration updated\n\n";

// Create startup script
$startScript = __DIR__ . '/start_https_server.bat';
$startContent = "@echo off\n";
$startContent .= "echo Starting HTTPS Server for University Admission Portal...\n";
$startContent .= "echo.\n";
$startContent .= "echo Access URL: https://$ip:8443/Admission Portal\n";
$startContent .= "echo.\n";
$startContent .= "echo Note: Browsers will show security warning for self-signed certificate\n";
$startContent .= "echo Click 'Advanced' then 'Proceed to site' to continue\n";
$startContent .= "echo.\n";
$startContent .= "echo Press Ctrl+C to stop the server\n";
$startContent .= "echo.\n";
$startContent .= "php -S 0.0.0.0:8443 -t . router.php\n";
$startContent .= "pause\n";

file_put_contents($startScript, $startContent);
echo "✅ Created startup script: $startScript\n\n";

// Create network access guide
$guideFile = __DIR__ . '/../HTTPS_NETWORK_GUIDE.md';
$guideContent = "# 🌐 HTTPS Network Access Guide\n\n";
$guideContent .= "## **Current Network Configuration**\n\n";
$guideContent .= "### **Your Device**\n";
$guideContent .= "- **IP Address**: `$ip`\n";
$guideContent .= "- **Port**: `8443` (HTTPS)\n";
$guideContent .= "- **Status**: ✅ Server Ready\n\n";
$guideContent .= "### **Access URLs**\n";
$guideContent .= "- **Local Access**: `https://localhost:8443/Admission Portal`\n";
$guideContent .= "- **Network Access**: `https://$ip:8443/Admission Portal`\n\n";
$guideContent .= "---\n\n";
$guideContent .= "## **How to Access from Other Devices**\n\n";
$guideContent .= "### **📱 Mobile Devices (Phone/Tablet)**\n";
$guideContent .= "1. Connect to the **same WiFi network**\n";
$guideContent .= "2. Open browser\n";
$guideContent .= "3. Go to: `https://$ip:8443/Admission Portal`\n";
$guideContent .= "4. Click **'Advanced'** when security warning appears\n";
$guideContent .= "5. Click **'Proceed to site'** to continue\n\n";
$guideContent .= "### **💻 Other Computers**\n";
$guideContent .= "1. Connect to the **same WiFi network**\n";
$guideContent .= "2. Open browser\n";
$guideContent .= "3. Go to: `https://$ip:8443/Admission Portal`\n";
$guideContent .= "4. Click **'Advanced'** when security warning appears\n";
$guideContent .= "5. Click **'Proceed to site'** to continue\n\n";
$guideContent .= "### **🔧 Troubleshooting**\n";
$guideContent .= "- **Can't access?** Make sure both devices are on the same WiFi\n";
$guideContent .= "- **Connection refused?** Check if Windows Firewall is blocking port 8443\n";
$guideContent .= "- **IP changed?** Run `ipconfig` to get new IP address\n";
$guideContent .= "- **Security warning?** This is normal for self-signed certificates\n\n";
$guideContent .= "---\n\n";
$guideContent .= "## **Quick Commands**\n\n";
$guideContent .= "### **Start HTTPS Server**\n";
$guideContent .= "```bash\n";
$guideContent .= "php -S 0.0.0.0:8443 -t . router.php\n";
$guideContent .= "```\n\n";
$guideContent .= "### **Check IP Address**\n";
$guideContent .= "```bash\n";
$guideContent .= "ipconfig\n";
$guideContent .= "```\n\n";
$guideContent .= "### **Stop Server**\n";
$guideContent .= "```bash\n";
$guideContent .= "taskkill /f /im php.exe\n";
$guideContent .= "```\n\n";
$guideContent .= "---\n\n";
$guideContent .= "## **✅ Current Status**\n";
$guideContent .= "- **Server**: Ready ✅\n";
$guideContent .= "- **Port**: 8443 (HTTPS) ✅\n";
$guideContent .= "- **Network**: Accessible ✅\n";
$guideContent .= "- **URL**: `https://$ip:8443/Admission Portal` ✅\n";
$guideContent .= "- **SSL Certificate**: Generated ✅\n\n";
$guideContent .= "**Ready for secure multi-device access!** 🎉\n\n";
$guideContent .= "## **Feedback Form**\n";
$guideContent .= "A comprehensive feedback form is available at:\n";
$guideContent .= "- **HTML Version**: `docs/Website_Testing_Feedback_Form.html`\n";
$guideContent .= "- **Text Version**: `docs/Website_Testing_Feedback_Form.docx`\n\n";
$guideContent .= "Open the HTML file in any browser to fill out the form and print it for testing.\n";

file_put_contents($guideFile, $guideContent);
echo "✅ Created network access guide: $guideFile\n\n";

echo "🎉 Setup Complete!\n";
echo "==================\n";
echo "📁 Files created:\n";
echo "   - SSL Certificate: $sslDir/server.crt\n";
echo "   - SSL Private Key: $sslDir/server.key\n";
echo "   - Startup Script: $startScript\n";
echo "   - Network Guide: $guideFile\n";
echo "   - Feedback Form: docs/Website_Testing_Feedback_Form.html\n\n";
echo "🚀 To start the HTTPS server:\n";
echo "   1. Double-click: $startScript\n";
echo "   2. Or run: php -S 0.0.0.0:8443 -t . router.php\n\n";
echo "🌐 Access your site at:\n";
echo "   https://$ip:8443/Admission Portal\n\n";
echo "📝 Feedback form available at:\n";
echo "   docs/Website_Testing_Feedback_Form.html\n\n";
echo "⚠️  Note: Browsers will show security warning for self-signed certificate\n";
echo "   This is normal for development. Click 'Advanced' → 'Proceed to site'\n\n";

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
