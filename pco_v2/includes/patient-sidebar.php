<?php
// Patient sidebar partial — include inside grid row
$user     = $user ?? current_user();
$initials = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
$current  = basename($_SERVER['PHP_SELF']);
?>
<div class="cell large-3 medium-4">
  <div class="pco-sidebar" style="margin-bottom:1.5rem;">
    <div class="pco-sidebar__top">
      <div class="pco-sidebar__avatar"><?= e($initials) ?></div>
      <div class="pco-sidebar__name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
      <div class="pco-sidebar__role">Patient</div>
    </div>
    <div class="pco-sidebar__nav">
      <a href="<?= APP_URL ?>/pages/patient/dashboard.php"      class="<?= $current==='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/pages/patient/consultations.php"  class="<?= $current==='consultations.php'?'active':'' ?>"><i class="fa-solid fa-clipboard-list"></i> Consultations</a>
      <a href="<?= APP_URL ?>/pages/patient/prescriptions.php"  class="<?= $current==='prescriptions.php'?'active':'' ?>"><i class="fa-solid fa-file-prescription"></i> Prescriptions</a>
      <a href="<?= APP_URL ?>/pages/patient/orders.php"         class="<?= $current==='orders.php'?'active':'' ?>"><i class="fa-solid fa-box-archive"></i> My Orders</a>
      <a href="<?= APP_URL ?>/pages/patient/account.php"        class="<?= $current==='account.php'?'active':'' ?>"><i class="fa-solid fa-id-card"></i> My Account</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/patient/addresses.php"      class="<?= $current==='addresses.php'?'active':'' ?>"><i class="fa-solid fa-location-dot"></i> Addresses</a>
      <a href="<?= APP_URL ?>/pages/patient/profile.php"        class="<?= $current==='profile.php'?'active':'' ?>"><i class="fa-solid fa-gear"></i> Account Settings</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/conditions.php"><i class="fa-solid fa-stethoscope"></i> Browse Treatments</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
    </div>
  </div>
</div>
