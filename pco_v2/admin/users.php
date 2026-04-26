<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');
    $uid    = (int)($_POST['user_id'] ?? 0);
    $target = $uid ? Database::fetchOne("SELECT * FROM users WHERE id=?", [$uid]) : null;

    if ($target) {
        if ($action === 'toggle_active') {
            $new = $target['is_active'] ? 0 : 1;
            Database::update('users', ['is_active' => $new], ['id' => $uid]);
            audit_log($new ? 'user_activated' : 'user_deactivated', 'user', $uid);
            flash_set('success', $new ? 'User activated.' : 'User deactivated.');
        }
        if ($action === 'change_role') {
            $role = clean($_POST['role'] ?? '');
            if (in_array($role, ['admin','prescriber','dispenser','patient'])) {
                Database::update('users', ['role' => $role], ['id' => $uid]);
                audit_log('user_role_changed', 'user', $uid, ['new_role' => $role]);
                flash_set('success', 'Role updated to ' . $role . '.');
            }
        }
        if ($action === 'reset_password') {
            $pass = clean($_POST['new_password'] ?? '');
            if (strlen($pass) >= 8) {
                Database::update('users', ['password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])], ['id' => $uid]);
                audit_log('admin_password_reset', 'user', $uid);
                flash_set('success', 'Password reset for ' . $target['email'] . '.');
            } else {
                flash_set('error', 'Password must be at least 8 characters.');
            }
        }
    }
    redirect('/pages/admin/users.php' . (isset($_GET['role']) ? '?role='.$_GET['role'] : ''));
}

$search  = clean($_GET['q']    ?? '');
$roleF   = clean($_GET['role'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 20;

$where   = "WHERE 1=1";
$params  = [];
if ($search) { $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($roleF)  { $where .= " AND role=?"; $params[] = $roleF; }

$total   = Database::fetchOne("SELECT COUNT(*) c FROM users $where", $params)['c'];
$pg      = paginate($total, $page, $per);
$users   = Database::fetchAll("SELECT * FROM users $where ORDER BY created_at DESC LIMIT {$per} OFFSET {$pg['offset']}", $params);

$roleCounts = [];
foreach (['admin','prescriber','dispenser','patient'] as $r) {
    $roleCounts[$r] = Database::fetchOne("SELECT COUNT(*) c FROM users WHERE role=?",[$r])['c'];
}

$page_title = 'Users';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Users</h1>
    <a href="<?= APP_URL ?>/pages/admin/user-edit.php?action=add" class="pco-btn pco-btn--primary pco-btn--sm">
      <i class="fa-solid fa-plus"></i> Add User
    </a>
  </div>

  <?= flash_render() ?>

  <!-- Role filter tabs -->
  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem;">
    <a href="?q=<?= e($search) ?>" class="pco-btn pco-btn--sm <?= !$roleF?'pco-btn--primary':'pco-btn--ghost' ?>">All (<?= array_sum($roleCounts) ?>)</a>
    <?php foreach ($roleCounts as $r => $cnt): ?>
    <a href="?role=<?= $r ?>&q=<?= e($search) ?>" class="pco-btn pco-btn--sm <?= $roleF===$r?'pco-btn--primary':'pco-btn--ghost' ?>">
      <?= ucfirst($r) ?> (<?= $cnt ?>)
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Search -->
  <div style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.5rem;">
      <?php if ($roleF): ?><input type="hidden" name="role" value="<?= e($roleF) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name or email…" style="max-width:300px;">
      <button type="submit" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-search"></i></button>
      <?php if ($search): ?><a href="?role=<?= e($roleF) ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="pco-card">
    <?php if (empty($users)): ?>
    <div class="pco-card__body text-center" style="padding:2.5rem;">
      <p style="color:var(--pco-grey-500);">No users found.</p>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td data-label="Name"><strong><?= e($u['first_name'].' '.$u['last_name']) ?></strong></td>
            <td data-label="Email" style="font-size:.82rem;"><?= e($u['email']) ?></td>
            <td data-label="Role"><?= status_badge($u['role']) ?></td>
            <td data-label="Status">
              <?php if ($u['is_active']): ?><span class="pco-badge badge--green">Active</span>
              <?php else: ?><span class="pco-badge badge--red">Inactive</span><?php endif; ?>
            </td>
            <td data-label="Joined" style="font-size:.82rem;"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                <a href="<?= APP_URL ?>/pages/admin/user-edit.php?id=<?= $u['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pen"></i></a>

                <!-- Role change -->
                <div style="position:relative;display:inline-block;" class="role-dropdown-wrap">
                  <button type="button" class="pco-btn pco-btn--ghost pco-btn--sm role-toggle-btn">
                    Role <i class="fa-solid fa-chevron-down fa-xs"></i>
                  </button>
                  <div class="role-dropdown" style="display:none;position:absolute;right:0;top:calc(100%+4px);background:white;border:1px solid var(--pco-grey-200);border-radius:var(--pco-r-lg);box-shadow:var(--pco-shadow-md);z-index:100;min-width:130px;padding:.3rem 0;">
                    <?php foreach (['patient','prescriber','dispenser','admin'] as $r): ?>
                    <form method="POST" style="display:block;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="change_role">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="role" value="<?= $r ?>">
                      <button type="submit" style="width:100%;text-align:left;background:none;border:none;padding:.4rem 1rem;font-size:.82rem;cursor:pointer;color:var(--pco-grey-700);<?= $u['role']===$r?'font-weight:700;color:var(--pco-purple);':'' ?>"
                              <?= $u['role']===$r?'disabled':'' ?>>
                        <?= ucfirst($r) ?>
                      </button>
                    </form>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Toggle active -->
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit"
                          class="pco-btn pco-btn--sm <?= $u['is_active']?'pco-btn--ghost':'pco-btn--outline' ?>"
                          data-confirm="<?= $u['is_active']?'Deactivate':'Activate' ?> this user?">
                    <?= $u['is_active'] ? '<i class="fa-solid fa-ban"></i>' : '<i class="fa-solid fa-check"></i>' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages'] > 1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);">Showing <?= $pg['offset']+1 ?>–<?= min($pg['offset']+$per,$total) ?> of <?= $total ?></span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?role=<?= e($roleF) ?>&q=<?= e($search) ?>&page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?role=<?= e($roleF) ?>&q=<?= e($search) ?>&page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</div></div></div></div>

<script>
document.querySelectorAll('.role-toggle-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const dd = btn.nextElementSibling;
    document.querySelectorAll('.role-dropdown').forEach(d => { if(d!==dd) d.style.display='none'; });
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
  });
});
document.addEventListener('click', () => document.querySelectorAll('.role-dropdown').forEach(d => d.style.display='none'));
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
