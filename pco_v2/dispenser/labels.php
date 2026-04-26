<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('dispenser','admin');
$uid  = current_user_id();
$user = current_user();

$rxId = (int)($_GET['rx_id'] ?? 0);
if (!$rxId) redirect('/pages/dispenser/dashboard.php');

$rx = Database::fetchOne(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name, u.date_of_birth,
            CONCAT(pr.first_name,' ',pr.last_name) prescriber_name
     FROM prescriptions p
     JOIN users u  ON u.id=p.patient_id
     JOIN users pr ON pr.id=p.prescriber_id
     WHERE p.id=?", [$rxId]
);
if (!$rx) { http_response_code(404); die('Prescription not found.'); }

$items = Database::fetchAll(
    "SELECT pi.*, p.name prod_name FROM prescription_items pi
     JOIN products p ON p.id=pi.product_id
     WHERE pi.prescription_id=? ORDER BY pi.id", [$rxId]
);

// Log label print
Database::insert('dispensing_labels',[
    'prescription_id'      => $rxId,
    'prescription_item_id' => $items[0]['id'] ?? 0,
    'patient_id'           => $rx['patient_id'],
    'generated_by'         => $uid,
    'printed_at'           => date('Y-m-d H:i:s'),
    'label_data_json'      => json_encode(['rx_ref'=>$rx['prescription_ref'],'items'=>count($items),'printed_by'=>$uid]),
]);

$pharmacy = get_setting('pharmacy_name','Prescribe & Co.');
$footer   = get_setting('label_footer_text','Keep out of reach of children. Store below 25°C.');
$gphc     = GPHC_NUMBER;
$patAge   = $rx['date_of_birth'] ? (new DateTime())->diff(new DateTime($rx['date_of_birth']))->y : null;

// Auto-print on load
$page_title = 'Print Labels — '.$rx['prescription_ref'];
include APP_PATH . '/includes/header.php';
?>

<div style="padding:1.5rem;" class="no-print">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
    <div>
      <h1 style="font-size:1.4rem;margin-bottom:.2rem;">Dispensing Labels</h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;"><?= e($rx['prescription_ref']) ?> · <?= e($rx['patient_name']) ?> · <?= count($items) ?> item<?= count($items)!=1?'s':'' ?></p>
    </div>
    <div style="display:flex;gap:.5rem;">
      <button onclick="window.print()" class="pco-btn pco-btn--primary"><i class="fa-solid fa-print"></i> Print Labels</button>
      <a href="<?= APP_URL ?>/pages/dispenser/dispense.php?rx_id=<?= $rxId ?>" class="pco-btn pco-btn--ghost">Back</a>
    </div>
  </div>
  <div class="pco-alert pco-alert--info">
    <i class="fa-solid fa-circle-info"></i>
    <span>Print on 89mm × 38mm label paper. Labels are sized for standard dispensary labels. <?= count($items) ?> label<?= count($items)!=1?'s':'' ?> will be printed.</span>
  </div>
</div>

<!-- Labels — only visible when printing -->
<div class="print-area" style="padding:4mm;">
  <?php foreach ($items as $item): ?>
  <div class="pco-label">
    <div class="pco-label__head">
      <?= htmlspecialchars($pharmacy) ?>
      <span class="pco-label__ref">GPhC: <?= $gphc ?></span>
    </div>
    <div class="pco-label__patient">
      <?= e($rx['patient_name']) ?><?= $patAge ? ' (Age '.$patAge.')' : '' ?>
    </div>
    <div class="pco-label__med">
      <?= e($item['medication_name']) ?><?= $item['strength'] ? ' — '.e($item['strength']) : '' ?>
    </div>
    <div class="pco-label__dosage"><?= e($item['dosage_instructions']) ?></div>
    <div class="pco-label__qty">Qty: <?= $item['quantity'] ?> <?= e($item['quantity_unit']) ?><?= $item['duration_days'] ? ' · '.$item['duration_days'].' day supply' : '' ?></div>
    <div class="pco-label__date">
      Rx: <?= e($rx['prescription_ref']) ?> &nbsp;|&nbsp;
      Issued: <?= date('d/m/Y',strtotime($rx['issue_date'])) ?> &nbsp;|&nbsp;
      Expires: <?= date('d/m/Y',strtotime($rx['expiry_date'])) ?>
    </div>
    <div class="pco-label__date">Prescriber: <?= e($rx['prescriber_name']) ?></div>
    <?php if ($item['warnings']): ?>
    <div class="pco-label__warn"><strong>Warning:</strong> <?= e($item['warnings']) ?></div>
    <?php endif; ?>
    <div class="pco-label__warn"><?= e($footer) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<style>
@media screen {
  .print-area { display: none; }
  .pco-label  { display: none; }
}
@media print {
  body > *         { display: none !important; }
  .print-area      { display: block !important; }
  .print-area *    { visibility: visible !important; }
  .pco-label       { display: inline-block !important; }
  .no-print        { display: none !important; }
}
</style>

<?php include APP_PATH . '/includes/footer.php'; ?>
