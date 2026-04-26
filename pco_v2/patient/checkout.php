<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid  = current_user_id();
$user = current_user();
$rxId = (int)($_GET['rx_id'] ?? $_POST['rx_id'] ?? 0);
if (!$rxId) redirect('/pages/patient/prescriptions.php');

$rx = Database::fetchOne(
    "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) prescriber_name
     FROM prescriptions p JOIN users u ON u.id=p.prescriber_id
     WHERE p.id=? AND p.patient_id=? AND p.status='active'", [$rxId,$uid]
);
if (!$rx) { flash_set('error','Prescription not found or not eligible for ordering.'); redirect('/pages/patient/prescriptions.php'); }

$items     = Database::fetchAll("SELECT pi.*, pr.price FROM prescription_items pi JOIN products pr ON pr.id=pi.product_id WHERE pi.prescription_id=? AND pi.status='pending'",[$rxId]);
if (empty($items)) { flash_set('warning','All items on this prescription have already been dispensed.'); redirect('/pages/patient/prescription-view.php?id='.$rxId); }

$addresses = Database::fetchAll("SELECT * FROM patient_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC",[$uid]);
$shipping  = (float)get_setting('shipping_cost','3.99');
$subtotal  = array_sum(array_column($items,'price'));
$freeThresh= (float)get_setting('free_shipping_threshold','50.00');
$shippingCost = $subtotal >= $freeThresh ? 0.00 : $shipping;
$total     = $subtotal + $shippingCost;

$errors = [];
$step   = (int)($_POST['step'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    csrf_check();
    $addrId  = (int)($_POST['address_id'] ?? 0);
    $delDate = clean($_POST['delivery_date'] ?? '');
    $delWin  = clean($_POST['delivery_window'] ?? 'Any');

    $addr = Database::fetchOne("SELECT * FROM patient_addresses WHERE id=? AND user_id=?", [$addrId,$uid]);
    if (!$addr)    $errors[] = 'Please select a delivery address.';
    if (!$delDate) $errors[] = 'Please select a delivery date.';
    if ($delDate && strtotime($delDate) < strtotime('today')) $errors[] = 'Delivery date must be in the future.';

    if (!$errors) {
        try {
            Database::beginTransaction();

            $orderId = Database::insert('orders',[
                'order_ref'       => order_ref(0),
                'patient_id'      => $uid,
                'prescription_id' => $rxId,
                'address_id'      => $addrId,
                'status'          => 'pending',
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shippingCost,
                'total_amount'    => $total,
                'currency'        => 'GBP',
                'payment_status'  => 'unpaid',
                'payment_method'  => 'stripe_placeholder',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
            Database::update('orders',['order_ref'=>order_ref($orderId)],['id'=>$orderId]);

            foreach ($items as $item) {
                Database::insert('order_items',[
                    'order_id'             => $orderId,
                    'prescription_item_id' => $item['id'],
                    'product_id'           => $item['product_id'],
                    'product_name'         => $item['medication_name'],
                    'quantity'             => $item['quantity'],
                    'unit_price'           => $item['price'],
                    'line_total'           => $item['price'],
                ]);
            }

            Database::insert('deliveries',[
                'order_id'        => $orderId,
                'address_id'      => $addrId,
                'requested_date'  => $delDate,
                'delivery_window' => $delWin,
                'status'          => 'scheduled',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            Database::commit();
            audit_log('order_placed','order',$orderId,['rx_id'=>$rxId,'total'=>$total]);
            flash_set('success','Order placed successfully! Reference: '.order_ref($orderId));
            redirect('/pages/patient/order-view.php?id='.$orderId);
        } catch (Throwable $e) {
            Database::rollback();
            $errors[] = 'Failed to place order. Please try again.';
            error_log('Order error: '.$e->getMessage());
        }
    }
}

$page_title = 'Checkout';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-page-head">
  <div class="grid-container">
    <h1 class="pco-page-head__title">Checkout</h1>
    <p class="pco-page-head__sub"><?= e($rx['prescription_ref']) ?></p>
  </div>
</div>
<div class="pco-page"><div class="grid-container">
<div class="grid-x grid-margin-x">

  <!-- Left: form -->
  <div class="cell large-7">

    <?php if (!empty($errors)): ?>
    <div class="pco-alert pco-alert--error" style="margin-bottom:1.25rem;">
      <i class="fa-solid fa-circle-xmark"></i>
      <div><?= implode('<br>',array_map('e',$errors)) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="rx_id" value="<?= $rxId ?>">
      <input type="hidden" name="step" value="3">

      <!-- Address -->
      <div class="pco-card" style="margin-bottom:1.5rem;">
        <div class="pco-card__head">
          <h3><i class="fa-solid fa-location-dot" style="color:var(--pco-purple);margin-right:.4rem;"></i>Delivery Address</h3>
          <a href="<?= APP_URL ?>/pages/patient/addresses.php" style="font-size:.8rem;color:var(--pco-purple);">Manage addresses</a>
        </div>
        <div class="pco-card__body">
          <?php if (empty($addresses)): ?>
          <div class="pco-alert pco-alert--warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>No delivery addresses saved. <a href="<?= APP_URL ?>/pages/patient/addresses.php?return=checkout&rx_id=<?= $rxId ?>">Add one now</a>.</div>
          </div>
          <?php else: ?>
          <div class="pco-choices">
            <?php foreach ($addresses as $addr): ?>
            <label class="pco-choice <?= $addr['is_default']?'selected':'' ?>">
              <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default']?'checked':'' ?> required>
              <span>
                <strong><?= e($addr['label']) ?></strong><br>
                <span style="font-size:.845rem;color:var(--pco-grey-700);">
                  <?= e($addr['line1']) ?><?= $addr['line2']?', '.e($addr['line2']):'' ?>, <?= e($addr['city']) ?>, <?= e($addr['postcode']) ?>
                </span>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Delivery date -->
      <div class="pco-card" style="margin-bottom:1.5rem;">
        <div class="pco-card__head"><h3><i class="fa-solid fa-calendar" style="color:var(--pco-purple);margin-right:.4rem;"></i>Delivery Date</h3></div>
        <div class="pco-card__body">
          <div class="grid-x grid-margin-x">
            <div class="cell medium-6">
              <div class="pco-form-group">
                <label>Preferred date <span style="color:var(--pco-red)">*</span></label>
                <input type="date" name="delivery_date" required data-future
                       min="<?= date('Y-m-d',strtotime('+1 day')) ?>"
                       value="<?= date('Y-m-d',strtotime('+2 days')) ?>">
              </div>
            </div>
            <div class="cell medium-6">
              <div class="pco-form-group">
                <label>Preferred time window</label>
                <select name="delivery_window">
                  <option value="Any">Any time</option>
                  <option value="AM">Morning (AM)</option>
                  <option value="PM">Afternoon (PM)</option>
                  <option value="Evening">Evening</option>
                </select>
              </div>
            </div>
          </div>
          <p style="font-size:.8rem;color:var(--pco-grey-500);margin:0;">Standard delivery 1–2 working days. Plain, unmarked packaging.</p>
        </div>
      </div>

      <!-- Stripe placeholder -->
      <div class="pco-card" style="margin-bottom:1.5rem;">
        <div class="pco-card__head"><h3><i class="fa-brands fa-stripe" style="color:#635bff;margin-right:.4rem;"></i>Payment</h3></div>
        <div class="pco-card__body">
          <div class="pco-stripe-placeholder">
            <i class="fa-brands fa-stripe" style="font-size:2rem;color:#635bff;"></i>
            <p style="margin:.4rem 0 .2rem;font-weight:600;color:var(--pco-black);">Secure Payment via Stripe</p>
            <p style="font-size:.8rem;color:var(--pco-grey-500);margin:0;">Payment processing will be activated on live deployment. Your order will be confirmed immediately for testing.</p>
          </div>
          <div style="display:flex;gap:.5rem;margin-top:1rem;flex-wrap:wrap;">
            <?php foreach (['fa-cc-visa','fa-cc-mastercard','fa-cc-amex'] as $ico): ?>
            <i class="fa-brands <?= $ico ?>" style="font-size:1.8rem;color:var(--pco-grey-300);"></i>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php if (empty($addresses)): ?>
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--full pco-btn--lg" disabled>Add an address to continue</button>
      <?php else: ?>
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--full pco-btn--lg"
              data-confirm="Confirm order of <?= money($total) ?>?">
        <i class="fa-solid fa-lock"></i> Place Order — <?= money($total) ?>
      </button>
      <?php endif; ?>
    </form>
  </div>

  <!-- Right: order summary -->
  <div class="cell large-5">
    <div class="pco-card" style="position:sticky;top:90px;">
      <div class="pco-card__head"><h3>Order Summary</h3></div>
      <div class="pco-card__body">
        <?php foreach ($items as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--pco-grey-100);font-size:.875rem;">
          <div>
            <div style="font-weight:600;"><?= e($item['medication_name']) ?></div>
            <div style="color:var(--pco-grey-500);font-size:.8rem;"><?= e($item['dosage_form']) ?> · Qty <?= $item['quantity'] ?></div>
          </div>
          <div style="font-weight:600;"><?= money($item['price']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:.75rem;">
          <?php foreach ([
            ['Subtotal', money($subtotal)],
            ['Delivery', $shippingCost > 0 ? money($shippingCost) : '<span style="color:var(--pco-green)">FREE</span>'],
          ] as [$lbl,$val]): ?>
          <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:.3rem 0;">
            <span style="color:var(--pco-grey-500);"><?= $lbl ?></span>
            <span><?= $val ?></span>
          </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:700;padding:.6rem 0;border-top:2px solid var(--pco-grey-200);margin-top:.3rem;">
            <span>Total</span>
            <span style="color:var(--pco-purple);"><?= money($total) ?></span>
          </div>
        </div>
        <?php if ($shippingCost > 0 && $subtotal < $freeThresh): ?>
        <div style="background:var(--pco-lavender-tint);border-radius:var(--pco-r);padding:.6rem .9rem;font-size:.78rem;color:var(--pco-purple);margin-top:.5rem;">
          Spend <?= money($freeThresh - $subtotal) ?> more for free delivery
        </div>
        <?php endif; ?>
      </div>
      <div class="pco-card__foot" style="font-size:.78rem;color:var(--pco-grey-500);">
        <i class="fa-solid fa-lock"></i> Encrypted &amp; GDPR compliant &nbsp;·&nbsp;
        <i class="fa-solid fa-truck-fast"></i> Plain packaging
      </div>
    </div>
  </div>

</div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
