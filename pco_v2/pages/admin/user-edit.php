<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user   = current_user();
$editId = (int)($_GET['id'] ?? 0);
$isAdd  = isset($_GET['action']) && $_GET['action'] === 'add';
$target = $editId ? Database::fetchOne("SELECT * FROM users WHERE id=?", [$editId]) : null;
if ($editId && !$target) { flash_set('error','User not found.'); redirect('/pages/admin/users.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'first_name'    => clean($_POST['first_name']    ?? ''),
        'last_name'     => clean($_POST['last_name']     ?? ''),
        'email'         => strtolower(clean($_POST['email'] ?? '')),
        'role'          => clean($_POST['role']          ?? 'patient'),
        'phone'         => clean($_POST['phone']         ?? ''),
        'date_of_birth' => clean($_POST['date_of_birth'] ?? '') ?: null,
        'gender'        => clean($_POST['gender']        ?? '') ?: null,
        'nhs_number'    => clean($_POST['nhs_number']    ?? '') ?: null,
        'is_active'     => (int)!empty($_POST['is_active']),
    ];
    $pass  = clean($_POST['password']  ?? '');
    $pass2 = clean($_POST['password2'] ?? '');

    if (!$d['first_name'])                                 $errors[] = 'First name required.';
    if (!$d['last_name'])                                  $errors[] = 'Last name required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))   $errors[] = 'Valid email required.';
    if (!in_array($d['role'],['admin','prescriber','dispenser','patient'])) $errors[] = 'Invalid role.';

    $dupCheck = Database::fetchOne("SELECT id FROM users WHERE email=?" . ($editId?" AND id!=?":""), $editId ? [$d['email'],$editId] : [$d['email']]);
    if ($dupCheck) $errors[] = 'Email already in use.';

    if ($isAdd || $pass) {
        if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($pass !== $pass2)  $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        if ($isAdd || $pass) $d['password_hash'] = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
        $d['updated_at'] = date('Y-m-d H:i:s');

        if ($isAdd) {
            $d['created_at'] = date('Y-m-d H:i:s');
            $newId = Database::insert('users', $d);
            audit_log('admin_user_created', 'user', $newId, ['email'=>$d['email'],'role'=>$d['role']]);
            flash_set('success', 'User created: ' . $d['email']);
        } else {
            Database::update('users', $d, ['id' => $editId]);
            audit_log('admin_user_updated', 'user', $editId, ['email'=>$d['email']]);
            flash_set('success', 'User updated.');
        }
        redirect('/pages/admin/users.php');
    }
    $target = array_merge($target ?? [], $d);
}

$page_title = $isAdd ? 'Add User' : 'Edit User';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div class="pco-breadcrumb" style="margin-bottom:1rem;">
    <a href="<?= APP_URL ?>/pages/admin/users.php">Users</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <span><?= $isAdd ? 'Add User' : 'Edit User' ?></span>
  </div>
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;"><?= $isAdd ? 'Add New User' : 'Edit User' ?></h1>

  <?php if (!empty($errors)): ?>
  <div class="pco-alert pco-alert--error" style="margin-bottom:1.25rem;"><?= implode('<br>',array_map('e',$errors)) ?></div>
  <?php endif; ?>

  <div class="pco-card">
    <div class="pco-card__body">
      <form method="POST">
        <?= csrf_field() ?>
        <div class="grid-x grid-margin-x">
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>First name *</label>
              <input type="text" name="first_name" value="<?= e($target['first_name']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Last name *</label>
              <input type="text" name="last_name" value="<?= e($target['last_name']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Email address *</label>
              <input type="email" name="email" value="<?= e($target['email']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Phone</label>
              <input type="tel" name="phone" value="<?= e($target['phone']??'') ?>">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Role *</label>
              <select name="role">
                <?php foreach (['patient','prescriber','dispenser','admin'] as $r): ?>
                <option value="<?= $r ?>" <?= ($target['role']??'patient')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Biological sex</label>
              <select name="gender">
                <option value="">Not specified</option>
                <?php foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($target['gender']??'')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Date of birth</label>
              <input type="date" name="date_of_birth" value="<?= e($target['date_of_birth']??'') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>NHS Number</label>
              <input type="text" name="nhs_number" value="<?= e($target['nhs_number']??'') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group" style="padding-top:1.5rem;">
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;">
                <input type="checkbox" name="is_active" value="1" style="width:auto;accent-color:var(--pco-purple);"
                       <?= ($target['is_active']??1)?'checked':'' ?>>
                Account active
              </label>
            </div>
          </div>

          <div class="cell small-12"><hr style="border-color:var(--pco-grey-200);margin:0.5rem 0 1rem;"></div>

          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Password <?= $isAdd ? '*' : '(leave blank to keep current)' ?></label>
              <input type="password" name="password" <?= $isAdd?'required':'' ?> minlength="8">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Confirm password <?= $isAdd ? '*' : '' ?></label>
              <input type="password" name="password2" minlength="8">
            </div>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:.5rem;">
          <button type="submit" class="pco-btn pco-btn--primary"><?= $isAdd ? 'Create User' : 'Save Changes' ?></button>
          <a href="<?= APP_URL ?>/pages/admin/users.php" class="pco-btn pco-btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
