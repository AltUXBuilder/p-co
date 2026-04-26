<?php
$user     = $user ?? current_user();
$initials = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
$current  = basename($_SERVER['PHP_SELF']);
$pending  = Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='submitted'")['c'];
?>
<div class="cell large-3 medium-4">
  <div class="pco-sidebar" style="margin-bottom:1.5rem;">
    <div class="pco-sidebar__top">
      <div class="pco-sidebar__avatar"><?= e($initials) ?></div>
      <div class="pco-sidebar__name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
      <div class="pco-sidebar__role">Prescriber</div>
    </div>
    <div class="pco-sidebar__nav">
      <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php"     class="<?= $current==='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/pages/prescriber/queue.php"         class="<?= $current==='queue.php'?'active':'' ?>">
        <i class="fa-solid fa-inbox"></i> Consultation Queue
        <?php if ($pending > 0): ?><span class="pco-badge badge--amber" style="margin-left:auto;"><?= $pending ?></span><?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/pages/prescriber/prescriptions.php" class="<?= $current==='prescriptions.php'?'active':'' ?>"><i class="fa-solid fa-file-prescription"></i> My Prescriptions</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/patient/profile.php"><i class="fa-solid fa-gear"></i> Settings</a>
      <a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
    </div>
  </div>
</div>
