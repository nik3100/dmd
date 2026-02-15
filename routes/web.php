<?php

declare(strict_types=1);

/**
 * Web routes - use $router (Router instance) to register routes.
 *
 * Examples:
 *   $router->get('/', 'App\Controllers\HomeController', 'index');
 *   $router->get('/about', 'App\Controllers\HomeController', 'about');
 *   $router->get('/item/{id}', 'App\Controllers\ItemController', 'show');
 *   $router->get('/dashboard', 'App\Controllers\DashboardController', 'index', 'auth');
 */

// Public routes
$router->get('/', 'App\Controllers\HomeController', 'index');

// Authentication routes (guest only)
$router->get('/login', 'App\Controllers\AuthController', 'showLogin', 'guest');
$router->post('/login', 'App\Controllers\AuthController', 'login', 'guest');
$router->get('/register', 'App\Controllers\AuthController', 'showRegister', 'guest');
$router->post('/register', 'App\Controllers\AuthController', 'register', 'guest');
$router->get('/logout', 'App\Controllers\AuthController', 'logout');

// Protected routes (require authentication)
$router->get('/dashboard', 'App\Controllers\DashboardController', 'index', 'auth');
