<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

$search     = clean($_GET['q']      ?? '');
$actionF    = clean($_GET['action'] ?? '');
$entityF    = clean($_GET['entity'] ?? '');
$dateFrom   = clean($_GET['from']   ?? '');
$dateTo     = clean($_GET['to']     ?? '');
$page       = max(1,(int)($_GET['page']??1));
$per        = 25;

$where  = "WHERE 1=1";
$params = [];
if ($search)   { $where .= " AND (al.action LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
if ($actionF)  { $where .= " AND al.action=?"; $params[] = $actionF; }
if ($entityF)  { $where .= " AND al.entity_type=?"; $params[] = $entityF; }
if ($dateFrom) { $where .= " AND DATE(al.created_at)>=?"; $params[] = $dateFrom; }
if ($dateTo)   { $where .= " AND DATE(al.created_at)<=?"; $params[] = $dateTo; }

$total = Database::fetchOne(
    "SELECT COUNT(*) c FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id $where", $params)['c'];
$pg    = paginate($total,$page,$per);
$logs  = Database::fetchAll(
    "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) user_name, u.email user_email, u.role user_role
     FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id
     $where ORDER BY al.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}", $params
);

// Get distinct actions for filter
$actions = Database::fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$entities = Database::fetchAll("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type");

$page_title = 'Audit Log';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">Audit Log</h1>

  <!-- Filters -->
  <div class="pco-card" style="margin-bottom:1.25rem;">
    <div class="pco-card__body">
      <form method="GET">
        <div class="grid-x grid-margin-x align-bottom">
          <div class="cell medium-4">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Search</label>
              <input type="text" name="q" value="<?= e($search) ?>" placeholder="Action or user email…">
            </div>
          </div>
          <div class="cell medium-2">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Action</label>
              <select name="action">
                <option value="">All</option>
                <?php foreach ($actions as $a): ?>
                <option value="<?= e($a['action']) ?>" <?= $actionF===$a['action']?'selected':'' ?>><?= e($a['action']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-2">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>Entity</label>
              <select name="entity">
                <option value="">All</option>
                <?php foreach ($entities as $e): ?>
                <option value="<?= e($e['entity_type']) ?>" <?= $entityF===$e['entity_type']?'selected':'' ?>><?= e($e['entity_type']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-2">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>From</label>
              <input type="date" name="from" value="<?= e($dateFrom) ?>">
            </div>
          </div>
          <div class="cell medium-2">
            <div class="pco-form-group" style="margin-bottom:0;">
              <label>To</label>
              <input type="date" name="to" value="<?= e($dateTo) ?>">
            </div>
          </div>
        </div>
        <div style="margin-top:.9rem;display:flex;gap:.4rem;">
          <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-filter"></i> Filter</button>
          <a href="?" class="pco-btn pco-btn--ghost pco-btn--sm">Clear</a>
          <span style="font-size:.8rem;color:var(--pco-grey-500);margin-left:auto;align-self:center;"><?= number_format($total) ?> event<?= $total!=1?'s':'' ?></span>
        </div>
      </form>
    </div>
  </div>

  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Time</th><th>Action</th><th>User</th><th>Entity</th><th>IP</th><th>Details</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td style="font-size:.78rem;white-space:nowrap;color:var(--pco-grey-500);"><?= date('d M H:i',strtotime($log['created_at'])) ?></td>
            <td><code style="font-size:.75rem;background:var(--pco-grey-100);padding:2px 6px;border-radius:4px;"><?= e($log['action']) ?></code></td>
            <td style="font-size:.82rem;">
              <?php if ($log['user_name']): ?>
              <div><?= e($log['user_name']) ?></div>
              <div style="font-size:.75rem;color:var(--pco-grey-500);"><?= e($log['user_email']) ?></div>
              <?php else: ?>
              <span style="color:var(--pco-grey-400);">System</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;"><?= e($log['entity_type']) ?><?= $log['entity_id']?' #'.$log['entity_id']:'' ?></td>
            <td style="font-size:.75rem;font-family:monospace;color:var(--pco-grey-500);"><?= e($log['ip_address']) ?></td>
            <td style="font-size:.78rem;max-width:200px;">
              <?php if ($log['details_json']): ?>
              <?php $det = json_decode($log['details_json'],true); foreach (array_slice($det,0,3) as $k=>$v): ?>
              <span style="color:var(--pco-grey-500);"><?= e($k) ?>:</span> <?= e(is_array($v)?json_encode($v):$v) ?><br>
              <?php endforeach; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);">Page <?= $page ?> of <?= $pg['pages'] ?></span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
