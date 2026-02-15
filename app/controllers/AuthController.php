<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitize;
use App\Models\User;

/**
 * Authentication controller - handles register, login, logout.
 */
class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth.login', [
            'title' => 'Login',
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Process login.
     */
    public function login(): void
    {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        // Validate CSRF token
        $token = $_POST[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->view('auth.login', [
                'title' => 'Login',
                'error' => 'Invalid security token. Please try again.',
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Sanitize input
        $email = Sanitize::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (!empty($errors)) {
            $this->view('auth.login', [
                'title' => 'Login',
                'errors' => $errors,
                'email' => $email,
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Find user
        $user = User::findByEmail($email);
        if (!$user || !$user['is_active']) {
            $this->view('auth.login', [
                'title' => 'Login',
                'error' => 'Invalid email or password.',
                'email' => $email,
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->view('auth.login', [
                'title' => 'Login',
                'error' => 'Invalid email or password.',
                'email' => $email,
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Login successful
        Auth::login($user);
        Csrf::regenerate(); // Regenerate CSRF token after successful login

        // Redirect to intended page or dashboard
        $redirect = $_GET['redirect'] ?? '/dashboard';
        $this->redirect($redirect);
    }

    /**
     * Show register form.
     */
    public function showRegister(): void
    {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth.register', [
            'title' => 'Register',
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * Process registration.
     */
    public function register(): void
    {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }

        // Validate CSRF token
        $token = $_POST[Csrf::fieldName()] ?? null;
        if (!Csrf::validate($token)) {
            $this->view('auth.register', [
                'title' => 'Register',
                'error' => 'Invalid security token. Please try again.',
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Sanitize input
        $name = Sanitize::string($_POST['name'] ?? '');
        $email = Sanitize::email($_POST['email'] ?? '');
        $phone = Sanitize::string($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validate input
        $errors = [];
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if (User::emailExists($email)) {
            $errors[] = 'Email already registered.';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Generate slug from name
        $slug = self::generateSlug($name);
        if (User::slugExists($slug)) {
            $slug = $slug . '-' . time(); // Make unique
        }

        if (!empty($errors)) {
            $this->view('auth.register', [
                'title' => 'Register',
                'errors' => $errors,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'csrf_token' => Csrf::token(),
            ]);
            return;
        }

        // Create user
        try {
            $userId = User::create([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name' => $name,
                'slug' => $slug,
                'phone' => $phone ?: null,
            ]);

            // Assign default role (User)
            $roleId = self::getRoleId('user');
            if ($roleId) {
                User::assignRole($userId, $roleId);
            }

            // Auto-login after registration
            $user = User::find($userId);
            if ($user) {
                Auth::login($user);
                Csrf::regenerate();
            }

            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            $this->view('auth.register', [
                'title' => 'Register',
                'error' => 'Registration failed. Please try again.',
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'csrf_token' => Csrf::token(),
            ]);
        }
    }

    /**
     * Logout user.
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }

    /**
     * Generate URL-friendly slug from string.
     */
    private static function generateSlug(string $string): string
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }

    /**
     * Get role ID by slug.
     */
    private static function getRoleId(string $slug): ?int
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? (int) $result['id'] : null;
    }
}
