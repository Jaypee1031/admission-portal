<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('PHP_FILE_EXTENSION')) {
    define('PHP_FILE_EXTENSION', '.php');
}

function includeFile($name)
{
    $trimmed = trim($name, " \t\n\r\0\x0B/");

    if ($trimmed === '') {
        throw new InvalidArgumentException('includeFile() requires a non-empty file name');
    }

    if (stripos($trimmed, '.php') !== false) {
        throw new InvalidArgumentException('Do not provide file extensions to includeFile()');
    }

    $relative = $trimmed . PHP_FILE_EXTENSION;

    $fullPath = rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        $safeName = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
        echo "Error: Page '{$safeName}' not found.";
        return false;
    }

    include_once $fullPath;

    return true;
}
