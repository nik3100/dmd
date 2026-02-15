<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Central error and exception handling.
 * In production, set APP_DEBUG to false and log to storage/logs.
 */
class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(\Throwable $e): void
    {
        $logPath = ROOT_PATH . '/logs/error.log';
        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = date('Y-m-d H:i:s') . ' | ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);

        $debug = defined('APP_DEBUG') && APP_DEBUG;

        if ($debug) {
            echo '<pre>';
            echo htmlspecialchars((string) $e);
            echo '</pre>';
        } else {
            http_response_code(500);
            echo 'An error occurred. Please try again later.';
        }
    }
}
