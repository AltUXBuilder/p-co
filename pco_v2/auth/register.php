<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if (is_logged_in()) redirect('/pages/patient/dashboard.php');

$errors = []; $d = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'first_name' => clean($_POST['first_name'] ?? ''),
        'last_name'  => clean($_POST['last_name']  ?? ''),
        'email'      => strtolower(clean($_POST['email'] ?? '')),
        'phone'      => clean($_POST['phone'] ?? ''),
        'gender'     => clean($_POST['gender'] ?? ''),
        'dob'        => clean($_POST['date_of_birth'] ?? ''),
    ];
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $terms = !empty($_POST['terms']);

    if (!$d['first_name'])                           $errors[] = 'First name is required.';
    if (!$d['last_name'])                            $errors[] = 'Last name is required.';
    if (!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8)                           $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                            $errors[] = 'Passwords do not match.';
    if (!$d['dob'])                                  $errors[] = 'Date of birth is required.';
    if (!$terms)                                     $errors[] = 'You must agree to the Terms of Service.';

    if ($d['dob'] && !count($errors)) {
        if ((new DateTime())->diff(new DateTime($d['dob']))->y < 18)
            $errors[] = 'You must be 18 or over to use this service.';
    }
    if (!count($errors) && Database::fetchOne("SELECT id FROM users WHERE email=?",[$d['email']]))
        $errors[] = 'An account with this email address already exists.';

    if (!count($errors)) {
        $id = Database::insert('users', [
            'email'         => $d['email'],
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]),
            'role'          => 'patient',
            'first_name'    => $d['first_name'],
            'last_name'     => $d['last_name'],
            'phone'         => $d['phone'],
            'date_of_birth' => $d['dob'],
            'gender'        => $d['gender'] ?: null,
            'is_active'     => 1,
            'email_verified'=> 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        login_user(Database::fetchOne("SELECT * FROM users WHERE id=?",[$id]));
        audit_log('user_registered','user',$id,['email'=>$d['email']]);
        redirect('/pages/patient/dashboard.php');
    }
}

$page_title = 'Create Account';
include APP_PATH . '/includes/header.php';
?>

<div style="background:var(--pco-grey-50);padding:3rem 0;min-height:calc(100vh - 70px);">
<div class="grid-container">
<div class="grid-x align-center">
<div class="cell large-6 medium-9 small-12">

  <div class="text-center" style="margin-bottom:2rem;">
    <h1 style="font-size:1.8rem;margin-bottom:.25rem;">Create your account</h1>
    <p style="color:var(--pco-grey-500);">Join Prescribe &amp; Co. — it's free and takes under 2 minutes.</p>
  </div>

  <?php if (!empty($errors)): ?>
  <div class="pco-alert pco-alert--error" style="margin-bottom:1.5rem;">
    <i class="fa-solid fa-circle-xmark"></i>
    <div>
      <?php if (count($errors)===1): echo e($errors[0]); else: ?>
        <strong>Please fix the following:</strong>
        <ul style="margin:.4rem 0 0 1rem;padding:0;">
          <?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="pco-card">
    <div class="pco-card__body" style="padding:2rem;">
      <form method="POST">
        <?= csrf_field() ?>

        <div class="grid-x grid-margin-x">
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>First name <span style="color:var(--pco-red)">*</span></label>
              <input type="text" name="first_name" value="<?= e($d['first_name']??'') ?>" required autocomplete="given-name">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Last name <span style="color:var(--pco-red)">*</span></label>
              <input type="text" name="last_name" value="<?= e($d['last_name']??'') ?>" required autocomplete="family-name">
            </div>
          </div>
        </div>

        <div class="pco-form-group">
          <label>Email address <span style="color:var(--pco-red)">*</span></label>
          <input type="email" name="email" value="<?= e($d['email']??'') ?>" required autocomplete="email">
        </div>

        <div class="pco-form-group">
          <label>Phone number</label>
          <input type="tel" name="phone" value="<?= e($d['phone']??'') ?>" placeholder="+44 7700 900 000" autocomplete="tel">
        </div>

        <div class="grid-x grid-margin-x">
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Date of birth <span style="color:var(--pco-red)">*</span></label>
              <input type="date" name="date_of_birth" value="<?= e($d['dob']??'') ?>" required autocomplete="bday">
              <span class="hint">Must be 18 or over.</span>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Biological sex</label>
              <select name="gender">
                <option value="">Prefer not to say</option>
                <option value="male"   <?= ($d['gender']??'')==='male'   ?'selected':'' ?>>Male</option>
                <option value="female" <?= ($d['gender']??'')==='female' ?'selected':'' ?>>Female</option>
                <option value="other"  <?= ($d['gender']??'')==='other'  ?'selected':'' ?>>Other</option>
              </select>
              <span class="hint">Helps show relevant treatments.</span>
            </div>
          </div>
        </div>

        <div class="pco-form-group">
          <label>Password <span style="color:var(--pco-red)">*</span></label>
          <input type="password" name="password" required minlength="8" autocomplete="new-password">
          <span class="hint">Minimum 8 characters.</span>
        </div>

        <div class="pco-form-group">
          <label>Confirm password <span style="color:var(--pco-red)">*</span></label>
          <input type="password" name="password2" required autocomplete="new-password">
        </div>

        <div class="pco-form-group">
          <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer;font-weight:400;">
            <input type="checkbox" name="terms" style="width:auto;flex-shrink:0;margin-top:.2rem;accent-color:var(--pco-purple);">
            <span style="font-size:.855rem;line-height:1.5;">
              I agree to the <a href="<?= APP_URL ?>/pages/terms.php">Terms of Service</a> and <a href="<?= APP_URL ?>/pages/privacy.php">Privacy Policy</a>.
              I confirm I am 18 or over and understand this service provides prescription medication.
              <span style="color:var(--pco-red)">*</span>
            </span>
          </label>
        </div>

        <button type="submit" class="pco-btn pco-btn--primary pco-btn--full pco-btn--lg">
          Create Account <i class="fa-solid fa-arrow-right"></i>
        </button>

      </form>
    </div>
    <div class="pco-card__foot text-center">
      <p style="margin:0;font-size:.875rem;color:var(--pco-grey-500);">
        Already have an account? <a href="<?= APP_URL ?>/pages/auth/login.php" style="font-weight:600;color:var(--pco-purple);">Sign in</a>
      </p>
    </div>
  </div>

  <p style="text-align:center;font-size:.75rem;color:var(--pco-grey-500);margin-top:1rem;">
    <i class="fa-solid fa-lock"></i> Data encrypted &amp; stored securely in line with GDPR
  </p>

</div>
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
