<?php

require_once ROOT_PATH . '/core/View.php';

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }
}
