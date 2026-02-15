# Quick Start: Authentication System

## 1. Setup Database

```bash
# Import schema
mysql -u root -p < database/schema.sql

# Seed default roles
mysql -u root -p < database/seeds/roles.sql
```

## 2. Test Authentication

1. **Register:** Visit `http://localhost/dmd/public/register`
   - Fill in name, email, password
   - Account is created and auto-logged in

2. **Login:** Visit `http://localhost/dmd/public/login`
   - Use registered email/password

3. **Dashboard:** Visit `http://localhost/dmd/public/dashboard`
   - Protected route (requires login)
   - Shows user info and roles

4. **Logout:** Visit `http://localhost/dmd/public/logout`

## 3. Default Roles

After seeding, these roles exist:
- **admin** - Full system access
- **business_owner** - Can manage listings
- **user** - Standard user

## 4. Assign Admin Role (via SQL)

```sql
USE digitalmarketing_display;

-- Get user ID (replace email)
SELECT id FROM users WHERE email = 'your@email.com';

-- Assign admin role (replace USER_ID and role ID)
INSERT INTO user_roles (user_id, role_id) 
VALUES (USER_ID, (SELECT id FROM roles WHERE slug = 'admin'));
```

## 5. Protected Routes Example

```php
// In routes/web.php
$router->get('/admin', 'App\Controllers\AdminController', 'index', 'auth');
```

The `'auth'` middleware automatically:
- Checks if user is logged in
- Redirects to `/login` if not authenticated

## 6. Role Checking in Controller

```php
use App\Helpers\Auth;

// Check role
if (Auth::hasRole('admin')) {
    // Admin only code
}

// Require role (403 if not authorized)
Auth::requireRole('admin');
```

## Security Features Active

✅ Password hashing (bcrypt)  
✅ CSRF protection  
✅ SQL injection prevention (PDO)  
✅ XSS prevention (sanitization)  
✅ Session fixation prevention  
✅ Input validation  
✅ Role-based access control  

See `AUTHENTICATION.md` for full documentation.
