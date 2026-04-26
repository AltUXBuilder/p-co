<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');

$user = current_user();
$uid  = current_user_id();

$stats = [
    'consults' => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE patient_id=?",[$uid])['c'],
    'rxActive' => Database::fetchOne("SELECT COUNT(*) c FROM prescriptions WHERE patient_id=? AND status='active'",[$uid])['c'],
    'orders'   => Database::fetchOne("SELECT COUNT(*) c FROM orders WHERE patient_id=?",[$uid])['c'],
];

$consultations = Database::fetchAll(
    "SELECT c.*, cn.name cond_name FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     WHERE c.patient_id=? ORDER BY c.created_at DESC LIMIT 5", [$uid]);

$prescriptions = Database::fetchAll(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) prescriber_name
     FROM prescriptions p JOIN users u ON u.id=p.prescriber_id
     WHERE p.patient_id=? AND p.status='active' AND p.expiry_date>=CURDATE()
     ORDER BY p.created_at DESC LIMIT 3", [$uid]);

$orders = Database::fetchAll(
    "SELECT * FROM orders WHERE patient_id=? ORDER BY created_at DESC LIMIT 5", [$uid]);

$page_title = 'My Dashboard';
$active_nav = 'dashboard';
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <?php include APP_PATH . '/includes/patient-sidebar.php'; ?>

  <!-- Main -->
  <div class="cell large-9 medium-8">

    <div style="margin-bottom:1.75rem;">
      <h1 style="font-size:1.6rem;margin-bottom:.2rem;">Hello, <?= e($user['first_name']) ?></h1>
      <p style="color:var(--pco-grey-500);font-size:.9rem;">Here's your Prescribe &amp; Co. health overview.</p>
    </div>

    <!-- Stats -->
    <div class="grid-x grid-margin-x" style="margin-bottom:1.5rem;">
      <?php
      $stat_items = [
        ['icon'=>'clipboard-list','mod'=>'purple', 'val'=>$stats['consults'], 'label'=>'Consultation'.($stats['consults']!=1?'s':'')],
        ['icon'=>'file-prescription','mod'=>'green','val'=>$stats['rxActive'],'label'=>'Active Prescription'.($stats['rxActive']!=1?'s':'')],
        ['icon'=>'box',            'mod'=>'amber',  'val'=>$stats['orders'],  'label'=>'Order'.($stats['orders']!=1?'s':'')],
      ];
      foreach ($stat_items as $s): ?>
      <div class="cell large-4 medium-4 small-12" style="margin-bottom:1rem;">
        <div class="pco-stat">
          <div class="pco-stat__icon pco-stat__icon--<?= $s['mod'] ?>">
            <i class="fa-solid fa-<?= $s['icon'] ?>"></i>
          </div>
          <div>
            <div class="pco-stat__val"><?= $s['val'] ?></div>
            <div class="pco-stat__label"><?= $s['label'] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Start CTA -->
    <div style="background:linear-gradient(145deg,var(--pco-black),var(--pco-purple-deep));border-radius:var(--pco-r-xl);padding:1.75rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
      <div>
        <div style="font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pco-lavender);margin-bottom:.4rem;">New Treatment</div>
        <h3 style="color:white;margin:0;font-size:1.1rem;font-family:var(--pco-font-body);font-weight:700;">Need a new prescription?</h3>
        <p style="color:rgba(255,255,255,.7);margin:.2rem 0 0;font-size:.855rem;">Browse conditions and start a free consultation.</p>
      </div>
      <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--primary">
        <i class="fa-solid fa-stethoscope"></i> Start consultation
      </a>
    </div>

    <!-- Active prescriptions -->
    <?php if (!empty($prescriptions)): ?>
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-file-prescription" style="color:var(--pco-purple);margin-right:.4rem;"></i>Active Prescriptions</h3>
        <a href="<?= APP_URL ?>/pages/patient/prescriptions.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <div class="pco-card__body" style="padding:1.25rem 1.5rem;">
        <?php foreach ($prescriptions as $rx): ?>
        <div class="pco-rx-item">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;flex-wrap:wrap;">
            <div>
              <div class="pco-rx-item__name"><?= e($rx['prescription_ref']) ?></div>
              <div class="pco-rx-item__detail">
                Prescribed by <?= e($rx['prescriber_name']) ?> &mdash;
                Expires <?= date('d M Y',strtotime($rx['expiry_date'])) ?>
              </div>
            </div>
            <?= status_badge($rx['status']) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Consultations table -->
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-clipboard-list" style="color:var(--pco-purple);margin-right:.4rem;"></i>Recent Consultations</h3>
        <a href="<?= APP_URL ?>/pages/patient/consultations.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <?php if (empty($consultations)): ?>
      <div class="pco-card__body text-center" style="padding:2.5rem;">
        <i class="fa-solid fa-clipboard" style="font-size:2.2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
        <p style="color:var(--pco-grey-500);margin:0;">No consultations yet. <a href="<?= APP_URL ?>/pages/conditions.php" style="color:var(--pco-purple);">Start your first</a></p>
      </div>
      <?php else: ?>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Condition</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($consultations as $c): ?>
            <tr>
              <td data-label="Condition"><strong><?= e($c['cond_name']) ?></strong></td>
              <td data-label="Status"><?= status_badge($c['status']) ?></td>
              <td data-label="Submitted">
                <?= $c['submitted_at'] ? date('d M Y',strtotime($c['submitted_at']))
                    : '<em style="color:var(--pco-grey-500)">Draft</em>' ?>
              </td>
              <td>
                <a href="<?= APP_URL ?>/pages/patient/consultation-view.php?id=<?= $c['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Orders -->
    <?php if (!empty($orders)): ?>
    <div class="pco-card">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-box" style="color:var(--pco-purple);margin-right:.4rem;"></i>Recent Orders</h3>
        <a href="<?= APP_URL ?>/pages/patient/orders.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Order Ref</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td data-label="Ref"><strong><?= e($o['order_ref']) ?></strong></td>
              <td data-label="Total"><?= money($o['total_amount']) ?></td>
              <td data-label="Payment"><?= status_badge($o['payment_status']) ?></td>
              <td data-label="Status"><?= status_badge($o['status']) ?></td>
              <td data-label="Date"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /main -->
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
