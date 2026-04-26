<?php
$user    = $user ?? current_user();
$initials= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="cell large-3 medium-4">
  <div class="pco-sidebar" style="margin-bottom:1.5rem;">
    <div class="pco-sidebar__top">
      <div class="pco-sidebar__avatar"><?= e($initials) ?></div>
      <div class="pco-sidebar__name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
      <div class="pco-sidebar__role">Administrator</div>
    </div>
    <div class="pco-sidebar__nav">
      <a href="<?= APP_URL ?>/pages/admin/dashboard.php"        class="<?= $current==='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <div class="section-label">Catalogue</div>
      <a href="<?= APP_URL ?>/pages/admin/conditions.php"       class="<?= $current==='conditions.php'?'active':'' ?>"><i class="fa-solid fa-stethoscope"></i> Conditions</a>
      <a href="<?= APP_URL ?>/pages/admin/products.php"         class="<?= $current==='products.php'?'active':'' ?>"><i class="fa-solid fa-pills"></i> Products</a>
      <a href="<?= APP_URL ?>/pages/admin/questionnaires.php"   class="<?= $current==='questionnaires.php'?'active':'' ?>"><i class="fa-solid fa-clipboard-list"></i> Questionnaires</a>
      <div class="section-label">Operations</div>
      <a href="<?= APP_URL ?>/pages/admin/orders.php"           class="<?= $current==='orders.php'?'active':'' ?>"><i class="fa-solid fa-box"></i> Orders</a>
      <a href="<?= APP_URL ?>/pages/admin/prescriptions.php"    class="<?= $current==='prescriptions.php'?'active':'' ?>"><i class="fa-solid fa-file-prescription"></i> Prescriptions</a>
      <div class="section-label">Users & System</div>
      <a href="<?= APP_URL ?>/pages/admin/users.php"            class="<?= $current==='users.php'?'active':'' ?>"><i class="fa-solid fa-users"></i> Users</a>
      <a href="<?= APP_URL ?>/pages/admin/audit-log.php"        class="<?= $current==='audit-log.php'?'active':'' ?>"><i class="fa-solid fa-scroll"></i> Audit Log</a>
      <a href="<?= APP_URL ?>/pages/admin/settings.php"         class="<?= $current==='settings.php'?'active':'' ?>"><i class="fa-solid fa-sliders"></i> Settings</a>
      <a href="<?= APP_URL ?>/pages/admin/reports.php"          class="<?= $current==='reports.php'?'active':'' ?>"><i class="fa-solid fa-chart-bar"></i> Reports</a>
      <a href="<?= APP_URL ?>/pages/admin/messages.php"         class="<?= $current==='messages.php'?'active':'' ?>"><i class="fa-solid fa-envelope"></i> Messages</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php"><i class="fa-solid fa-user-doctor"></i> Prescriber View</a>
      <a href="<?= APP_URL ?>/pages/dispenser/dashboard.php"><i class="fa-solid fa-pills"></i> Dispenser View</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
    </div>
  </div>
</div>
