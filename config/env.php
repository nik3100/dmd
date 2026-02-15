<?php

declare(strict_types=1);

/**
 * Environment configuration.
 * Loads .env from project root if present (optional), then defines constants.
 */

$envFile = ROOT_PATH . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}

// Defaults if not set by .env
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
if (!defined('APP_ENV')) {
    define('APP_ENV', 'local');
}
