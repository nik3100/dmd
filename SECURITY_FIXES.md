# Security Fixes

## Fixed: Open Redirect Vulnerability (CVE-style)

### Issue
**Location:** `app/controllers/AuthController.php:108-109`

**Description:** The login action was redirecting to an unvalidated `$_GET['redirect']` parameter, allowing attackers to craft malicious URLs like `/login?redirect=https://evil.com` to redirect authenticated users to arbitrary external sites after login.

**Risk:** 
- Phishing attacks
- Credential theft
- Session hijacking via malicious redirects

### Fix
1. **Added `validateRedirect()` method** to `app/core/Controller.php`
   - Only allows relative paths starting with `/`
   - Rejects URLs with schemes (`http://`, `https://`, `javascript:`, etc.)
   - Rejects URLs with hosts/domains
   - Rejects URLs with user/pass components
   - Allows query strings for preserving search parameters

2. **Updated login method** in `app/controllers/AuthController.php`
   - Now uses `validateRedirect()` to sanitize redirect URLs
   - Falls back to `/dashboard` if validation fails

### Attack Vectors Prevented
- ✅ `https://evil.com` → Rejected
- ✅ `//evil.com` → Rejected  
- ✅ `javascript:alert(1)` → Rejected
- ✅ `http://evil.com` → Rejected
- ✅ `//user@evil.com` → Rejected
- ✅ `/dashboard` → Allowed ✓
- ✅ `/dashboard?tab=settings` → Allowed ✓

### Testing
Test the fix with these URLs (should all redirect to `/dashboard`):
```
/login?redirect=https://evil.com
/login?redirect=//evil.com
/login?redirect=javascript:alert(1)
/login?redirect=/dashboard  ← Should work
```

---

## Security Best Practices

When handling redirects:
1. **Always validate** user-provided redirect URLs
2. **Whitelist** allowed redirect destinations
3. **Use relative paths** when possible
4. **Never trust** user input for redirects without validation
