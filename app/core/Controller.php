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
}
