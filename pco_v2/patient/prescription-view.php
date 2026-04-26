<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid  = current_user_id();
$user = current_user();
$rxId = (int)($_GET['id'] ?? 0);
if (!$rxId) redirect('/pages/patient/prescriptions.php');

$rx = Database::fetchOne(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) prescriber_name
     FROM prescriptions p
     JOIN users u ON u.id=p.prescriber_id
     WHERE p.id=? AND p.patient_id=?", [$rxId,$uid]
);
if (!$rx) { http_response_code(404); die('Not found.'); }

$items   = Database::fetchAll("SELECT * FROM prescription_items WHERE prescription_id=? ORDER BY id",[$rxId]);
$orders  = Database::fetchAll("SELECT * FROM orders WHERE prescription_id=? ORDER BY created_at DESC",[$rxId]);
$consult = Database::fetchOne("SELECT c.*, cn.name cond_name FROM consultations c JOIN conditions cn ON cn.id=c.condition_id WHERE c.id=?",[$rx['consultation_id']]);

$page_title = 'Prescription '.$rx['prescription_ref'];
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>

<div class="cell large-9 medium-8">
  <div class="pco-breadcrumb" style="margin-bottom:1rem;">
    <a href="<?= APP_URL ?>/pages/patient/prescriptions.php">Prescriptions</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <span><?= e($rx['prescription_ref']) ?></span>
  </div>

  <!-- Rx header card -->
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-rx-header">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
        <div>
          <div style="font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pco-lavender);opacity:.8;margin-bottom:.3rem;">Prescription</div>
          <div class="pco-rx-ref"><?= e($rx['prescription_ref']) ?></div>
        </div>
        <?= status_badge($rx['status']) ?>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-top:.9rem;font-size:.8rem;color:rgba(255,255,255,.7);">
        <span><i class="fa-solid fa-user-doctor"></i> <?= e($rx['prescriber_name']) ?></span>
        <span><i class="fa-solid fa-calendar"></i> Issued <?= date('d M Y',strtotime($rx['issue_date'])) ?></span>
        <span><i class="fa-solid fa-calendar-xmark"></i> Expires <?= date('d M Y',strtotime($rx['expiry_date'])) ?></span>
        <?php if ($consult): ?><span><i class="fa-solid fa-stethoscope"></i> <?= e($consult['cond_name']) ?></span><?php endif; ?>
      </div>
    </div>

    <div class="pco-card__body">
      <h4 style="font-family:var(--pco-font-body);font-weight:700;font-size:.875rem;margin-bottom:1rem;">Prescribed Medications</h4>
      <?php foreach ($items as $item): ?>
      <div class="pco-rx-item">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
          <div>
            <div class="pco-rx-item__name"><?= e($item['medication_name']) ?><?= $item['strength']?' — '.e($item['strength']):'' ?></div>
            <div class="pco-rx-item__detail"><?= e($item['dosage_form']) ?> · <?= $item['quantity'] ?> <?= e($item['quantity_unit']) ?><?= $item['duration_days']?' · '.$item['duration_days'].' days':'' ?></div>
          </div>
          <?= status_badge($item['status']) ?>
        </div>
        <?php if ($item['dosage_instructions']): ?>
        <p style="margin:.5rem 0 0;font-size:.855rem;"><?= e($item['dosage_instructions']) ?></p>
        <?php endif; ?>
        <?php if ($item['warnings']): ?>
        <div style="margin-top:.35rem;font-size:.78rem;color:var(--pco-amber);"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($item['warnings']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <?php if ($rx['clinical_notes']): ?>
      <div style="margin-top:1rem;padding:1rem;background:var(--pco-grey-50);border-radius:var(--pco-r-lg);">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin-bottom:.4rem;">Prescriber Notes</div>
        <p style="margin:0;font-size:.875rem;"><?= e($rx['clinical_notes']) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($rx['status'] === 'active'): ?>
      <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem;">
        <a href="<?= APP_URL ?>/pages/patient/checkout.php?rx_id=<?= $rx['id'] ?>" class="pco-btn pco-btn--primary">
          <i class="fa-solid fa-truck-fast"></i> Order &amp; Deliver
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Orders for this Rx -->
  <?php if (!empty($orders)): ?>
  <div class="pco-card">
    <div class="pco-card__head"><h3>Orders</h3></div>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Order Ref</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= e($o['order_ref']) ?></strong></td>
            <td><?= money($o['total_amount']) ?></td>
            <td><?= status_badge($o['payment_status']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
            <td><a href="<?= APP_URL ?>/pages/patient/order-view.php?id=<?= $o['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
