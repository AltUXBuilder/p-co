<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid    = current_user_id();
$user   = current_user();
$status = clean($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$per    = 10;

$where  = "WHERE p.patient_id=?";
$params = [$uid];
if ($status) { $where .= " AND p.status=?"; $params[] = $status; }

$total = Database::fetchOne("SELECT COUNT(*) c FROM prescriptions p $where", $params)['c'];
$pg    = paginate($total, $page, $per);

$prescriptions = Database::fetchAll(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) prescriber_name,
            COUNT(pi.id) item_count
     FROM prescriptions p
     JOIN users u ON u.id=p.prescriber_id
     LEFT JOIN prescription_items pi ON pi.prescription_id=p.id
     $where GROUP BY p.id
     ORDER BY p.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}",
    $params
);

$page_title = 'My Prescriptions';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">

<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>

<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">My Prescriptions</h1>

  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.5rem;">
    <?php foreach ([''=> 'All','active'=>'Active','dispensed'=>'Dispensed','expired'=>'Expired','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
    <a href="?status=<?= $val ?>" class="pco-btn pco-btn--sm <?= $status===$val?'pco-btn--primary':'pco-btn--ghost' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <div class="pco-card">
    <?php if (empty($prescriptions)): ?>
    <div class="pco-card__body text-center" style="padding:3rem;">
      <i class="fa-solid fa-file-prescription" style="font-size:2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
      <p style="color:var(--pco-grey-500);margin:0;">No prescriptions yet.</p>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Reference</th><th>Prescriber</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($prescriptions as $rx): ?>
          <?php $expiring = $rx['status']==='active' && strtotime($rx['expiry_date']) < strtotime('+7 days'); ?>
          <tr>
            <td data-label="Ref"><strong style="font-family:monospace;color:var(--pco-purple);"><?= e($rx['prescription_ref']) ?></strong></td>
            <td data-label="Prescriber" style="font-size:.85rem;"><?= e($rx['prescriber_name']) ?></td>
            <td data-label="Issued" style="font-size:.85rem;"><?= date('d M Y',strtotime($rx['issue_date'])) ?></td>
            <td data-label="Expires" style="font-size:.85rem;<?= $expiring?'color:var(--pco-amber);font-weight:600;':'' ?>">
              <?= date('d M Y',strtotime($rx['expiry_date'])) ?>
              <?= $expiring ? ' <i class="fa-solid fa-triangle-exclamation fa-xs"></i>' : '' ?>
            </td>
            <td data-label="Status"><?= status_badge($rx['status']) ?></td>
            <td>
              <a href="<?= APP_URL ?>/pages/patient/prescription-view.php?id=<?= $rx['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);">Showing <?= $pg['offset']+1 ?>–<?= min($pg['offset']+$per,$total) ?> of <?= $total ?></span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?status=<?= $status ?>&page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?status=<?= $status ?>&page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

</div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
