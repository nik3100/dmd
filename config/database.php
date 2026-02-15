<?php

declare(strict_types=1);

/**
 * Database configuration.
 * Uses constants from config/env.php (set by .env or defaults).
 */

return [
    'host'     => defined('DB_HOST') ? DB_HOST : 'localhost',
    'port'     => defined('DB_PORT') ? DB_PORT : '3306',
    'database' => defined('DB_NAME') ? DB_NAME : 'dmd',
    'username' => defined('DB_USER') ? DB_USER : 'root',
    'password' => defined('DB_PASS') ? DB_PASS : '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
