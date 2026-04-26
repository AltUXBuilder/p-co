<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('dispenser','admin');
$user = current_user();
$uid  = current_user_id();

$from   = clean($_GET['from'] ?? date('Y-m-d'));
$to     = clean($_GET['to']   ?? date('Y-m-d'));
$page   = max(1,(int)($_GET['page']??1));
$per    = 20;

$total  = Database::fetchOne(
    "SELECT COUNT(*) c FROM prescription_items pi
     WHERE pi.dispensed_by=? AND DATE(pi.dispensed_at) BETWEEN ? AND ?", [$uid,$from,$to])['c'];
$pg     = paginate($total,$page,$per);

$items  = Database::fetchAll(
    "SELECT pi.*, p.prescription_ref, CONCAT(u.first_name,' ',u.last_name) patient_name
     FROM prescription_items pi
     JOIN prescriptions p ON p.id=pi.prescription_id
     JOIN users u ON u.id=p.patient_id
     WHERE pi.dispensed_by=? AND DATE(pi.dispensed_at) BETWEEN ? AND ?
     ORDER BY pi.dispensed_at DESC LIMIT {$per} OFFSET {$pg['offset']}",
    [$uid,$from,$to]
);

$page_title = 'Dispensing History';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">

  <div class="cell large-3 medium-4">
    <div class="pco-sidebar" style="margin-bottom:1.5rem;">
      <div class="pco-sidebar__top">
        <div class="pco-sidebar__avatar"><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?></div>
        <div class="pco-sidebar__name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
        <div class="pco-sidebar__role">Dispenser</div>
      </div>
      <div class="pco-sidebar__nav">
        <a href="<?= APP_URL ?>/pages/dispenser/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="<?= APP_URL ?>/pages/dispenser/dispense.php"><i class="fa-solid fa-pills"></i> Dispense Queue</a>
        <a href="<?= APP_URL ?>/pages/dispenser/history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        <div class="sep"></div>
        <a href="<?= APP_URL ?>/pages/auth/logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
      </div>
    </div>
  </div>

  <div class="cell large-9 medium-8">
    <h1 style="font-size:1.55rem;margin-bottom:1.25rem;">My Dispensing History</h1>

    <form method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1.25rem;">
      <div>
        <label style="font-size:.78rem;font-weight:700;display:block;margin-bottom:.25rem;">From</label>
        <input type="date" name="from" value="<?= e($from) ?>" style="font-size:.845rem;padding:.4rem .6rem;">
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:700;display:block;margin-bottom:.25rem;">To</label>
        <input type="date" name="to" value="<?= e($to) ?>" style="font-size:.845rem;padding:.4rem .6rem;">
      </div>
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm">Apply</button>
      <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Today</a>
      <span style="font-size:.8rem;color:var(--pco-grey-500);margin-left:auto;"><?= $total ?> item<?= $total!=1?'s':'' ?> dispensed</span>
    </form>

    <div class="pco-card">
      <?php if (empty($items)): ?>
      <div class="pco-card__body text-center" style="padding:2.5rem;">
        <p style="color:var(--pco-grey-500);margin:0;">No items dispensed in this period.</p>
      </div>
      <?php else: ?>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Time</th><th>Medication</th><th>Rx Ref</th><th>Patient</th><th>Qty</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td style="font-size:.82rem;white-space:nowrap;"><?= date('d M H:i',strtotime($item['dispensed_at'])) ?></td>
              <td>
                <strong><?= e($item['medication_name']) ?></strong>
                <?php if ($item['strength']): ?><span style="font-size:.8rem;color:var(--pco-grey-500);"> — <?= e($item['strength']) ?></span><?php endif; ?>
              </td>
              <td style="font-family:monospace;font-size:.82rem;color:var(--pco-purple);"><?= e($item['prescription_ref']) ?></td>
              <td style="font-size:.845rem;"><?= e($item['patient_name']) ?></td>
              <td><?= $item['dispensed_qty'] ?? $item['quantity'] ?> <?= e($item['quantity_unit']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pg['pages']>1): ?>
      <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.8rem;color:var(--pco-grey-500);">Page <?= $page ?> of <?= $pg['pages'] ?></span>
        <div style="display:flex;gap:.4rem;">
          <?php if ($pg['has_prev']): ?><a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
          <?php if ($pg['has_next']): ?><a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
