<?php

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $path = ROOT_PATH . '/views/' . $view . '.php';

        if (!file_exists($path)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        require $path;
    }
}
