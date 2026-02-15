# Authentication System Documentation

## Overview

Secure authentication system built with core PHP MVC, featuring:
- User registration and login
- Password hashing (bcrypt via `password_hash`)
- Session management with security best practices
- Role-based access control (Admin, BusinessOwner, User)
- CSRF protection
- Input sanitization
- SQL injection prevention (PDO prepared statements)
- XSS prevention
- Session fixation prevention

---

## Setup

### 1. Import Database Schema

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seeds/roles.sql
```

### 2. Update Database Config

Ensure `.env` or `config/database.php` has correct credentials:
```php
DB_NAME=digitalmarketing_display
DB_USER=root
DB_PASS=
```

### 3. Test Routes

- **Register:** `http://localhost/dmd/public/register`
- **Login:** `http://localhost/dmd/public/login`
- **Dashboard (protected):** `http://localhost/dmd/public/dashboard`
- **Logout:** `http://localhost/dmd/public/logout`

---

## Architecture

### Components

| Component | File | Purpose |
|-----------|------|---------|
| **User Model** | `app/models/User.php` | Database queries for users |
| **Auth Helper** | `app/helpers/Auth.php` | Session management, authentication checks |
| **CSRF Helper** | `app/helpers/Csrf.php` | CSRF token generation/validation |
| **Sanitize Helper** | `app/helpers/Sanitize.php` | Input sanitization (XSS prevention) |
| **AuthController** | `app/controllers/AuthController.php` | Register, login, logout actions |
| **Router Middleware** | `app/core/Router.php` | Route-level middleware support |

---

## Usage

### Authentication Check

```php
use App\Helpers\Auth;

// Check if user is logged in
if (Auth::check()) {
    // User is authenticated
}

// Get current user ID
$userId = Auth::id();

// Get current user data
$user = Auth::user();
// Returns: ['id', 'email', 'name', 'slug']

// Get user roles
$roles = Auth::roles();
// Returns: ['admin', 'user', ...]
```

### Role Checking

```php
use App\Helpers\Auth;

// Check if user has specific role
if (Auth::hasRole('admin')) {
    // User is admin
}

// Check if user has any of the roles
if (Auth::hasAnyRole(['admin', 'business_owner'])) {
    // User is admin or business owner
}

// Require authentication (redirects if not logged in)
Auth::requireAuth();

// Require specific role (403 if not authorized)
Auth::requireRole('admin');

// Require any of the roles
Auth::requireAnyRole(['admin', 'business_owner']);
```

### CSRF Protection

**In Forms:**
```php
<?php use App\Helpers\Csrf; ?>

<form method="POST">
    <input type="hidden" name="<?= Csrf::fieldName() ?>" value="<?= Csrf::token() ?>">
    <!-- form fields -->
</form>
```

**In Controller:**
```php
use App\Helpers\Csrf;

$token = $_POST[Csrf::fieldName()] ?? null;
if (!Csrf::validate($token)) {
    // Invalid token - show error
    return;
}
// Process form
Csrf::regenerate(); // Regenerate after successful submission
```

### Input Sanitization

```php
use App\Helpers\Sanitize;

// Sanitize string (strip tags, trim)
$name = Sanitize::string($_POST['name']);

// Sanitize email
$email = Sanitize::email($_POST['email']);

// Sanitize URL
$url = Sanitize::url($_POST['website']);

// Sanitize integer
$id = Sanitize::int($_GET['id']);

// Escape HTML output (for views)
echo Sanitize::html($userInput);
```

---

## Routes & Middleware

### Route Registration

```php
// Public route
$router->get('/', 'App\Controllers\HomeController', 'index');

// Protected route (requires authentication)
$router->get('/dashboard', 'App\Controllers\DashboardController', 'index', 'auth');

// Guest-only route (redirects if logged in)
$router->get('/login', 'App\Controllers\AuthController', 'showLogin', 'guest');
$router->post('/login', 'App\Controllers\AuthController', 'login', 'guest');
```

### Middleware Types

1. **`'auth'`** - Requires authentication (redirects to `/login` if not logged in)
2. **`'guest'`** - Requires guest (redirects to `/dashboard` if logged in)
3. **Custom middleware class** - `'App\Middleware\RoleMiddleware'` (must have `handle()` method)
4. **Callable** - Anonymous function or array of callables

---

## Security Features

### 1. Password Hashing
- Uses PHP's `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- Passwords verified with `password_verify()`
- Minimum 8 characters required

### 2. Session Security
- **HttpOnly cookies:** Prevents JavaScript access
- **Secure cookies:** Enabled in HTTPS environments
- **SameSite=Strict:** CSRF protection
- **Session regeneration:** On login and session start (prevents fixation)

### 3. SQL Injection Prevention
- All queries use PDO prepared statements
- No direct string concatenation in SQL

### 4. XSS Prevention
- Input sanitization via `Sanitize` helper
- Output escaping via `htmlspecialchars()` in views
- `Sanitize::html()` for safe HTML output

### 5. CSRF Protection
- Token generated per session
- Token validated on POST requests
- Token regenerated after successful submission

### 6. Input Validation
- Email format validation
- Password strength requirements
- Required field checks
- Unique email/slug validation

---

## Database Schema

### Users Table
- `id` - Primary key
- `email` - Unique, indexed
- `password_hash` - Bcrypt hash
- `name` - User's full name
- `slug` - URL-friendly identifier
- `is_active` - Account status
- `deleted_at` - Soft delete

### Roles Table
- `id` - Primary key
- `name` - Role name (Admin, Business Owner, User)
- `slug` - URL-friendly identifier

### User Roles Table
- `user_id` - Foreign key to users
- `role_id` - Foreign key to roles
- Composite primary key

---

## Example: Protected Controller

```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;

class MyController extends Controller
{
    public function index()
    {
        // Middleware handles auth check, but you can also check manually
        $user = Auth::user();
        
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            die('Access denied');
        }
        
        $this->view('my.view', ['user' => $user]);
    }
}
```

**Route:**
```php
$router->get('/admin', 'App\Controllers\MyController', 'index', 'auth');
```

---

## Example: Role-Based Middleware

Create custom middleware:

```php
<?php
namespace App\Middleware;

use App\Helpers\Auth;

class AdminMiddleware
{
    public static function handle(): void
    {
        Auth::requireAuth();
        Auth::requireRole('admin');
    }
}
```

**Usage:**
```php
$router->get('/admin', 'App\Controllers\AdminController', 'index', 'App\Middleware\AdminMiddleware');
```

---

## Troubleshooting

### Session Not Working
- Check `php.ini` session settings
- Ensure `storage/logs` directory is writable
- Check browser cookies are enabled

### CSRF Token Mismatch
- Ensure form includes hidden `_token` field
- Check session is started before generating token
- Verify token is regenerated after use

### Password Not Working
- Ensure database column is `password_hash` (not `password`)
- Verify `password_hash()` is available (PHP 5.5+)
- Check password is hashed before saving

### Role Not Assigned
- Run `database/seeds/roles.sql` to create default roles
- Check `user_roles` table has correct entries
- Verify role slug matches exactly (case-sensitive)

---

## Best Practices

1. **Always use middleware** for protected routes
2. **Sanitize all user input** before database operations
3. **Escape output** in views using `htmlspecialchars()` or `Sanitize::html()`
4. **Validate CSRF tokens** on all POST requests
5. **Regenerate CSRF token** after successful form submission
6. **Use prepared statements** for all database queries
7. **Check roles** before sensitive operations
8. **Log authentication events** for security auditing

---

## Next Steps

- Add password reset functionality
- Implement email verification
- Add two-factor authentication (2FA)
- Create admin panel for user management
- Add rate limiting for login attempts
- Implement remember me functionality
