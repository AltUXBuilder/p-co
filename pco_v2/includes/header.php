<?php
$user  = current_user();
$role  = current_role();
$uname = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : '';
$initials = $user ? strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) : '';

$dash_url = match($role) {
    'admin'      => APP_URL.'/pages/admin/dashboard.php',
    'prescriber' => APP_URL.'/pages/prescriber/dashboard.php',
    'dispenser'  => APP_URL.'/pages/dispenser/dashboard.php',
    default      => APP_URL.'/pages/patient/dashboard.php',
};
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($page_title ?? 'Prescribe & Co.') ?> — P&amp;Co.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.8.1/css/foundation.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <?= $extra_head ?? '' ?>
</head>
<body class="role-<?= e($role ?: 'guest') ?>">

<nav class="pco-nav">
  <div class="grid-container">
    <div class="pco-nav__inner">

      <!-- Logo -->
      <a href="<?= APP_URL ?>/" class="pco-nav__logo">
        <?php if (file_exists(APP_PATH . '/assets/img/logo.png')): ?>
          <img src="<?= APP_URL ?>/assets/img/logo.png" alt="Prescribe and Co logo" class="pco-nav__logo-img">
        <?php else: ?>
          <span class="pco-nav__logo-text">Prescribe &amp; Co</span>
        <?php endif; ?>
      </a>

      <!-- Public nav links -->
      <?php if (!is_logged_in() || $role === 'patient'): ?>
      <div class="pco-nav__links hide-for-small-only">
        <a href="<?= APP_URL ?>/pages/conditions.php?gender=male"   class="<?= ($active_nav??'')   === 'men'   ? 'active' : '' ?>">Men</a>
        <a href="<?= APP_URL ?>/pages/conditions.php?gender=female" class="<?= ($active_nav??'')   === 'women' ? 'active' : '' ?>">Women</a>
        <a href="<?= APP_URL ?>/pages/about.php"                    class="<?= ($active_nav??'')   === 'about' ? 'active' : '' ?>">How It Works</a>
      </div>
      <?php endif; ?>

      <!-- Right actions -->
      <div class="pco-nav__actions">
        <?php if (is_logged_in()): ?>

          <!-- Role badge -->
          <span class="pco-nav__role role-<?= e($role) ?>"><?= e(ucfirst($role)) ?></span>

          <!-- Dash link -->
          <a href="<?= $dash_url ?>" class="hide-for-small-only" style="display:inline-flex;align-items:center;gap:.4rem;font-size:.845rem;font-weight:500;color:var(--pco-grey-700);padding:.35rem .8rem;border-radius:var(--pco-r-pill);transition:all .18s;">
            <i class="fa-solid fa-gauge-high" style="font-size:.85rem;"></i>
            <span>Dashboard</span>
          </a>

          <!-- User menu -->
          <div class="pco-user-menu">
            <button class="pco-user-btn" id="userMenuBtn" aria-expanded="false">
              <span class="avatar"><?= e($initials) ?></span>
              <span class="hide-for-small-only"><?= e($user['first_name']) ?></span>
              <i class="fa-solid fa-chevron-down fa-xs"></i>
            </button>
            <ul class="pco-dropdown" id="userDropdown">
              <li><a href="<?= APP_URL ?>/pages/patient/account.php"><i class="fa-solid fa-id-card fa-fw"></i> My Account</a></li>
              <?php if ($role === 'patient'): ?>
              <li><a href="<?= APP_URL ?>/pages/patient/consultations.php"><i class="fa-solid fa-clipboard-list fa-fw"></i> Consultations</a></li>
              <li><a href="<?= APP_URL ?>/pages/patient/prescriptions.php"><i class="fa-solid fa-file-prescription fa-fw"></i> Prescriptions</a></li>
              <li><a href="<?= APP_URL ?>/pages/patient/orders.php"><i class="fa-solid fa-box fa-fw"></i> Orders</a></li>
              <?php endif; ?>
              <li class="sep"></li>
              <li><a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket fa-fw"></i> Sign Out</a></li>
            </ul>
          </div>

        <?php else: ?>
          <a href="<?= APP_URL ?>/pages/auth/login.php"    class="pco-btn pco-btn--ghost pco-btn--sm hide-for-small-only">Sign In</a>
          <a href="<?= APP_URL ?>/pages/auth/register.php" class="pco-btn pco-btn--primary pco-btn--sm">Get Started</a>
        <?php endif; ?>

        <!-- Mobile toggle -->
        <button class="pco-mobile-toggle show-for-small-only" id="mobileToggle">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>

    </div>
  </div>

  <!-- Mobile menu -->
  <div class="pco-mobile-nav" id="mobileNav">
    <div class="grid-container">
      <?php if (!is_logged_in() || $role === 'patient'): ?>
        <a href="<?= APP_URL ?>/pages/conditions.php?gender=male">Men's Health</a>
        <a href="<?= APP_URL ?>/pages/conditions.php?gender=female">Women's Health</a>
        <a href="<?= APP_URL ?>/pages/about.php">How It Works</a>
      <?php endif; ?>
      <?php if (is_logged_in()): ?>
        <a href="<?= $dash_url ?>">Dashboard</a>
        <a href="<?= APP_URL ?>/pages/patient/account.php">My Account</a>
        <a href="<?= APP_URL ?>/pages/auth/logout.php" style="color:var(--pco-red)">Sign Out</a>
      <?php else: ?>
        <a href="<?= APP_URL ?>/pages/auth/login.php">Sign In</a>
        <a href="<?= APP_URL ?>/pages/auth/register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Flash messages -->
<div style="position:fixed;top:80px;right:16px;z-index:9000;min-width:280px;max-width:400px;" id="flash-global">
  <?= flash_render() ?>
</div>
