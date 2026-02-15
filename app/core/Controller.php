<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller - common behavior for all controllers.
 * Handles view rendering and shared data.
 */
abstract class Controller
{
    /**
     * Render a view from app/views/ with optional data.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $viewName, array $data = []): void
    {
        $viewPath = ROOT_PATH . '/app/views/' . str_replace('.', DIRECTORY_SEPARATOR, $viewName) . '.php';
        if (!is_file($viewPath)) {
            throw new \RuntimeException("View not found: {$viewName}");
        }
        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    /**
     * Send JSON response.
     *
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Redirect to a URL.
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Validate and sanitize redirect URL to prevent open redirect attacks.
     * Only allows relative paths (starting with /) - rejects external URLs.
     *
     * @param string|null $url The redirect URL to validate
     * @param string $default Default URL if validation fails
     * @return string Safe redirect URL
     */
    protected function validateRedirect(?string $url, string $default = '/'): string
    {
        if (empty($url)) {
            return $default;
        }

        // Remove whitespace
        $url = trim($url);

        // Reject empty strings after trim
        if ($url === '') {
            return $default;
        }

        // Only allow relative paths (starting with /)
        // This prevents: http://, https://, //evil.com, javascript:, etc.
        if (strpos($url, '/') !== 0) {
            return $default;
        }

        // Parse URL to check for malicious components
        $parsed = parse_url($url);
        
        // If parse_url fails, reject
        if ($parsed === false) {
            return $default;
        }
        
        // Reject if contains scheme (http://, https://, javascript:, etc.)
        if (isset($parsed['scheme'])) {
            return $default;
        }
        
        // Reject if contains host/domain (prevents //evil.com)
        if (isset($parsed['host'])) {
            return $default;
        }
        
        // Reject if contains user/pass (prevents //user@evil.com)
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return $default;
        }
        
        // Build safe URL from path only
        $safeUrl = $parsed['path'] ?? '/';
        
        // Ensure path starts with / (should already be true, but double-check)
        if (strpos($safeUrl, '/') !== 0) {
            return $default;
        }
        
        // Allow query string (for preserving search params, etc.)
        if (isset($parsed['query'])) {
            $safeUrl .= '?' . $parsed['query'];
        }
        
        // Note: We intentionally ignore fragments (#) for security
        
        return $safeUrl;
    }
}
