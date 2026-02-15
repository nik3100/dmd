<?php

declare(strict_types=1);

/**
 * Front Controller - All requests are routed through this file.
 * The .htaccess rewrites non-file requests to index.php?url=<path>
 */

// Define application root (one level up from public)
define('ROOT_PATH', dirname(__DIR__));

// Bootstrap the application
require ROOT_PATH . '/bootstrap.php';

// Run the router
$router = new App\Core\Router();
$router->dispatch();
