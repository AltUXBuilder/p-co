<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid  = current_user_id();
$user = current_user();
$page = max(1,(int)($_GET['page']??1));
$per  = 10;

$total  = Database::fetchOne("SELECT COUNT(*) c FROM orders WHERE patient_id=?",[$uid])['c'];
$pg     = paginate($total,$page,$per);
$orders = Database::fetchAll(
    "SELECT o.*, COUNT(oi.id) item_count
     FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id
     WHERE o.patient_id=? GROUP BY o.id
     ORDER BY o.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}",[$uid]);

$page_title = 'My Orders';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">My Orders</h1>
  <div class="pco-card">
    <?php if (empty($orders)): ?>
    <div class="pco-card__body text-center" style="padding:3rem;">
      <i class="fa-solid fa-box" style="font-size:2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
      <p style="color:var(--pco-grey-500);margin:0 0 1rem;">No orders yet.</p>
      <a href="<?= APP_URL ?>/pages/patient/prescriptions.php" class="pco-btn pco-btn--primary pco-btn--sm">View prescriptions</a>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Order Ref</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= e($o['order_ref']) ?></strong></td>
            <td style="font-size:.85rem;"><?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></td>
            <td style="font-weight:600;"><?= money($o['total_amount']) ?></td>
            <td><?= status_badge($o['payment_status']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
            <td><a href="<?= APP_URL ?>/pages/patient/order-view.php?id=<?= $o['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);"><?= $total ?> orders total</span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
