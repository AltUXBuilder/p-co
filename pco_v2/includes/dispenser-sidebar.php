<?php
$user     = $user ?? current_user();
$initials = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
$current  = basename($_SERVER['PHP_SELF']);
$pending  = Database::fetchOne(
    "SELECT COUNT(*) c FROM prescription_items pi
     JOIN prescriptions p ON p.id=pi.prescription_id
     WHERE pi.status='pending' AND p.status='active' AND p.expiry_date>=CURDATE()")['c'];
?>
<div class="cell large-3 medium-4">
  <div class="pco-sidebar" style="margin-bottom:1.5rem;">
    <div class="pco-sidebar__top">
      <div class="pco-sidebar__avatar"><?= e($initials) ?></div>
      <div class="pco-sidebar__name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
      <div class="pco-sidebar__role">Dispenser</div>
    </div>
    <div class="pco-sidebar__nav">
      <a href="<?= APP_URL ?>/pages/dispenser/dashboard.php" class="<?= $current==='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="<?= APP_URL ?>/pages/dispenser/dispense.php"  class="<?= $current==='dispense.php'?'active':'' ?>">
        <i class="fa-solid fa-pills"></i> Dispense Queue
        <?php if ($pending > 0): ?><span class="pco-badge badge--amber" style="margin-left:auto;"><?= $pending ?></span><?php endif; ?>
      </a>
      <a href="<?= APP_URL ?>/pages/dispenser/history.php"   class="<?= $current==='history.php'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
      <div class="sep"></div>
      <a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
    </div>
  </div>
</div>
