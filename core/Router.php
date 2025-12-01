<?php

class Router
{
    public function dispatch(string $controllerName, string $action): void
    {
        require_once ROOT_PATH . '/core/Controller.php';

        $file = ROOT_PATH . '/controllers/' . $controllerName . '.php';
        if (!file_exists($file)) {
            http_response_code(500);
            echo 'Controller file not found: ' . htmlspecialchars($controllerName, ENT_QUOTES, 'UTF-8');
            return;
        }

        require_once $file;

        if (!class_exists($controllerName)) {
            http_response_code(500);
            echo 'Controller class not found: ' . htmlspecialchars($controllerName, ENT_QUOTES, 'UTF-8');
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo 'Action not found: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
            return;
        }

        $controller->$action();
    }
}
