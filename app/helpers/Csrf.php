<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * CSRF protection - generate and validate tokens.
 */
class Csrf
{
    private const SESSION_KEY = 'csrf_token';
    private const TOKEN_LENGTH = 32;

    /**
     * Start session if not started.
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generate and store CSRF token.
     */
    public static function token(): string
    {
        self::ensureSession();
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Get CSRF token field name.
     */
    public static function fieldName(): string
    {
        return '_token';
    }

    /**
     * Validate CSRF token.
     */
    public static function validate(?string $token): bool
    {
        self::ensureSession();
        
        if ($token === null || !isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Regenerate CSRF token (after use for security).
     */
    public static function regenerate(): void
    {
        self::ensureSession();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
}
