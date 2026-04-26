<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid     = current_user_id();
$user    = current_user();
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) redirect('/pages/patient/orders.php');

$order = Database::fetchOne(
    "SELECT o.*, pa.line1, pa.line2, pa.city, pa.county, pa.postcode, pa.label addr_label
     FROM orders o JOIN patient_addresses pa ON pa.id=o.address_id
     WHERE o.id=? AND o.patient_id=?", [$orderId,$uid]
);
if (!$order) { http_response_code(404); die('Order not found.'); }

$items    = Database::fetchAll("SELECT * FROM order_items WHERE order_id=?",[$orderId]);
$delivery = Database::fetchOne("SELECT * FROM deliveries WHERE order_id=?",[$orderId]);

$page_title = 'Order '.$order['order_ref'];
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div class="pco-breadcrumb" style="margin-bottom:1rem;">
    <a href="<?= APP_URL ?>/pages/patient/orders.php">Orders</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <span><?= e($order['order_ref']) ?></span>
  </div>

  <!-- Flash -->
  <?= flash_render() ?>

  <div class="grid-x grid-margin-x">
    <div class="cell large-8">

      <!-- Order items -->
      <div class="pco-card" style="margin-bottom:1.5rem;">
        <div class="pco-card__head">
          <h3>Order <?= e($order['order_ref']) ?></h3>
          <?= status_badge($order['status']) ?>
        </div>
        <div class="pco-card__body">
          <?php foreach ($items as $item): ?>
          <div style="display:flex;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid var(--pco-grey-100);font-size:.875rem;">
            <div>
              <div style="font-weight:600;"><?= e($item['product_name']) ?></div>
              <div style="color:var(--pco-grey-500);font-size:.8rem;">Qty <?= $item['quantity'] ?></div>
            </div>
            <div style="font-weight:600;"><?= money($item['line_total']) ?></div>
          </div>
          <?php endforeach; ?>
          <div style="margin-top:.75rem;">
            <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:.25rem 0;">
              <span style="color:var(--pco-grey-500);">Subtotal</span><span><?= money($order['subtotal']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:.25rem 0;">
              <span style="color:var(--pco-grey-500);">Delivery</span>
              <span><?= $order['shipping_cost'] > 0 ? money($order['shipping_cost']) : '<span style="color:var(--pco-green)">FREE</span>' ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:700;padding:.5rem 0;border-top:2px solid var(--pco-grey-200);margin-top:.25rem;">
              <span>Total</span><span style="color:var(--pco-purple);"><?= money($order['total_amount']) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery info -->
      <?php if ($delivery): ?>
      <div class="pco-card">
        <div class="pco-card__head"><h3><i class="fa-solid fa-truck-fast" style="color:var(--pco-purple);margin-right:.4rem;"></i>Delivery</h3></div>
        <div class="pco-card__body" style="font-size:.875rem;">
          <div class="grid-x grid-margin-x">
            <?php foreach ([
              ['Status',    status_badge($delivery['status'])],
              ['Date',      date('d M Y',strtotime($delivery['requested_date']))],
              ['Window',    e($delivery['delivery_window'])],
              ['Tracking',  $delivery['tracking_number'] ? e($delivery['tracking_number']) : '<span style="color:var(--pco-grey-500)">Not yet assigned</span>'],
              ['Carrier',   $delivery['carrier'] ? e($delivery['carrier']) : '<span style="color:var(--pco-grey-500)">—</span>'],
            ] as [$lbl,$val]): ?>
            <div class="cell medium-6" style="margin-bottom:.75rem;">
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);"><?= $lbl ?></div>
              <div><?= $val ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:.5rem;padding-top:.75rem;border-top:1px solid var(--pco-grey-200);">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin-bottom:.3rem;">Delivery Address</div>
            <div><?= e($order['addr_label']) ?> — <?= e($order['line1']) ?><?= $order['line2']?', '.e($order['line2']):'' ?>, <?= e($order['city']) ?>, <?= e($order['postcode']) ?></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <div class="cell large-4">
      <div class="pco-card">
        <div class="pco-card__head"><h3>Payment</h3></div>
        <div class="pco-card__body" style="font-size:.875rem;">
          <?php foreach ([
            ['Status',  status_badge($order['payment_status'])],
            ['Method',  e($order['payment_method'] ?? 'Stripe')],
            ['Ref',     $order['payment_ref'] ? e($order['payment_ref']) : '<span style="color:var(--pco-grey-500)">Pending</span>'],
            ['Date',    date('d M Y',strtotime($order['created_at']))],
          ] as [$lbl,$val]): ?>
          <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid var(--pco-grey-100);">
            <span style="color:var(--pco-grey-500);"><?= $lbl ?></span><span><?= $val ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
