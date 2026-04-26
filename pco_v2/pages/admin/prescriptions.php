<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user   = current_user();
$status = clean($_GET['status'] ?? '');
$search = clean($_GET['q']      ?? '');
$page   = max(1,(int)($_GET['page']??1));
$per    = 20;

$where  = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND p.status=?"; $params[] = $status; }
if ($search) { $where .= " AND (p.prescription_ref LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)"; $params = array_merge($params,["%$search%","%$search%"]); }

$total = Database::fetchOne("SELECT COUNT(*) c FROM prescriptions p JOIN users u ON u.id=p.patient_id $where",$params)['c'];
$pg    = paginate($total,$page,$per);
$rxs   = Database::fetchAll(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) patient_name,
            CONCAT(pr.first_name,' ',pr.last_name) prescriber_name
     FROM prescriptions p
     JOIN users u  ON u.id=p.patient_id
     JOIN users pr ON pr.id=p.prescriber_id
     $where ORDER BY p.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}", $params
);

$page_title = 'All Prescriptions';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.25rem;">All Prescriptions</h1>

  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem;">
    <?php foreach ([''=> 'All','active'=>'Active','dispensed'=>'Dispensed','expired'=>'Expired','cancelled'=>'Cancelled'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="pco-btn pco-btn--sm <?= $status===$v?'pco-btn--primary':'pco-btn--ghost' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.4rem;">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Ref or patient…" style="max-width:280px;">
      <button type="submit" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-search"></i></button>
    </form>
  </div>
  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Reference</th><th>Patient</th><th>Prescriber</th><th>Issued</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rxs as $rx): ?>
          <tr>
            <td><strong style="font-family:monospace;color:var(--pco-purple);"><?= e($rx['prescription_ref']) ?></strong></td>
            <td style="font-size:.845rem;"><?= e($rx['patient_name']) ?></td>
            <td style="font-size:.845rem;"><?= e($rx['prescriber_name']) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($rx['issue_date'])) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($rx['expiry_date'])) ?></td>
            <td><?= status_badge($rx['status']) ?></td>
            <td>
              <a href="<?= APP_URL ?>/pages/dispenser/dispense.php?rx_id=<?= $rx['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Dispense</a>
            </td>
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
</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
