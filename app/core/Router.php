<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple router - matches request URL to controller action.
 * Routes are defined in /routes/web.php (included by dispatch).
 */
class Router
{
    private array $routes = [];
    private string $notFoundController = 'App\Controllers\HomeController';
    private string $notFoundAction = 'notFound';

    public function __construct()
    {
        $routesFile = ROOT_PATH . '/routes/web.php';
        if (is_file($routesFile)) {
            $router = $this;
            (static function () use ($router, $routesFile): void {
                require $routesFile;
            })();
        }
    }

    /**
     * Register a GET route.
     *
     * @param callable|string|array<int, string>|null $middleware
     */
    public function get(string $path, string $controller, string $action = 'index', $middleware = null): self
    {
        $this->addRoute('GET', $path, $controller, $action, $middleware);
        return $this;
    }

    /**
     * Register a POST route.
     *
     * @param callable|string|array<int, string>|null $middleware
     */
    public function post(string $path, string $controller, string $action = 'index', $middleware = null): self
    {
        $this->addRoute('POST', $path, $controller, $action, $middleware);
        return $this;
    }

    private function addRoute(string $method, string $path, string $controller, string $action, $middleware = null): void
    {
        $pattern = $this->pathToRegex($path);
        $this->routes[] = [
            'method'   => $method,
            'pattern'  => $pattern,
            'controller' => $controller,
            'action'   => $action,
            'paramNames' => $this->getParamNames($path),
            'middleware' => $middleware,
        ];
    }

    private function pathToRegex(string $path): string
{
    if ($path === '/') {
        return '#^/$#';
    }

    $path = trim($path, '/');
    $path = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);

    return '#^/' . $path . '$#';
}

    private function getParamNames(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Get the request URL path (from front controller).
     */
    private function getRequestUrl(): string
    {
        $url = $_GET['url'] ?? '/';
        $url = '/' . trim((string) $url, '/');
        $url = $url === '' ? '/' : $url;
        return $url;
    }

    /**
     * Dispatch the request to the matched controller action.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $url = $this->getRequestUrl();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $url, $matches)) {
                array_shift($matches); // full match
                $params = array_combine($route['paramNames'], $matches) ?: [];
                
                // Execute middleware if present
                if (isset($route['middleware']) && $route['middleware'] !== null) {
                    $this->executeMiddleware($route['middleware']);
                }
                
                $this->invoke($route['controller'], $route['action'], $params);
                return;
            }
        }

        $this->invoke($this->notFoundController, $this->notFoundAction, []);
    }

    /**
     * Execute middleware.
     *
     * @param callable|string|array<int, string> $middleware
     */
    private function executeMiddleware($middleware): void
    {
        if (is_callable($middleware)) {
            $middleware();
            return;
        }
        
        if (is_string($middleware)) {
            $this->executeMiddlewareString($middleware);
            return;
        }
        
        if (is_array($middleware)) {
            foreach ($middleware as $mw) {
                $this->executeMiddleware($mw);
            }
        }
    }

    /**
     * Execute middleware by string name.
     */
    private function executeMiddlewareString(string $middleware): void
    {
        switch ($middleware) {
            case 'auth':
                \App\Helpers\Auth::requireAuth();
                break;
            case 'guest':
                if (\App\Helpers\Auth::check()) {
                    header('Location: /dashboard');
                    exit;
                }
                break;
            default:
                // Support custom middleware: 'App\Middleware\RoleMiddleware'
                if (class_exists($middleware) && method_exists($middleware, 'handle')) {
                    $middleware::handle();
                }
                break;
        }
    }

    private function invoke(string $controllerClass, string $action, array $params): void
    {
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller not found: {$controllerClass}");
        }
        $controller = new $controllerClass();
        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Action not found: {$controllerClass}::{$action}");
        }
        call_user_func_array([$controller, $action], $params);
    }

    /**
     * Set controller/action for 404.
     */
    public function setNotFound(string $controller, string $action = 'notFound'): self
    {
        $this->notFoundController = $controller;
        $this->notFoundAction = $action;
        return $this;
    }
}
