<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient','admin','prescriber','dispenser');
$uid  = current_user_id();
$user = current_user();
$full = Database::fetchOne("SELECT * FROM users WHERE id=?",[$uid]);

$errors  = [];
$success = '';
$tab     = clean($_GET['tab'] ?? 'details');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'update_details') {
        $d = [
            'first_name'    => clean($_POST['first_name']    ?? ''),
            'last_name'     => clean($_POST['last_name']     ?? ''),
            'phone'         => clean($_POST['phone']         ?? ''),
            'gender'        => clean($_POST['gender']        ?? ''),
            'date_of_birth' => clean($_POST['date_of_birth'] ?? ''),
            'nhs_number'    => clean($_POST['nhs_number']    ?? ''),
        ];
        if (!$d['first_name']) $errors[] = 'First name required.';
        if (!$d['last_name'])  $errors[] = 'Last name required.';
        if (!$errors) {
            Database::update('users', array_merge($d,['updated_at'=>date('Y-m-d H:i:s')]), ['id'=>$uid]);
            audit_log('profile_updated','user',$uid);
            flash_set('success','Profile updated successfully.');
            redirect('/pages/patient/profile.php?tab=details');
        }
    }

    if ($action === 'change_email') {
        $email = strtolower(clean($_POST['email'] ?? ''));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        elseif (Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?",[$email,$uid])) $errors[] = 'Email already in use.';
        else {
            Database::update('users',['email'=>$email,'updated_at'=>date('Y-m-d H:i:s')],['id'=>$uid]);
            flash_set('success','Email updated.');
            redirect('/pages/patient/profile.php?tab=details');
        }
    }

    if ($action === 'change_password') {
        $tab = 'security';
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $new2 = $_POST['new_password2']    ?? '';
        if (!password_verify($cur,$full['password_hash'])) $errors[] = 'Current password is incorrect.';
        elseif (strlen($new) < 8)  $errors[] = 'New password must be at least 8 characters.';
        elseif ($new !== $new2)    $errors[] = 'New passwords do not match.';
        else {
            Database::update('users',['password_hash'=>password_hash($new,PASSWORD_BCRYPT,['cost'=>12]),'updated_at'=>date('Y-m-d H:i:s')],['id'=>$uid]);
            audit_log('password_changed','user',$uid);
            flash_set('success','Password updated.');
            redirect('/pages/patient/profile.php?tab=security');
        }
    }
}

$page_title = 'Account Settings';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/patient-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">Account Settings</h1>
  <?= flash_render() ?>

  <!-- Tab nav -->
  <div style="display:flex;gap:.4rem;margin-bottom:1.5rem;border-bottom:2px solid var(--pco-grey-200);padding-bottom:.75rem;">
    <?php foreach (['details'=>'Personal Details','security'=>'Security'] as $t=>$lbl): ?>
    <a href="?tab=<?= $t ?>" class="pco-btn pco-btn--sm <?= $tab===$t?'pco-btn--primary':'pco-btn--ghost' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="pco-alert pco-alert--error" style="margin-bottom:1.25rem;"><?= implode('<br>',array_map('e',$errors)) ?></div>
  <?php endif; ?>

  <?php if ($tab === 'details'): ?>
  <!-- Personal details -->
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3>Personal Information</h3></div>
    <div class="pco-card__body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_details">
        <div class="grid-x grid-margin-x">
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>First name</label>
              <input type="text" name="first_name" value="<?= e($full['first_name']) ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Last name</label>
              <input type="text" name="last_name" value="<?= e($full['last_name']) ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Phone number</label>
              <input type="tel" name="phone" value="<?= e($full['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Date of birth</label>
              <input type="date" name="date_of_birth" value="<?= e($full['date_of_birth'] ?? '') ?>">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Biological sex</label>
              <select name="gender">
                <option value="">Prefer not to say</option>
                <?php foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $full['gender']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>NHS Number</label>
              <input type="text" name="nhs_number" value="<?= e($full['nhs_number'] ?? '') ?>" placeholder="000 000 0000">
              <span class="hint">Optional — may speed up prescriptions.</span>
            </div>
          </div>
        </div>
        <button type="submit" class="pco-btn pco-btn--primary">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Email -->
  <div class="pco-card">
    <div class="pco-card__head"><h3>Email Address</h3></div>
    <div class="pco-card__body">
      <p style="font-size:.875rem;color:var(--pco-grey-500);margin-bottom:1rem;">Current: <strong><?= e($full['email']) ?></strong></p>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_email">
        <div class="pco-form-group" style="max-width:380px;">
          <label>New email address</label>
          <input type="email" name="email" placeholder="new@email.com" required>
        </div>
        <button type="submit" class="pco-btn pco-btn--outline pco-btn--sm">Update Email</button>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- Security -->
  <div class="pco-card">
    <div class="pco-card__head"><h3>Change Password</h3></div>
    <div class="pco-card__body">
      <form method="POST" style="max-width:420px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="pco-form-group">
          <label>Current password</label>
          <input type="password" name="current_password" required>
        </div>
        <div class="pco-form-group">
          <label>New password</label>
          <input type="password" name="new_password" required minlength="8">
        </div>
        <div class="pco-form-group">
          <label>Confirm new password</label>
          <input type="password" name="new_password2" required>
        </div>
        <button type="submit" class="pco-btn pco-btn--primary">Update Password</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
