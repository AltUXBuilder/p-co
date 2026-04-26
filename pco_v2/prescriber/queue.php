<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('prescriber','admin');
$user = current_user();

$status  = clean($_GET['status']    ?? 'submitted');
$condId  = (int)($_GET['condition'] ?? 0);
$search  = clean($_GET['q']         ?? '');
$page    = max(1,(int)($_GET['page']??1));
$per     = 20;

$where  = "WHERE 1=1";
$params = [];
if ($status)  { $where .= " AND c.status=?"; $params[] = $status; }
if ($condId)  { $where .= " AND c.condition_id=?"; $params[] = $condId; }
if ($search)  { $where .= " AND (CONCAT(u.first_name,' ',u.last_name) LIKE ? OR u.email LIKE ?)"; $params = array_merge($params,["%$search%","%$search%"]); }

$total = Database::fetchOne(
    "SELECT COUNT(*) c FROM consultations c JOIN users u ON u.id=c.patient_id $where", $params)['c'];
$pg    = paginate($total,$page,$per);

$consultations = Database::fetchAll(
    "SELECT c.*, cn.name cond_name,
            CONCAT(u.first_name,' ',u.last_name) patient_name,
            u.date_of_birth, u.gender, u.email patient_email
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     JOIN users u ON u.id=c.patient_id
     $where ORDER BY c.submitted_at ASC LIMIT {$per} OFFSET {$pg['offset']}",
    $params
);

$conditions = Database::fetchAll("SELECT id, name FROM conditions WHERE is_active=1 ORDER BY sort_order");
$pending    = Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='submitted'")['c'];

$page_title = 'Consultation Queue';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container">

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
      <h1 style="font-size:1.55rem;margin-bottom:.15rem;">Consultation Queue</h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;">
        <?php if ($pending > 0): ?><strong style="color:var(--pco-amber);"><?= $pending ?> pending review</strong><?php else: ?>All caught up<?php endif; ?>
      </p>
    </div>
    <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php" class="pco-btn pco-btn--ghost pco-btn--sm">
      <i class="fa-solid fa-arrow-left"></i> Dashboard
    </a>
  </div>

  <!-- Filters -->
  <div class="pco-card" style="margin-bottom:1.25rem;">
    <div class="pco-card__body">
      <form method="GET">
        <div class="grid-x grid-margin-x align-bottom">
          <div class="cell medium-2">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Status</label>
              <select name="status">
                <?php foreach (['submitted'=>'Pending','approved'=>'Approved','rejected'=>'Rejected',''=>'All'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-3">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Condition</label>
              <select name="condition">
                <option value="">All conditions</option>
                <?php foreach ($conditions as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $condId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Search patient</label>
              <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or email…">
            </div>
          </div>
          <div class="cell medium-3">
            <div style="display:flex;gap:.4rem;">
              <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
              <a href="?" class="pco-btn pco-btn--ghost pco-btn--sm">Clear</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="pco-card">
    <?php if (empty($consultations)): ?>
    <div class="pco-card__body text-center" style="padding:3rem;">
      <i class="fa-solid fa-circle-check" style="font-size:2rem;color:var(--pco-green);display:block;margin-bottom:.75rem;"></i>
      <p style="color:var(--pco-grey-500);margin:0;">No consultations match these filters.</p>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Patient</th><th>Condition</th><th>Age/Sex</th><th>Status</th><th>Submitted</th><th>Waiting</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($consultations as $c):
            $age  = $c['date_of_birth'] ? (new DateTime())->diff(new DateTime($c['date_of_birth']))->y : '—';
            $wait = $c['submitted_at']  ? (new DateTime())->diff(new DateTime($c['submitted_at']))->h : null;
            $urgent = $wait !== null && $wait > 4;
          ?>
          <tr>
            <td data-label="Patient">
              <strong><?= e($c['patient_name']) ?></strong>
              <div style="font-size:.78rem;color:var(--pco-grey-500);"><?= e($c['patient_email']) ?></div>
            </td>
            <td data-label="Condition"><?= e($c['cond_name']) ?></td>
            <td data-label="Age/Sex"><?= $age ?> / <?= e(ucfirst($c['gender']??'—')) ?></td>
            <td data-label="Status"><?= status_badge($c['status']) ?></td>
            <td data-label="Submitted" style="font-size:.82rem;"><?= $c['submitted_at'] ? date('d M H:i',strtotime($c['submitted_at'])) : '—' ?></td>
            <td data-label="Waiting">
              <?php if ($c['status']==='submitted' && $wait !== null): ?>
              <span style="font-size:.82rem;<?= $urgent?'color:var(--pco-amber);font-weight:600;':'' ?>">
                <?= $urgent ? '<i class="fa-solid fa-triangle-exclamation"></i> ' : '' ?><?= time_ago($c['submitted_at']) ?>
              </span>
              <?php else: echo '—'; endif; ?>
            </td>
            <td>
              <?php if ($c['status']==='submitted'): ?>
              <a href="<?= APP_URL ?>/pages/prescriber/review.php?id=<?= $c['id'] ?>" class="pco-btn pco-btn--primary pco-btn--sm">Review</a>
              <?php else: ?>
              <a href="<?= APP_URL ?>/pages/patient/consultation-view.php?id=<?= $c['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);"><?= $total ?> result<?= $total!=1?'s':'' ?></span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
