<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');

$user = current_user();
$uid  = current_user_id();

$stats = [
    'addresses' => Database::fetchOne("SELECT COUNT(*) c FROM patient_addresses WHERE patient_id=?",[$uid])['c'],
    'orders'    => Database::fetchOne("SELECT COUNT(*) c FROM orders WHERE patient_id=?",[$uid])['c'],
    'consults'  => Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE patient_id=?",[$uid])['c'],
];

$page_title = 'My Account';
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash">
<div class="grid-container">
<div class="grid-x grid-margin-x">
  <?php include APP_PATH . '/includes/patient-sidebar.php'; ?>

  <div class="cell large-9 medium-8">
    <div style="margin-bottom:1.4rem;">
      <h1 style="font-size:1.55rem;margin-bottom:.2rem;">My Account</h1>
      <p style="color:var(--pco-grey-500);font-size:.9rem;">Manage your profile, addresses and order history.</p>
    </div>

    <div class="grid-x grid-margin-x" style="margin-bottom:1.2rem;">
      <div class="cell medium-4">
        <div class="pco-stat"><div><div class="pco-stat__val"><?= (int)$stats['consults'] ?></div><div class="pco-stat__label">Consultations</div></div></div>
      </div>
      <div class="cell medium-4">
        <div class="pco-stat"><div><div class="pco-stat__val"><?= (int)$stats['orders'] ?></div><div class="pco-stat__label">Orders</div></div></div>
      </div>
      <div class="cell medium-4">
        <div class="pco-stat"><div><div class="pco-stat__val"><?= (int)$stats['addresses'] ?></div><div class="pco-stat__label">Saved Addresses</div></div></div>
      </div>
    </div>

    <div class="grid-x grid-margin-x">
      <div class="cell medium-6">
        <div class="pco-card">
          <div class="pco-card__head"><h3>Profile Details</h3></div>
          <div class="pco-card__body">
            <p><strong>Name:</strong> <?= e($user['first_name'].' '.$user['last_name']) ?></p>
            <p><strong>Email:</strong> <?= e($user['email']) ?></p>
            <a class="pco-btn pco-btn--primary pco-btn--sm" href="<?= APP_URL ?>/pages/patient/profile.php">Edit profile</a>
          </div>
        </div>
      </div>
      <div class="cell medium-6">
        <div class="pco-card">
          <div class="pco-card__head"><h3>Addresses & Orders</h3></div>
          <div class="pco-card__body">
            <p>Keep your delivery details up to date and track previous orders.</p>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
              <a class="pco-btn pco-btn--ghost pco-btn--sm" href="<?= APP_URL ?>/pages/patient/addresses.php">Manage addresses</a>
              <a class="pco-btn pco-btn--ghost pco-btn--sm" href="<?= APP_URL ?>/pages/patient/orders.php">View orders</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
