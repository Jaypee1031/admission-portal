<?php
/**
 * SSL Certificate Generator for Development
 * Creates self-signed SSL certificates for HTTPS testing
 */

echo "🔐 Generating SSL Certificate for HTTPS Development\n";
echo "================================================\n\n";

$ip = getLocalIP();
echo "📡 Using IP Address: $ip\n\n";

// Create certificate directory
$certDir = __DIR__ . '/../ssl';
if (!is_dir($certDir)) {
    mkdir($certDir, 0755, true);
    echo "📁 Created SSL directory: $certDir\n";
}

// Generate private key
$keyFile = $certDir . '/server.key';
$certFile = $certDir . '/server.crt';

echo "🔑 Generating private key...\n";
$keyCommand = "openssl genrsa -out \"$keyFile\" 2048";
exec($keyCommand, $keyOutput, $keyReturn);

if ($keyReturn !== 0) {
    echo "❌ Error generating private key. Make sure OpenSSL is installed.\n";
    echo "💡 Install OpenSSL or use Git Bash which includes OpenSSL\n";
    exit(1);
}

echo "✅ Private key generated successfully\n";

// Generate certificate
echo "📜 Generating SSL certificate...\n";
$certCommand = "openssl req -new -x509 -key \"$keyFile\" -out \"$certFile\" -days 365 -subj \"/C=US/ST=State/L=City/O=University/OU=IT/CN=$ip\"";
exec($certCommand, $certOutput, $certReturn);

if ($certReturn !== 0) {
    echo "❌ Error generating certificate\n";
    exit(1);
}

echo "✅ SSL certificate generated successfully\n\n";

echo "🎉 SSL Setup Complete!\n";
echo "=====================\n";
echo "📁 Certificate files:\n";
echo "   Key: $keyFile\n";
echo "   Cert: $certFile\n\n";

echo "🚀 To start HTTPS server, run:\n";
echo "   php scripts/start_https_server.php\n\n";

echo "🌐 Access your site at:\n";
echo "   https://$ip:8443/Admission Portal\n\n";

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
