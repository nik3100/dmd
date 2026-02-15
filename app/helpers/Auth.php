<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;

/**
 * Authentication helper - session management, user check, role checking.
 */
class Auth
{
    private const SESSION_KEY = 'user_id';
    private const SESSION_ROLES_KEY = 'user_roles';

    /**
     * Start session with security settings.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Session security settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_secure', self::isHttps() ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID to prevent session fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    /**
     * Check if HTTPS is enabled.
     */
    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || ($_SERVER['SERVER_PORT'] ?? 0) == 443;
    }

    /**
     * Login user (set session).
     *
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        self::startSession();
        
        // Regenerate session ID on login (prevent session fixation)
        session_regenerate_id(true);
        
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        
        // Load and cache user roles
        $roles = User::getRoles((int) $user['id']);
        $_SESSION[self::SESSION_ROLES_KEY] = array_column($roles, 'slug');
        
        $_SESSION['user_data'] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'slug' => $user['slug'],
        ];
    }

    /**
     * Logout user (destroy session).
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Check if user is authenticated.
     */
    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Get current user ID.
     */
    public static function id(): ?int
    {
        self::startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get current user data from session.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Get current user's roles.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        self::startSession();
        return $_SESSION[self::SESSION_ROLES_KEY] ?? [];
    }

    /**
     * Check if user has a specific role.
     */
    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array<int, string> $roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        $userRoles = self::roles();
        return !empty(array_intersect($roles, $userRoles));
    }

    /**
     * Require authentication (redirect if not logged in).
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require specific role (redirect if not authorized).
     */
    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Require any of the given roles.
     *
     * @param array<int, string> $roles
     */
    public static function requireAnyRole(array $roles): void
    {
        self::requireAuth();
        if (!self::hasAnyRole($roles)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }
}
