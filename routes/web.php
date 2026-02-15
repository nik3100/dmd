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

// Admin category routes (admin only)
$router->get('/admin/categories', 'App\Controllers\CategoryController', 'index', 'App\Middleware\AdminMiddleware');
$router->get('/admin/categories/create', 'App\Controllers\CategoryController', 'create', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/store', 'App\Controllers\CategoryController', 'store', 'App\Middleware\AdminMiddleware');
$router->get('/admin/categories/edit/{id}', 'App\Controllers\CategoryController', 'edit', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/update/{id}', 'App\Controllers\CategoryController', 'update', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/delete/{id}', 'App\Controllers\CategoryController', 'delete', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/toggle-active/{id}', 'App\Controllers\CategoryController', 'toggleActive', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/suggestions/approve/{id}', 'App\Controllers\CategoryController', 'approveSuggestion', 'App\Middleware\AdminMiddleware');
$router->post('/admin/categories/suggestions/reject/{id}', 'App\Controllers\CategoryController', 'rejectSuggestion', 'App\Middleware\AdminMiddleware');

// Public API routes
$router->get('/api/categories/tree', 'App\Controllers\CategoryController', 'tree');
