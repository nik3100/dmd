<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Default controller - index and 404 placeholder.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home.index', ['title' => 'Digital Marketing Display']);
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->view('home.404', ['title' => 'Page Not Found']);
    }
}
