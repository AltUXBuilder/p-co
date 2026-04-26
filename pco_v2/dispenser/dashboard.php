<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('dispenser','admin');

$user = current_user();
$uid  = current_user_id();

$pendingItems = Database::fetchOne(
    "SELECT COUNT(*) c FROM prescription_items pi
     JOIN prescriptions p ON p.id=pi.prescription_id
     WHERE pi.status='pending' AND p.status='active' AND p.expiry_date>=CURDATE()")['c'];
$dispensedToday = Database::fetchOne(
    "SELECT COUNT(*) c FROM prescription_items WHERE status='dispensed' AND DATE(dispensed_at)=CURDATE() AND dispensed_by=?",[$uid])['c'];
$labelsToday = Database::fetchOne(
    "SELECT COUNT(*) c FROM dispensing_labels WHERE DATE(printed_at)=CURDATE() AND generated_by=?",[$uid])['c'];

$queue = Database::fetchAll(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name,
            CONCAT(pr.first_name,' ',pr.last_name) prescriber_name,
            COUNT(pi.id) item_count,
            SUM(CASE WHEN pi.status='dispensed' THEN 1 ELSE 0 END) dispensed_count
     FROM prescriptions p
     JOIN users u  ON u.id=p.patient_id
     JOIN users pr ON pr.id=p.prescriber_id
     JOIN prescription_items pi ON pi.prescription_id=p.id
     WHERE p.status='active' AND p.expiry_date>=CURDATE()
       AND pi.status='pending'
     GROUP BY p.id
     ORDER BY p.created_at ASC LIMIT 20"
);

$page_title = 'Dispenser Dashboard';
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <?php include APP_PATH . '/includes/dispenser-sidebar.php'; ?>

  <!-- Main -->
  <div class="cell large-9 medium-8">

    <div style="margin-bottom:1.75rem;">
      <h1 style="font-size:1.55rem;margin-bottom:.2rem;">Dispensary</h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;">
        <?= $pendingItems ?> prescription item<?= $pendingItems!=1?'s':'' ?> pending dispensing.
      </p>
    </div>

    <!-- Stats -->
    <div class="grid-x grid-margin-x" style="margin-bottom:1.5rem;">
      <?php foreach ([
        ['pills','amber',  $pendingItems,   'Items to Dispense'],
        ['check','green',  $dispensedToday, 'Dispensed Today'],
        ['tag',  'purple', $labelsToday,    'Labels Printed Today'],
      ] as [$ico,$mod,$val,$lbl]): ?>
      <div class="cell large-4 medium-4 small-4">
        <div class="pco-stat">
          <div class="pco-stat__icon pco-stat__icon--<?= $mod ?>"><i class="fa-solid fa-<?= $ico ?>"></i></div>
          <div>
            <div class="pco-stat__val"><?= $val ?></div>
            <div class="pco-stat__label"><?= $lbl ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Queue -->
    <div class="pco-card">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-pills" style="color:var(--pco-amber);margin-right:.4rem;"></i>Prescriptions Pending Dispensing</h3>
      </div>
      <?php if (empty($queue)): ?>
      <div class="pco-card__body text-center" style="padding:2.5rem;">
        <i class="fa-solid fa-circle-check" style="font-size:2rem;color:var(--pco-green);display:block;margin-bottom:.75rem;"></i>
        <p style="color:var(--pco-grey-500);margin:0;">All prescriptions dispensed — queue clear.</p>
      </div>
      <?php else: ?>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Ref</th><th>Patient</th><th>Prescriber</th><th>Issued</th><th>Items</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($queue as $rx): ?>
            <tr>
              <td data-label="Ref"><strong style="font-family:monospace;color:var(--pco-purple);"><?= e($rx['prescription_ref']) ?></strong></td>
              <td data-label="Patient"><?= e($rx['patient_name']) ?></td>
              <td data-label="Prescriber"><?= e($rx['prescriber_name']) ?></td>
              <td data-label="Issued"><?= date('d M Y',strtotime($rx['issue_date'])) ?></td>
              <td data-label="Items">
                <span class="pco-badge <?= $rx['dispensed_count']>0?'badge--amber':'badge--grey' ?>">
                  <?= $rx['dispensed_count'] ?>/<?= $rx['item_count'] ?> dispensed
                </span>
              </td>
              <td>
                <a href="<?= APP_URL ?>/pages/dispenser/dispense.php?rx_id=<?= $rx['id'] ?>" class="pco-btn pco-btn--primary pco-btn--sm">
                  Dispense <i class="fa-solid fa-arrow-right fa-xs"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
