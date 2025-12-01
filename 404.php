<?php
require_once __DIR__ . '/config/config.php';

http_response_code(404);
$route = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
$route = trim($route, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo SITE_NAME; ?></title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>
        <?php
        if ($route !== '') {
            echo "Error: Page '" . htmlspecialchars($route, ENT_QUOTES, 'UTF-8') . "' not found.";
        } else {
            echo "Error: Page not found.";
        }
        ?>
    </p>
    <p><a href="<?php echo buildUrl('/'); ?>">Return to home page</a></p>
</body>
</html>
