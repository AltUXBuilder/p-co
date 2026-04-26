<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid    = current_user_id();
$user   = current_user();
$status = clean($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$per    = 10;

$where  = "WHERE c.patient_id=?";
$params = [$uid];
if ($status) { $where .= " AND c.status=?"; $params[] = $status; }

$total = Database::fetchOne("SELECT COUNT(*) c FROM consultations c $where", $params)['c'];
$pg    = paginate($total, $page, $per);

$consultations = Database::fetchAll(
    "SELECT c.*, cn.name cond_name
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     $where ORDER BY c.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}",
    $params
);

$page_title = 'My Consultations';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">

<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>

<div class="cell large-9 medium-8">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">My Consultations</h1>
    <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--primary pco-btn--sm">
      <i class="fa-solid fa-plus"></i> New consultation
    </a>
  </div>

  <!-- Status filters -->
  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.5rem;">
    <?php foreach (['' => 'All', 'submitted' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'draft' => 'Draft'] as $val => $lbl): ?>
    <a href="?status=<?= $val ?>" class="pco-btn pco-btn--sm <?= $status===$val ? 'pco-btn--primary' : 'pco-btn--ghost' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <div class="pco-card">
    <?php if (empty($consultations)): ?>
    <div class="pco-card__body text-center" style="padding:3rem;">
      <i class="fa-solid fa-clipboard" style="font-size:2.2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
      <p style="color:var(--pco-grey-500);margin:0 0 1rem;">No consultations found.</p>
      <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--primary pco-btn--sm">Browse treatments</a>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Condition</th><th>Status</th><th>Submitted</th><th>Updated</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($consultations as $c): ?>
          <tr>
            <td data-label="Condition"><strong><?= e($c['cond_name']) ?></strong></td>
            <td data-label="Status"><?= status_badge($c['status']) ?></td>
            <td data-label="Submitted"><?= $c['submitted_at'] ? date('d M Y',strtotime($c['submitted_at'])) : '<em style="color:var(--pco-grey-500)">Draft</em>' ?></td>
            <td data-label="Updated" style="font-size:.82rem;color:var(--pco-grey-500);"><?= time_ago($c['updated_at']) ?></td>
            <td><a href="<?= APP_URL ?>/pages/patient/consultation-view.php?id=<?= $c['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages'] > 1): ?>
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
