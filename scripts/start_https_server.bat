@echo off
echo Starting HTTPS Server for University Admission Portal...
echo.
echo Access URL: https://192.168.106.142:8443/Admission Portal
echo.
echo Note: Browsers will show security warning for self-signed certificate
echo Click 'Advanced' then 'Proceed to site' to continue
echo.
echo Press Ctrl+C to stop the server
echo.
php -S 0.0.0.0:8443 -t . router.php
pause
