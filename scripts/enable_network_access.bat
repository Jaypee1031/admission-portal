@echo off
echo Adding Windows Firewall Rules for Network Access...
echo.

REM Add firewall rule for HTTP (port 8000)
netsh advfirewall firewall add rule name="PHP HTTP Server" dir=in action=allow protocol=TCP localport=8000
if %errorlevel% equ 0 (
    echo ✅ HTTP rule added successfully (port 8000)
) else (
    echo ❌ Failed to add HTTP rule
)

REM Add firewall rule for HTTPS (port 8443)
netsh advfirewall firewall add rule name="PHP HTTPS Server" dir=in action=allow protocol=TCP localport=8443
if %errorlevel% equ 0 (
    echo ✅ HTTPS rule added successfully (port 8443)
) else (
    echo ❌ Failed to add HTTPS rule
)

echo.
echo 🚀 Starting HTTP server for network access...
echo 📱 Other devices can access: http://192.168.106.142:8000
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start the server
php -S 0.0.0.0:8000





