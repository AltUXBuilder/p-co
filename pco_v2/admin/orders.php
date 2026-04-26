<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = clean($_POST['action'] ?? '');
    $orderId = (int)($_POST['order_id'] ?? 0);
    $order   = $orderId ? Database::fetchOne("SELECT * FROM orders WHERE id=?",[$orderId]) : null;

    if ($order) {
        if ($action === 'update_status') {
            $status  = clean($_POST['status'] ?? '');
            $payStatus = clean($_POST['payment_status'] ?? '');
            $notes   = clean($_POST['notes'] ?? '');
            if (in_array($status,['pending','processing','dispatched','delivered','cancelled','refunded'])) {
                Database::update('orders',['status'=>$status,'payment_status'=>$payStatus,'notes'=>$notes,'updated_at'=>date('Y-m-d H:i:s')],['id'=>$orderId]);
            }
        }
        if ($action === 'update_delivery') {
            $tracking = clean($_POST['tracking_number'] ?? '');
            $carrier  = clean($_POST['carrier'] ?? '');
            $dStatus  = clean($_POST['delivery_status'] ?? '');
            Database::query("UPDATE deliveries SET tracking_number=?,carrier=?,status=?,updated_at=? WHERE order_id=?",
                [$tracking,$carrier,$dStatus,date('Y-m-d H:i:s'),$orderId]);
            if ($dStatus === 'delivered') Database::query("UPDATE deliveries SET delivered_at=? WHERE order_id=?", [date('Y-m-d H:i:s'),$orderId]);
        }
        audit_log('order_updated','order',$orderId,['action'=>$action]);
        flash_set('success','Order updated.');
    }
    redirect('/pages/admin/orders.php' . ($orderId?'?view='.$orderId:''));
}

$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $viewOrder = Database::fetchOne(
        "SELECT o.*, CONCAT(u.first_name,' ',u.last_name) patient_name, u.email patient_email,
                pa.line1, pa.line2, pa.city, pa.postcode
         FROM orders o JOIN users u ON u.id=o.patient_id
         JOIN patient_addresses pa ON pa.id=o.address_id
         WHERE o.id=?",[$viewId]
    );
    $viewItems    = Database::fetchAll("SELECT * FROM order_items WHERE order_id=?",[$viewId]);
    $viewDelivery = Database::fetchOne("SELECT * FROM deliveries WHERE order_id=?",[$viewId]);
}

$status = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$per    = 15;

$where  = "WHERE 1=1";
$params = [];
if ($status) { $where .= " AND o.status=?"; $params[] = $status; }
if ($search) { $where .= " AND (o.order_ref LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ? OR u.email LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }

$total  = Database::fetchOne("SELECT COUNT(*) c FROM orders o JOIN users u ON u.id=o.patient_id $where",$params)['c'];
$pg     = paginate($total,$page,$per);
$orders = Database::fetchAll(
    "SELECT o.*, CONCAT(u.first_name,' ',u.last_name) patient_name
     FROM orders o JOIN users u ON u.id=o.patient_id
     $where ORDER BY o.created_at DESC LIMIT {$per} OFFSET {$pg['offset']}", $params
);

$page_title = 'Orders';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">

  <?= flash_render() ?>

  <?php if ($viewOrder ?? false): ?>
  <!-- Order detail view -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
      <div class="pco-breadcrumb"><a href="?">Orders</a><i class="fa-solid fa-chevron-right fa-xs"></i><span><?= e($viewOrder['order_ref']) ?></span></div>
      <h1 style="font-size:1.4rem;margin:.3rem 0 0;"><?= e($viewOrder['order_ref']) ?></h1>
    </div>
    <div style="display:flex;gap:.5rem;"><?= status_badge($viewOrder['status']) ?><?= status_badge($viewOrder['payment_status']) ?></div>
  </div>
  <div class="grid-x grid-margin-x">
    <div class="cell large-7">
      <div class="pco-card" style="margin-bottom:1rem;">
        <div class="pco-card__head"><h3>Items</h3></div>
        <div class="pco-card__body">
          <?php foreach ($viewItems as $item): ?>
          <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--pco-grey-100);font-size:.875rem;">
            <div><strong><?= e($item['product_name']) ?></strong><div style="color:var(--pco-grey-500);font-size:.8rem;">Qty <?= $item['quantity'] ?></div></div>
            <div style="font-weight:600;"><?= money($item['line_total']) ?></div>
          </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;padding:.5rem 0;font-size:.875rem;"><span style="color:var(--pco-grey-500);">Delivery</span><span><?= money($viewOrder['shipping_cost']) ?></span></div>
          <div style="display:flex;justify-content:space-between;padding:.5rem 0;font-weight:700;border-top:2px solid var(--pco-grey-200);"><span>Total</span><span style="color:var(--pco-purple);"><?= money($viewOrder['total_amount']) ?></span></div>
        </div>
      </div>
      <!-- Update status form -->
      <div class="pco-card" style="margin-bottom:1rem;">
        <div class="pco-card__head"><h3>Update Order</h3></div>
        <div class="pco-card__body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $viewId ?>">
            <div class="grid-x grid-margin-x">
              <div class="cell medium-6">
                <div class="pco-form-group">
                  <label>Order Status</label>
                  <select name="status">
                    <?php foreach (['pending','processing','dispatched','delivered','cancelled','refunded'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewOrder['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="cell medium-6">
                <div class="pco-form-group">
                  <label>Payment Status</label>
                  <select name="payment_status">
                    <?php foreach (['unpaid','paid','refunded','failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewOrder['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="cell small-12">
                <div class="pco-form-group">
                  <label>Internal Notes</label>
                  <textarea name="notes" rows="2"><?= e($viewOrder['notes']??'') ?></textarea>
                </div>
              </div>
            </div>
            <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm">Save</button>
          </form>
        </div>
      </div>
      <?php if ($viewDelivery): ?>
      <div class="pco-card">
        <div class="pco-card__head"><h3>Delivery</h3></div>
        <div class="pco-card__body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_delivery">
            <input type="hidden" name="order_id" value="<?= $viewId ?>">
            <div class="grid-x grid-margin-x">
              <div class="cell medium-4">
                <div class="pco-form-group">
                  <label>Status</label>
                  <select name="delivery_status">
                    <?php foreach (['scheduled','collected','in_transit','out_for_delivery','delivered','failed','returned'] as $s): ?>
                    <option value="<?= $s ?>" <?= $viewDelivery['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="cell medium-4">
                <div class="pco-form-group">
                  <label>Carrier</label>
                  <input type="text" name="carrier" value="<?= e($viewDelivery['carrier']??'') ?>" placeholder="Royal Mail">
                </div>
              </div>
              <div class="cell medium-4">
                <div class="pco-form-group">
                  <label>Tracking #</label>
                  <input type="text" name="tracking_number" value="<?= e($viewDelivery['tracking_number']??'') ?>">
                </div>
              </div>
            </div>
            <button type="submit" class="pco-btn pco-btn--primary pco-btn--sm">Update Delivery</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="cell large-5">
      <div class="pco-card" style="margin-bottom:1rem;">
        <div class="pco-card__head"><h3>Patient</h3></div>
        <div class="pco-card__body" style="font-size:.875rem;">
          <div style="font-weight:700;margin-bottom:.3rem;"><?= e($viewOrder['patient_name']) ?></div>
          <div style="color:var(--pco-grey-600);"><?= e($viewOrder['patient_email']) ?></div>
          <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--pco-grey-200);">
            <strong>Delivery address:</strong><br>
            <?= e($viewOrder['line1']) ?><?= $viewOrder['line2']?', '.e($viewOrder['line2']):'' ?><br>
            <?= e($viewOrder['city']) ?>, <?= e($viewOrder['postcode']) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- Orders list -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
    <h1 style="font-size:1.55rem;margin:0;">Orders</h1>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem;">
    <?php foreach ([''=> 'All','pending'=>'Pending','processing'=>'Processing','dispatched'=>'Dispatched','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
    <a href="?status=<?= $v ?>" class="pco-btn pco-btn--sm <?= $status===$v?'pco-btn--primary':'pco-btn--ghost' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <div style="margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.4rem;">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search order ref or patient…" style="max-width:280px;">
      <button type="submit" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-search"></i></button>
    </form>
  </div>
  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Ref</th><th>Patient</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= e($o['order_ref']) ?></strong></td>
            <td style="font-size:.845rem;"><?= e($o['patient_name']) ?></td>
            <td style="font-weight:600;"><?= money($o['total_amount']) ?></td>
            <td><?= status_badge($o['payment_status']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td style="font-size:.82rem;"><?= date('d M Y',strtotime($o['created_at'])) ?></td>
            <td><a href="?view=<?= $o['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pg['pages']>1): ?>
    <div class="pco-card__foot" style="display:flex;justify-content:space-between;align-items:center;">
      <span style="font-size:.8rem;color:var(--pco-grey-500);"><?= $total ?> orders</span>
      <div style="display:flex;gap:.4rem;">
        <?php if ($pg['has_prev']): ?><a href="?status=<?= $status ?>&q=<?= e($search) ?>&page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
        <?php if ($pg['has_next']): ?><a href="?status=<?= $status ?>&q=<?= e($search) ?>&page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
