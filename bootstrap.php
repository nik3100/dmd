<?php

declare(strict_types=1);

/**
 * Application bootstrap - loads config, autoloader, and error handling.
 */

// Load autoloader first
require ROOT_PATH . '/app/core/Autoloader.php';
App\Core\Autoloader::register();

// Load environment config (optional .env support)
require ROOT_PATH . '/config/env.php';

// Start session for authentication
\App\Helpers\Auth::startSession();

// Initialize error handling
App\Core\ErrorHandler::register();
