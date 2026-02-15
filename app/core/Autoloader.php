<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 style autoloader for App namespace.
 * Maps App\* to app/* directory structure.
 */
class Autoloader
{
    private static string $basePath;

    public static function register(): void
    {
        self::$basePath = ROOT_PATH . '/app/';
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        // Only handle App namespace
        if (strpos($class, 'App\\') !== 0) {
            return;
        }

        $relativePath = str_replace('App\\', '', $class);
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
        $file = self::$basePath . $relativePath . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
}
