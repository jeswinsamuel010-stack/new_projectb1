# Password Reset Token Debugging Guide

## What Was Fixed

### Issue 1: Timezone Comparison
**Problem:** The expiry check used `expires_at > NOW()` which can fail due to timezone differences between PHP and MySQL.

**Fix:** Changed to `UNIX_TIMESTAMP(expires_at) > UNIX_TIMESTAMP(NOW())` for timezone-safe comparison.

### Issue 2: Debug Mode Added
Added debug mode to both files so you can see exactly what's happening.

---

## How to Debug

### Step 1: Enable Debug Mode

In `process_forgot_password.php` (line ~15):
```php
$DEBUG = true;
```

In `reset_password.php` (line ~12):
```php
$DEBUG = true;
```

### Step 2: Request Password Reset
1. Go to `forgot_password.php`
2. Enter your email
3. Check your email for the reset link

### Step 3: Check Token Generation Debug
When you submit the forgot password form with `$DEBUG = true`, you'll see:
- Token (plain) - the actual token sent in email
- Token Hash - the SHA-256 hash stored in DB
- PHP Time
- Expires At time

### Step 4: Use Debug Token Checker
After clicking the reset link, use:
```
http://localhost/new_projectb/debug_token.php?token=YOUR_TOKEN_FROM_EMAIL
```

This will show:
- If token is found by hash
- If token is found by plain match (indicates storage issue)
- Expiry status
- All recent tokens in database

---

## Common Issues & Solutions

### Issue: "No record found for this token hash"
**Cause:** Token hash stored incorrectly or using wrong hash algorithm

**Fix:** Check `process_forgot_password.php` line ~90:
```php
$tokenHash = hash('sha256', $token);
```

And ensure database stores this exact hash.

### Issue: Token found by plain token but not hash
**Cause:** You're comparing with token_hash but stored plain token

**Fix:** Either:
1. Use `WHERE pr.token = ?` instead of `WHERE pr.token_hash = ?`
2. Or ensure both columns contain the correct values

### Issue: "This reset link has expired"
**Cause:** Timezone mismatch between PHP and MySQL

**Fix:** Already applied - using `UNIX_TIMESTAMP()` for comparison

---

## Manual Database Check

Run this SQL in phpMyAdmin to see all tokens:

```sql
SELECT
    id,
    email,
    token,
    token_hash,
    expires_at,
    used,
    created_at,
    TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago,
    CASE WHEN expires_at > NOW() THEN 'VALID' ELSE 'EXPIRED' END as status
FROM password_resets
ORDER BY created_at DESC
LIMIT 10;
```

---

## Quick Test Checklist

1. [ ] Table `password_resets` exists
2. [ ] Token is 64 characters (32 bytes hex)
3. [ ] Token hash is 64 characters (SHA-256)
4. [ ] `expires_at` is in future when token is created
5. [ ] `used` is 0 for new tokens
6. [ ] Hash in DB matches `hash('sha256', token from email)`

---

## If Still Not Working

Try this test in `reset_password.php` - temporarily change the query to find by plain token:

```php
// TEMPORARY TEST - Use this to debug
$sql = "SELECT pr.*, u.full_name, u.email
        FROM password_resets pr
        JOIN users u ON LOWER(pr.email) = LOWER(u.email)
        WHERE pr.token = ?  -- Changed from token_hash to token
        AND pr.used = 0
        AND UNIX_TIMESTAMP(pr.expires_at) > UNIX_TIMESTAMP(NOW())";

$resetRequest = fetchOne($sql, "s", [$token]);
```

If this works, it means the token_hash comparison is failing. Check:
1. Hash algorithm used when storing (must be SHA-256)
2. Hash algorithm used when comparing (must match)
