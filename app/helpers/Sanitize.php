<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Input sanitization helper - prevent XSS and clean user input.
 */
class Sanitize
{
    /**
     * Sanitize string input (strip tags, trim).
     */
    public static function string(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(strip_tags($value));
    }

    /**
     * Sanitize email.
     */
    public static function email(?string $value): string
    {
        $email = self::string($value);
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL.
     */
    public static function url(?string $value): string
    {
        $url = self::string($value);
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize integer.
     */
    public static function int(?string $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Escape HTML output (for views).
     */
    public static function html(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize textarea (allow some HTML if needed, or strip all).
     */
    public static function text(?string $value, bool $allowHtml = false): string
    {
        if ($value === null) {
            return '';
        }
        if ($allowHtml) {
            return trim($value);
        }
        return trim(strip_tags($value));
    }
}
