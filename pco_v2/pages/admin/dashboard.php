<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');

$user = current_user();

$stats = [
    'patients'     => Database::fetchOne("SELECT COUNT(*) c FROM users WHERE role='patient'")['c'],
    'consultations'=> Database::fetchOne("SELECT COUNT(*) c FROM consultations")['c'],
    'prescriptions'=> Database::fetchOne("SELECT COUNT(*) c FROM prescriptions")['c'],
    'pending'      => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='submitted'")['c'],
];

$recentAudit = Database::fetchAll(
    "SELECT al.*, CONCAT(u.first_name,' ',u.last_name) user_name
     FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id
     ORDER BY al.created_at DESC LIMIT 15"
);

$userList = Database::fetchAll(
    "SELECT id, email, role, first_name, last_name, is_active, created_at
     FROM users ORDER BY created_at DESC LIMIT 10"
);

$page_title = 'Admin Dashboard';
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <?php include APP_PATH . '/includes/admin-sidebar.php'; ?>

  <!-- Main -->
  <div class="cell large-9 medium-8">

    <div style="margin-bottom:1.75rem;">
      <h1 style="font-size:1.55rem;margin-bottom:.2rem;">Administration</h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;">Platform overview — <?= date('l, d F Y') ?></p>
    </div>

    <!-- Stats -->
    <div class="grid-x grid-margin-x" style="margin-bottom:1.5rem;">
      <?php foreach ([
        ['users','black',  $stats['patients'],     'Registered Patients'],
        ['clipboard-list','purple',$stats['consultations'],'Total Consultations'],
        ['file-prescription','green',$stats['prescriptions'],'Prescriptions Issued'],
        ['inbox','amber',  $stats['pending'],       'Pending Review'],
      ] as [$ico,$mod,$val,$lbl]): ?>
      <div class="cell large-3 medium-6 small-6" style="margin-bottom:1rem;">
        <div class="pco-stat">
          <div class="pco-stat__icon pco-stat__icon--<?= $mod ?>"><i class="fa-solid fa-<?= $ico ?>"></i></div>
          <div>
            <div class="pco-stat__val"><?= $val ?></div>
            <div class="pco-stat__label"><?= $lbl ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pending alert -->
    <?php if ($stats['pending'] > 0): ?>
    <div class="pco-alert pco-alert--warning" style="margin-bottom:1.5rem;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <div>
        <strong><?= $stats['pending'] ?> consultation<?= $stats['pending']!=1?'s':'' ?> awaiting prescriber review.</strong>
        <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php" style="color:var(--pco-amber);text-decoration:underline;margin-left:.4rem;">View queue</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent users -->
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-users" style="color:var(--pco-purple);margin-right:.4rem;"></i>Recent Users</h3>
        <a href="<?= APP_URL ?>/pages/admin/users.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
          <tbody>
            <?php foreach ($userList as $u): ?>
            <tr>
              <td data-label="Name"><strong><?= e($u['first_name'].' '.$u['last_name']) ?></strong></td>
              <td data-label="Email" style="font-size:.82rem;"><?= e($u['email']) ?></td>
              <td data-label="Role"><?= status_badge($u['role']) ?></td>
              <td data-label="Status">
                <?php if ($u['is_active']): ?>
                <span class="pco-badge badge--green">Active</span>
                <?php else: ?>
                <span class="pco-badge badge--red">Inactive</span>
                <?php endif; ?>
              </td>
              <td data-label="Joined" style="font-size:.82rem;"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Audit log -->
    <div class="pco-card">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-scroll" style="color:var(--pco-purple);margin-right:.4rem;"></i>Recent Audit Activity</h3>
        <a href="<?= APP_URL ?>/pages/admin/audit-log.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Action</th><th>User</th><th>Entity</th><th>IP</th><th>Time</th></tr></thead>
          <tbody>
            <?php foreach ($recentAudit as $log): ?>
            <tr>
              <td data-label="Action"><code style="font-size:.78rem;background:var(--pco-grey-100);padding:2px 6px;border-radius:4px;"><?= e($log['action']) ?></code></td>
              <td data-label="User" style="font-size:.82rem;"><?= e($log['user_name'] ?? 'System') ?></td>
              <td data-label="Entity" style="font-size:.82rem;"><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #'.$log['entity_id'] : '' ?></td>
              <td data-label="IP" style="font-size:.78rem;color:var(--pco-grey-500);font-family:monospace;"><?= e($log['ip_address']) ?></td>
              <td data-label="Time" style="font-size:.82rem;"><?= time_ago($log['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
