<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;

/**
 * Dashboard controller - protected route example.
 * Requires authentication.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $roles = Auth::roles();

        $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'user' => $user,
            'roles' => $roles,
        ]);
    }
}
