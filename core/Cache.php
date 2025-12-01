<?php

class Cache
{
    private static function isEnabled(): bool
    {
        return defined('ENABLE_CACHE') && ENABLE_CACHE;
    }

    private static function cacheDir(): string
    {
        if (defined('CACHE_DIR')) {
            $dir = CACHE_DIR;
        } else {
            $dir = dirname(__DIR__) . '/cache';
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private static function keyToPath(string $key): string
    {
        $filename = sha1($key) . '.cache';
        return rtrim(self::cacheDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    public static function get(string $key)
    {
        if (!self::isEnabled()) {
            return null;
        }

        $path = self::keyToPath($key);
        if (!is_file($path)) {
            return null;
        }

        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $payload = @unserialize($data);
        if (!is_array($payload) || !isset($payload['expires_at']) || !array_key_exists('value', $payload)) {
            return null;
        }

        if ($payload['expires_at'] < time()) {
            @unlink($path);
            return null;
        }

        return $payload['value'];
    }

    public static function set(string $key, $value, int $ttl): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $path = self::keyToPath($key);
        $payload = [
            'expires_at' => time() + max(1, $ttl),
            'value'      => $value,
        ];

        @file_put_contents($path, serialize($payload), LOCK_EX);
    }

    public static function delete(string $key): void
    {
        $path = self::keyToPath($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function flush(): void
    {
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
