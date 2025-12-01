<?php

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(function (string $class): void {
            $class = ltrim($class, '\\');

            $paths = [
                __DIR__ . DIRECTORY_SEPARATOR . $class . '.php',
                __DIR__ . '/../controllers/' . $class . '.php',
                __DIR__ . '/../models/' . $class . '.php',
                __DIR__ . '/../services/' . $class . '.php',
                __DIR__ . '/../helpers/' . $class . '.php',
                __DIR__ . '/../widgets/' . $class . '.php',
                __DIR__ . '/../repositories/' . $class . '.php',
                __DIR__ . '/../includes/' . $class . '.php',
            ];

            foreach ($paths as $path) {
                if (is_readable($path)) {
                    require_once $path;
                    return;
                }
            }
        });
    }
}
