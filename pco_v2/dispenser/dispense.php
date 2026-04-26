<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('dispenser','admin');

$uid  = current_user_id();
$rxId = (int)($_GET['rx_id'] ?? 0);
if (!$rxId) redirect('/pages/dispenser/dashboard.php');

$rx = Database::fetchOne(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name,
            u.date_of_birth, u.gender,
            CONCAT(pr.first_name,' ',pr.last_name) prescriber_name,
            pa.line1, pa.line2, pa.city, pa.postcode
     FROM prescriptions p
     JOIN users u  ON u.id=p.patient_id
     JOIN users pr ON pr.id=p.prescriber_id
     LEFT JOIN orders o ON o.prescription_id=p.id AND o.status!='cancelled'
     LEFT JOIN patient_addresses pa ON pa.id=o.address_id
     WHERE p.id=?", [$rxId]
);
if (!$rx) { http_response_code(404); die('Prescription not found.'); }

$items = Database::fetchAll(
    "SELECT pi.*, p.name prod_name FROM prescription_items pi
     JOIN products p ON p.id=pi.product_id
     WHERE pi.prescription_id=? ORDER BY pi.id",
    [$rxId]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'dispense') {
        $toDispense = $_POST['dispense'] ?? [];
        $dispensed  = 0;
        foreach ($items as $item) {
            if (!empty($toDispense[$item['id']]) && $item['status'] === 'pending') {
                Database::update('prescription_items',[
                    'status'       => 'dispensed',
                    'dispensed_at' => date('Y-m-d H:i:s'),
                    'dispensed_by' => $uid,
                    'dispensed_qty'=> (int)$item['quantity'],
                    'updated_at'   => date('Y-m-d H:i:s'),
                ],['id'=>$item['id']]);
                $dispensed++;
            }
        }

        // Check if all items dispensed — update prescription status
        $remaining = Database::fetchOne(
            "SELECT COUNT(*) c FROM prescription_items WHERE prescription_id=? AND status='pending'",[$rxId])['c'];
        if ($remaining == 0) {
            Database::update('prescriptions',['status'=>'dispensed','updated_at'=>date('Y-m-d H:i:s')],['id'=>$rxId]);
        }

        audit_log('items_dispensed','prescription',$rxId,['count'=>$dispensed]);
        flash_set('success',$dispensed.' item'.($dispensed!=1?'s':'').' marked as dispensed.');
        redirect('/pages/dispenser/dispense.php?rx_id='.$rxId);
    }
}

// Reload items
$items = Database::fetchAll(
    "SELECT pi.*, p.name prod_name FROM prescription_items pi
     JOIN products p ON p.id=pi.product_id
     WHERE pi.prescription_id=? ORDER BY pi.id",
    [$rxId]
);

$patient = Database::fetchOne("SELECT * FROM users WHERE id=?",[$rx['patient_id']]);

$page_title = 'Dispense — '.$rx['prescription_ref'];
include APP_PATH . '/includes/header.php';
?>

<div class="pco-page-head">
  <div class="grid-container">
    <div class="pco-breadcrumb">
      <a href="<?= APP_URL ?>/pages/dispenser/dashboard.php">Dispensary</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <span>Dispense</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
      <div>
        <h1 class="pco-page-head__title"><?= e($rx['prescription_ref']) ?></h1>
        <p class="pco-page-head__sub"><?= e($rx['patient_name']) ?> &mdash; <?= e($rx['prescriber_name']) ?></p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <?= status_badge($rx['status']) ?>
        <button onclick="printLabels()" class="pco-btn pco-btn--outline pco-btn--sm no-print">
          <i class="fa-solid fa-print"></i> Print Labels
        </button>
      </div>
    </div>
  </div>
</div>

<div class="pco-page">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <!-- Items + dispense form -->
  <div class="cell large-7">
    <div class="pco-card">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-pills" style="color:var(--pco-purple);margin-right:.4rem;"></i>Prescription Items</h3>
      </div>
      <div class="pco-card__body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="dispense">

          <?php foreach ($items as $item): ?>
          <div class="pco-rx-item" style="position:relative;<?= $item['status']==='dispensed'?'opacity:.65;':'' ?>">
            <?php if ($item['status']==='pending'): ?>
            <label style="position:absolute;top:.8rem;right:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:600;color:var(--pco-purple);">
              <input type="checkbox" name="dispense[<?= $item['id'] ?>]" value="1"
                     style="accent-color:var(--pco-purple);width:16px;height:16px;">
              Mark dispensed
            </label>
            <?php else: ?>
            <span style="position:absolute;top:.8rem;right:.8rem;" class="pco-badge badge--green">
              <i class="fa-solid fa-check fa-xs"></i> Dispensed <?= $item['dispensed_at'] ? date('d M, H:i',strtotime($item['dispensed_at'])) : '' ?>
            </span>
            <?php endif; ?>

            <div class="pco-rx-item__name"><?= e($item['medication_name']) ?><?= $item['strength'] ? ' — '.e($item['strength']) : '' ?></div>
            <div class="pco-rx-item__detail">
              <?= e($item['dosage_form']) ?> · Qty: <?= $item['quantity'] ?> <?= e($item['quantity_unit']) ?>
              <?= $item['duration_days'] ? ' · '.e($item['duration_days']).' days' : '' ?>
            </div>
            <div style="margin-top:.4rem;font-size:.855rem;"><?= e($item['dosage_instructions']) ?></div>
            <?php if ($item['warnings']): ?>
            <div style="margin-top:.3rem;font-size:.78rem;color:var(--pco-amber);">
              <i class="fa-solid fa-triangle-exclamation"></i> <?= e($item['warnings']) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

          <?php $hasPending = count(array_filter($items, fn($i)=>$i['status']==='pending')) > 0; ?>
          <?php if ($hasPending): ?>
          <div style="margin-top:1rem;">
            <button type="submit" class="pco-btn pco-btn--primary"
                    data-confirm="Confirm: mark selected items as dispensed?">
              <i class="fa-solid fa-check"></i> Save Dispensing
            </button>
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <!-- Patient info panel -->
  <div class="cell large-5">
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head"><h3><i class="fa-solid fa-user" style="color:var(--pco-purple);margin-right:.4rem;"></i>Patient</h3></div>
      <div class="pco-card__body">
        <div style="font-weight:700;font-size:.95rem;margin-bottom:.4rem;"><?= e($rx['patient_name']) ?></div>
        <?php if ($patient): ?>
        <div style="font-size:.845rem;color:var(--pco-grey-700);">
          <?= e($patient['email']) ?><br>
          <?php if ($patient['date_of_birth']): ?>
          <?= date('d M Y',strtotime($patient['date_of_birth'])) ?> (age <?= (new DateTime())->diff(new DateTime($patient['date_of_birth']))->y ?>)<br>
          <?php endif; ?>
          <?= ucfirst($patient['gender'] ?? '') ?>
        </div>
        <?php endif; ?>
        <?php if ($rx['line1']): ?>
        <div style="margin-top:.75rem;font-size:.845rem;color:var(--pco-grey-700);border-top:1px solid var(--pco-grey-200);padding-top:.75rem;">
          <strong>Delivery address:</strong><br>
          <?= e($rx['line1']) ?><?= $rx['line2']?', '.e($rx['line2']):'' ?><br>
          <?= e($rx['city']) ?>, <?= e($rx['postcode']) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="pco-card">
      <div class="pco-card__head"><h3><i class="fa-solid fa-info-circle" style="color:var(--pco-purple);margin-right:.4rem;"></i>Prescription Details</h3></div>
      <div class="pco-card__body" style="font-size:.875rem;">
        <?php foreach ([
          ['Ref',       $rx['prescription_ref']],
          ['Prescriber',$rx['prescriber_name']],
          ['Issued',    date('d M Y',strtotime($rx['issue_date']))],
          ['Expires',   date('d M Y',strtotime($rx['expiry_date']))],
        ] as [$lbl,$val]): ?>
        <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--pco-grey-100);">
          <span style="color:var(--pco-grey-500);"><?= $lbl ?></span>
          <span style="font-weight:600;"><?= e($val) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($rx['clinical_notes']): ?>
        <div style="margin-top:.75rem;font-size:.8rem;color:var(--pco-grey-700);">
          <strong>Clinical notes:</strong><br><?= e($rx['clinical_notes']) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>
</div>
</div>

<!-- ── PRINT-ONLY LABELS ─────────────────────────────────────────── -->
<div class="print-area" style="display:none;">
  <?php foreach ($items as $item): ?>
  <div class="pco-label">
    <div class="pco-label__head">
      <?= e(get_setting('pharmacy_name','Prescribe & Co.')) ?>
      <span class="pco-label__ref"><?= e($rx['prescription_ref']) ?></span>
    </div>
    <div class="pco-label__patient"><?= e($rx['patient_name']) ?></div>
    <div class="pco-label__med"><?= e($item['medication_name']) ?> <?= e($item['strength']) ?></div>
    <div class="pco-label__dosage"><?= e($item['dosage_instructions']) ?></div>
    <div class="pco-label__qty">Quantity: <?= $item['quantity'] ?> <?= e($item['quantity_unit']) ?></div>
    <div class="pco-label__date">
      Issued: <?= date('d M Y',strtotime($rx['issue_date'])) ?> &nbsp;|&nbsp;
      Expires: <?= date('d M Y',strtotime($rx['expiry_date'])) ?>
    </div>
    <div class="pco-label__warn">
      <?= e(get_setting('label_footer','Keep out of reach of children. Store below 25°C.')) ?>
      <?php if ($item['warnings']): ?> | <?= e($item['warnings']) ?><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
window.printLabels = function() {
  document.querySelector('.print-area').style.display = 'block';
  window.print();
  setTimeout(() => document.querySelector('.print-area').style.display = 'none', 800);
};
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
