# Prescribe & Co. — Deployment Guide
**Version 2.0.0 | PHP 8.1+ | MySQL 5.7+ / MariaDB 10.3+**

---

## Complete Page List

### Public
- `/` — Homepage
- `/pages/conditions.php` — Conditions browser
- `/pages/condition.php?slug=X` — Single condition + products
- `/pages/about.php` — How It Works
- `/pages/contact.php` — Contact form (stored in DB)
- `/pages/privacy.php` — Privacy Policy
- `/pages/terms.php` — Terms of Service

### Auth
- `/pages/auth/login.php`
- `/pages/auth/register.php`
- `/pages/auth/forgot-password.php`
- `/pages/auth/reset-password.php`

### Patient Portal
- `/pages/patient/dashboard.php`
- `/pages/patient/consultations.php`
- `/pages/patient/consultation-view.php?id=X`
- `/pages/patient/prescriptions.php`
- `/pages/patient/prescription-view.php?id=X`
- `/pages/patient/orders.php`
- `/pages/patient/order-view.php?id=X`
- `/pages/patient/checkout.php?rx_id=X`
- `/pages/patient/addresses.php`
- `/pages/patient/profile.php`
- `/pages/patient/account.php`

### Prescriber Portal
- `/pages/prescriber/dashboard.php`
- `/pages/prescriber/queue.php`
- `/pages/prescriber/review.php?id=X`
- `/pages/prescriber/prescriptions.php`

### Dispenser Portal
- `/pages/dispenser/dashboard.php`
- `/pages/dispenser/dispense.php?rx_id=X`
- `/pages/dispenser/labels.php?rx_id=X`
- `/pages/dispenser/history.php`

### Admin Portal (13 pages)
- `/pages/admin/dashboard.php`
- `/pages/admin/users.php` — list, search, role, activate/deactivate
- `/pages/admin/user-edit.php` — add/edit any user
- `/pages/admin/conditions.php` — list, add, edit, image upload, toggle
- `/pages/admin/products.php` — list, add, edit, image upload, toggle
- `/pages/admin/questionnaires.php` — template list
- `/pages/admin/questionnaire-edit.php?id=X` — drag-drop editor, all question types
- `/pages/admin/orders.php` — list, view, update status + delivery tracking
- `/pages/admin/prescriptions.php` — all prescriptions across prescribers
- `/pages/admin/audit-log.php` — full log, all filters
- `/pages/admin/settings.php` — edit all system settings
- `/pages/admin/reports.php` — charts, stats, approval rates, top products
- `/pages/admin/messages.php` — contact form inbox

---

## Deployment Workflow (InfinityFree trial → Hostinger production)

1. Deploy first to **InfinityFree** for trial validation (see `INFINITYFREE.md`).
2. After sign-off, deploy the same codebase to **Hostinger production**.

## Deployment (Hostinger)

### 1. Upload all files to `public_html/`

### 2. Create database
hPanel → Databases → MySQL Databases → create `prescribeco_db` with a user with full privileges.

### 3. Edit `config/config.php`
```php
define('APP_URL',    'https://yourdomain.co.uk');
define('DB_HOST',    'localhost');
define('DB_NAME',    'prescribeco_db');
define('DB_USER',    'your_user');
define('DB_PASS',    'your_password');
define('SECRET_KEY', '64-random-chars-here');
define('GPHC_NUMBER','your-gphc-number');
```

### 4. Import database
phpMyAdmin → Import → select `database_full.sql` → Go

### 5. Run setup
- Edit `setup.php` line 10: set your own `SETUP_KEY`
- Visit `https://yourdomain.co.uk/setup.php`
- **Delete `setup.php` after**

### 6. Set permissions
`uploads/` → 755, `logs/` → 755

### 7. Login and change all passwords
| Role | Email | Default Password |
|------|-------|-----------------|
| Admin | admin@prescribeandco.co.uk | PrescribeCo@2024! |
| Prescriber | dr.patel@prescribeandco.co.uk | PrescribeCo@2024! |
| Dispenser | dispenser@prescribeandco.co.uk | PrescribeCo@2024! |

---

## Deployment (InfinityFree)
See `INFINITYFREE.md` — key differences:
- Files go in `htdocs/` not `public_html/`
- MySQL hostname is NOT `localhost` (get from control panel)
- DB name/user have forced `epiz_XXXXX_` prefix
- mod_headers not supported — .htaccess already safe for InfinityFree

---

## Admin Guide

### Conditions
Admin → Conditions → Add/Edit. Upload image (JPG/PNG/WebP ≤5MB), set gender filter (male/female/all), Font Awesome icon name, sort order, toggle active.

### Brand logo
Place your company logo at `assets/img/logo.png` and it will be used automatically in the top navigation.

### Products  
Admin → Products → Add/Edit. Assign to condition, set SKU, name, brand, strength, form, price, stock, upload image, toggle Rx required.

### Questionnaire Editor
Admin → Questionnaires → select template → Edit Questions.
- Drag rows to reorder questions
- Question types: text, textarea, radio, checkbox, select, number, date, boolean
- Set disqualifier values — auto-rejects consultation if matched
- Group questions into steps with the Step # field
- Create entirely new templates with New Template

### Users
Admin → Users. Change roles, activate/deactivate, reset passwords, add new staff accounts.

### Settings
Admin → Settings. Update pharmacy details, GPhC number, shipping costs, Stripe keys, label footer, maintenance mode.

### Reports
Admin → Reports. Date range selector, daily charts (Chart.js), approval rates per condition, top products.

---

## Stripe (when ready for live payments)
1. Get keys from stripe.com
2. Admin → Settings → paste `pk_live_...` and `sk_live_...`
3. Update `pages/patient/checkout.php` — replace placeholder UI with Stripe.js Elements
4. Handle `payment_intent.succeeded` webhook

---

## Post-Deployment Checklist
- [ ] Delete `setup.php`
- [ ] Change all 3 default staff passwords
- [ ] Correct `APP_URL` in `config.php`
- [ ] Long random `SECRET_KEY`
- [ ] Real `GPHC_NUMBER` in config + Admin → Settings
- [ ] Pharmacy address/phone/email in Admin → Settings
- [ ] Upload condition + product images in admin
- [ ] `uploads/` and `logs/` permissions set to 755
- [ ] SSL enabled in hosting panel
- [ ] Full test: register → consult → approve → checkout → dispense → labels

---

## Security
- bcrypt cost 12 password hashing
- CSRF tokens on all POST forms
- Role-based access on every page (`require_auth()`)
- Upload directory blocks non-image direct access
- Full audit log on all sensitive actions
- Session cookies: HttpOnly + SameSite=Strict

---

*Prescribe & Co. v2.0 | GPhC-registered online pharmacy platform*
