<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('prescriber','admin');
$uid  = current_user_id();
$user = current_user();

$viewId = (int)($_GET['id'] ?? 0);

if ($viewId) {
    // Single prescription view
    $rx = Database::fetchOne(
        "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name,
                u.email patient_email, u.date_of_birth, u.gender
         FROM prescriptions p JOIN users u ON u.id=p.patient_id
         WHERE p.id=? AND p.prescriber_id=?", [$viewId,$uid]
    );
    if (!$rx) redirect('/pages/prescriber/prescriptions.php');

    $items  = Database::fetchAll("SELECT * FROM prescription_items WHERE prescription_id=?",[$viewId]);
    $consult= Database::fetchOne("SELECT c.*, cn.name cond_name FROM consultations c JOIN conditions cn ON cn.id=c.condition_id WHERE c.id=?",[$rx['consultation_id']]);
    $orders = Database::fetchAll("SELECT * FROM orders WHERE prescription_id=? ORDER BY created_at DESC",[$viewId]);

    $page_title = $rx['prescription_ref'];
    include APP_PATH . '/includes/header.php';
    ?>
<div class="pco-dash"><div class="grid-container">
  <div class="pco-breadcrumb" style="margin:1rem 0 .5rem;">
    <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php">Dashboard</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <a href="<?= APP_URL ?>/pages/prescriber/prescriptions.php">Prescriptions</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <span><?= e($rx['prescription_ref']) ?></span>
  </div>

  <div class="grid-x grid-margin-x">
    <div class="cell large-8">
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
            <span><i class="fa-solid fa-user"></i> <?= e($rx['patient_name']) ?></span>
            <?php if ($consult): ?><span><i class="fa-solid fa-stethoscope"></i> <?= e($consult['cond_name']) ?></span><?php endif; ?>
            <span><i class="fa-solid fa-calendar"></i> <?= date('d M Y',strtotime($rx['issue_date'])) ?></span>
            <span><i class="fa-solid fa-calendar-xmark"></i> <?= date('d M Y',strtotime($rx['expiry_date'])) ?></span>
          </div>
        </div>
        <div class="pco-card__body">
          <?php foreach ($items as $item): ?>
          <div class="pco-rx-item">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
              <div>
                <div class="pco-rx-item__name"><?= e($item['medication_name']) ?><?= $item['strength']?' — '.e($item['strength']):'' ?></div>
                <div class="pco-rx-item__detail"><?= e($item['dosage_form']) ?> · <?= $item['quantity'] ?> <?= e($item['quantity_unit']) ?><?= $item['duration_days']?' · '.$item['duration_days'].' days':'' ?></div>
              </div>
              <?= status_badge($item['status']) ?>
            </div>
            <p style="margin:.4rem 0 0;font-size:.855rem;"><?= e($item['dosage_instructions']) ?></p>
            <?php if ($item['warnings']): ?><div style="margin-top:.3rem;font-size:.78rem;color:var(--pco-amber);"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($item['warnings']) ?></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php if ($rx['clinical_notes']): ?>
          <div style="margin-top:1rem;padding:1rem;background:var(--pco-grey-50);border-radius:var(--pco-r-lg);">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin-bottom:.4rem;">Clinical Notes</div>
            <p style="margin:0;font-size:.875rem;"><?= e($rx['clinical_notes']) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="cell large-4">
      <div class="pco-card" style="margin-bottom:1rem;">
        <div class="pco-card__head"><h3>Patient</h3></div>
        <div class="pco-card__body" style="font-size:.875rem;">
          <div style="font-weight:700;margin-bottom:.3rem;"><?= e($rx['patient_name']) ?></div>
          <div style="color:var(--pco-grey-600);"><?= e($rx['patient_email']) ?></div>
          <?php if ($rx['date_of_birth']): ?>
          <div style="margin-top:.4rem;color:var(--pco-grey-500);">Age <?= (new DateTime())->diff(new DateTime($rx['date_of_birth']))->y ?> · <?= ucfirst($rx['gender']??'') ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($orders)): ?>
      <div class="pco-card">
        <div class="pco-card__head"><h3>Orders</h3></div>
        <div class="pco-card__body">
          <?php foreach ($orders as $o): ?>
          <div style="display:flex;justify-content:space-between;font-size:.845rem;padding:.3rem 0;">
            <span><?= e($o['order_ref']) ?></span><?= status_badge($o['status']) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div></div>
    <?php include APP_PATH . '/includes/footer.php'; ?>
    <?php exit; }

// List view
$status = clean($_GET['status'] ?? '');
$search = clean($_GET['q']      ?? '');
$page   = max(1,(int)($_GET['page']??1));
$per    = 15;

$where  = "WHERE p.prescriber_id=?";
$params = [$uid];
if ($status) { $where .= " AND p.status=?"; $params[] = $status; }
if ($search) { $where .= " AND (p.prescription_ref LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)"; $params = array_merge($params,["%$search%","%$search%"]); }

$total = Database::fetchOne("SELECT COUNT(*) c FROM prescriptions p JOIN users u ON u.id=p.patient_id $where",$params)['c'];
$pg    = paginate($total,$page,$per);
$rxs   = Database::fetchAll(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name
     FROM prescriptions p JOIN users u ON u.id=p.patient_id
     $where ORDER BY p.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}", $params
);

$page_title = 'My Prescriptions';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">My Issued Prescriptions</h1>

  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem;">
    <?php foreach ([''=> 'All','active'=>'Active','dispensed'=>'Dispensed','expired'=>'Expired','cancelled'=>'Cancelled'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="pco-btn pco-btn--sm <?= $status===$v?'pco-btn--primary':'pco-btn--ghost' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.4rem;">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Ref or patient name…" style="max-width:280px;">
      <button type="submit" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-search"></i></button>
    </form>
  </div>

  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Reference</th><th>Patient</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rxs as $rx): ?>
          <tr>
            <td><strong style="font-family:monospace;color:var(--pco-purple);"><?= e($rx['prescription_ref']) ?></strong></td>
            <td><?= e($rx['patient_name']) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($rx['issue_date'])) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($rx['expiry_date'])) ?></td>
            <td><?= status_badge($rx['status']) ?></td>
            <td><a href="?id=<?= $rx['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);"><?= $total ?> prescriptions</span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?status=<?= e($status) ?>&q=<?= e($search) ?>&page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?status=<?= e($status) ?>&q=<?= e($search) ?>&page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
