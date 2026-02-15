<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;

/**
 * Admin middleware - requires admin role.
 */
class AdminMiddleware
{
    public static function handle(): void
    {
        Auth::requireAuth();
        Auth::requireRole('admin');
    }
}
