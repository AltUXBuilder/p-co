<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');
$uid  = current_user_id();
$user = current_user();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['addr_id'] ?? 0);
        $addr = Database::fetchOne("SELECT * FROM patient_addresses WHERE id=? AND user_id=?",[$id,$uid]);
        if ($addr) { Database::query("DELETE FROM patient_addresses WHERE id=?",[$id]); flash_set('success','Address removed.'); }
        redirect('/pages/patient/addresses.php');
    }

    if ($action === 'set_default') {
        $id = (int)($_POST['addr_id'] ?? 0);
        Database::query("UPDATE patient_addresses SET is_default=0 WHERE user_id=?",[$uid]);
        Database::query("UPDATE patient_addresses SET is_default=1 WHERE id=? AND user_id=?",[$id,$uid]);
        flash_set('success','Default address updated.');
        redirect('/pages/patient/addresses.php');
    }

    if (in_array($action,['add','edit'])) {
        $id     = (int)($_POST['addr_id'] ?? 0);
        $errors = [];
        $d = [
            'label'    => clean($_POST['label']    ?? 'Home'),
            'line1'    => clean($_POST['line1']    ?? ''),
            'line2'    => clean($_POST['line2']    ?? ''),
            'city'     => clean($_POST['city']     ?? ''),
            'county'   => clean($_POST['county']   ?? ''),
            'postcode' => strtoupper(clean($_POST['postcode'] ?? '')),
            'country'  => 'United Kingdom',
        ];
        if (!$d['line1'])    $errors[] = 'Address line 1 is required.';
        if (!$d['city'])     $errors[] = 'City/town is required.';
        if (!$d['postcode']) $errors[] = 'Postcode is required.';
        if ($d['postcode'] && !validate_postcode($d['postcode'])) $errors[] = 'Please enter a valid UK postcode.';

        if (!$errors) {
            $count = Database::fetchOne("SELECT COUNT(*) c FROM patient_addresses WHERE user_id=?",[$uid])['c'];
            $d['is_default'] = ($count == 0) ? 1 : (int)!empty($_POST['is_default']);

            if ($action === 'add') {
                if ($d['is_default']) Database::query("UPDATE patient_addresses SET is_default=0 WHERE user_id=?",[$uid]);
                Database::insert('patient_addresses', array_merge($d, ['user_id'=>$uid,'created_at'=>date('Y-m-d H:i:s')]));
                flash_set('success','Address added.');
            } else {
                $addr = Database::fetchOne("SELECT * FROM patient_addresses WHERE id=? AND user_id=?",[$id,$uid]);
                if ($addr) {
                    if ($d['is_default']) Database::query("UPDATE patient_addresses SET is_default=0 WHERE user_id=?",[$uid]);
                    Database::update('patient_addresses', $d, ['id'=>$id]);
                    flash_set('success','Address updated.');
                }
            }
            redirect('/pages/patient/addresses.php');
        }
    }
}

$addresses = Database::fetchAll("SELECT * FROM patient_addresses WHERE user_id=? ORDER BY is_default DESC, id",[$uid]);
$editAddr  = isset($_GET['edit']) ? Database::fetchOne("SELECT * FROM patient_addresses WHERE id=? AND user_id=?",[(int)$_GET['edit'],$uid]) : null;
$showForm  = isset($_GET['add']) || $editAddr;

$page_title = 'My Addresses';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Delivery Addresses</h1>
    <?php if (!$showForm): ?>
    <a href="?add=1" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-plus"></i> Add Address</a>
    <?php endif; ?>
  </div>

  <?= flash_render() ?>

  <!-- Add / Edit form -->
  <?php if ($showForm): ?>
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3><?= $editAddr ? 'Edit Address' : 'New Address' ?></h3></div>
    <div class="pco-card__body">
      <?php if (!empty($errors)): ?>
      <div class="pco-alert pco-alert--error"><?= implode('<br>',array_map('e',$errors)) ?></div>
      <?php endif; ?>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editAddr?'edit':'add' ?>">
        <?php if ($editAddr): ?><input type="hidden" name="addr_id" value="<?= $editAddr['id'] ?>"><?php endif; ?>
        <div class="grid-x grid-margin-x">
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Label</label>
              <select name="label">
                <?php foreach (['Home','Work','Other'] as $l): ?>
                <option <?= ($editAddr['label']??'Home')===$l?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-8">
            <div class="pco-form-group">
              <label>Address Line 1 <span style="color:var(--pco-red)">*</span></label>
              <input type="text" name="line1" value="<?= e($editAddr['line1']??$_POST['line1']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Address Line 2</label>
              <input type="text" name="line2" value="<?= e($editAddr['line2']??$_POST['line2']??'') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>City / Town <span style="color:var(--pco-red)">*</span></label>
              <input type="text" name="city" value="<?= e($editAddr['city']??$_POST['city']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>County</label>
              <input type="text" name="county" value="<?= e($editAddr['county']??$_POST['county']??'') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Postcode <span style="color:var(--pco-red)">*</span></label>
              <input type="text" name="postcode" value="<?= e($editAddr['postcode']??$_POST['postcode']??'') ?>" required placeholder="SW1A 1AA">
            </div>
          </div>
        </div>
        <div class="pco-form-group">
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;">
            <input type="checkbox" name="is_default" value="1" style="width:auto;accent-color:var(--pco-purple);"
                   <?= ($editAddr['is_default']??0)?'checked':'' ?>>
            Set as default address
          </label>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button type="submit" class="pco-btn pco-btn--primary"><?= $editAddr ? 'Save Changes' : 'Add Address' ?></button>
          <a href="<?= APP_URL ?>/pages/patient/addresses.php" class="pco-btn pco-btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Address list -->
  <?php if (empty($addresses)): ?>
  <div class="pco-card"><div class="pco-card__body text-center" style="padding:2.5rem;">
    <i class="fa-solid fa-location-dot" style="font-size:2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
    <p style="color:var(--pco-grey-500);margin:0 0 1rem;">No addresses saved yet.</p>
    <a href="?add=1" class="pco-btn pco-btn--primary pco-btn--sm">Add your first address</a>
  </div></div>
  <?php else: ?>
  <div class="grid-x grid-margin-x">
    <?php foreach ($addresses as $addr): ?>
    <div class="cell large-6" style="margin-bottom:1rem;">
      <div class="pco-card <?= $addr['is_default']?'pco-card--hover':'' ?>" style="<?= $addr['is_default']?'border-color:var(--pco-purple);':'' ?>height:100%;">
        <div class="pco-card__body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
            <div>
              <span style="font-weight:700;font-size:.875rem;"><?= e($addr['label']) ?></span>
              <?php if ($addr['is_default']): ?>
              <span class="pco-badge badge--purple" style="margin-left:.5rem;">Default</span>
              <?php endif; ?>
            </div>
          </div>
          <p style="font-size:.855rem;color:var(--pco-grey-700);margin:0;line-height:1.65;">
            <?= e($addr['line1']) ?><br>
            <?php if ($addr['line2']): ?><?= e($addr['line2']) ?><br><?php endif; ?>
            <?= e($addr['city']) ?><?= $addr['county']?', '.e($addr['county']):'' ?><br>
            <?= e($addr['postcode']) ?>
          </p>
          <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:1rem;">
            <a href="?edit=<?= $addr['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pen"></i> Edit</a>
            <?php if (!$addr['is_default']): ?>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="set_default">
              <input type="hidden" name="addr_id" value="<?= $addr['id'] ?>">
              <button class="pco-btn pco-btn--ghost pco-btn--sm">Set Default</button>
            </form>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="addr_id" value="<?= $addr['id'] ?>">
              <button class="pco-btn pco-btn--danger pco-btn--sm" data-confirm="Delete this address?"><i class="fa-solid fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
