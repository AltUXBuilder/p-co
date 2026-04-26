# Prescribe & Co. — InfinityFree Deployment Guide

## Key Differences vs Hostinger

| | Hostinger | InfinityFree |
|-|-----------|--------------|
| Files folder | `public_html/` | `htdocs/` |
| MySQL hostname | `localhost` | `sql104.infinityfree.net` (from panel) |
| DB name prefix | your choice | `epiz_XXXXXXXX_yourname` |
| Username prefix | your choice | `epiz_XXXXXXXX` |
| PHP version | 8.1+ | 8.2 ✓ |
| mod_headers | ✅ | ❌ auto-deletes .htaccess |
| Free subdomain | — | `yoursite.infinityfree.app` |

---

## Step by Step

### 1. Create account + hosting
Sign up at https://infinityfree.com → create new account → pick subdomain.

### 2. Create database
Control panel → MySQL Databases → create one (e.g. name it `prescribeco`).
Note down the full details shown:
- **Hostname** e.g. `sql104.infinityfree.net`
- **Database name** e.g. `epiz_12345678_prescribeco`
- **Username** e.g. `epiz_12345678`
- **Password** — from control panel → Account Details

### 3. Import database
Control panel → MySQL Databases → phpMyAdmin → select your DB → Import → `database_full.sql` → Go.

### 4. Edit config/config.php
```php
define('APP_URL',  'https://yoursite.infinityfree.app'); // your subdomain
define('DB_HOST',  'sql104.infinityfree.net');  // from your panel
define('DB_NAME',  'epiz_12345678_prescribeco'); // full name with prefix
define('DB_USER',  'epiz_12345678');             // username with prefix
define('DB_PASS',  'your_password');             // from Account Details
define('SECRET_KEY','paste-64-random-chars');    // random.org
```

### 5. Upload files
Control panel → Online File Manager → open `htdocs/` → upload all files.
Structure must be:
```
htdocs/
├── index.php
├── .htaccess
├── config/
├── includes/
├── assets/
├── pages/
└── ...
```

### 6. Run setup
- Edit `setup.php` line 10 — set your own key e.g. `'mySetupKey99'`
- Visit `https://yoursite.infinityfree.app/setup.php`
- Enter credentials + key → Run Setup
- **DELETE setup.php immediately after**

### 7. Login
Visit `/pages/auth/login.php`

| Role | Email | Password |
|------|-------|---------|
| Admin | admin@prescribeandco.co.uk | PrescribeCo@2024! |
| Prescriber | dr.patel@prescribeandco.co.uk | PrescribeCo@2024! |
| Dispenser | dispenser@prescribeandco.co.uk | PrescribeCo@2024! |

**Change all passwords immediately.**

---

## Troubleshooting

### "Service temporarily unavailable"
Database connection failed. Check:
- DB_HOST is the full hostname from your panel (not `localhost`)
- DB_NAME and DB_USER include the full `epiz_XXXXXXXX_` prefix
- Password is from Account Details, not the MySQL section

### Login says incorrect password
The bcrypt hash may need regenerating on InfinityFree's PHP build.
Use the password reset flow: visit `/pages/auth/forgot-password.php`, enter admin email, copy the link shown on screen, reset to a new password.

### .htaccess deleted automatically
InfinityFree's security system deletes .htaccess files containing Header directives.
The included .htaccess is already safe — all `mod_headers` blocks have been removed.

### Blank page / 500 error
- Check APP_URL matches your subdomain exactly
- Make sure all files are directly inside `htdocs/` (not in a subfolder)
- Temporarily set `APP_DEBUG` to `true` in `config.php` to see the error — disable after

### Image uploads failing
InfinityFree may restrict file permissions. In File Manager, right-click `uploads/` → Permissions → set to 755.

---

## InfinityFree Limitations (Free Tier)
- No outbound email (password reset shows token on screen — this is intentional)
- 5 GB bandwidth/day (fine for testing)
- Account suspended if inactive 30 days
- No cron jobs (not needed)

For production use migrate to Hostinger or iFastNet.

---

*Prescribe & Co. v2.0*
