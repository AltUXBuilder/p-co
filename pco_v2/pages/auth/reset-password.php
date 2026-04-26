<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if (is_logged_in()) redirect('/pages/patient/dashboard.php');

$token  = clean($_GET['token'] ?? '');
$error  = '';
$done   = false;

$user = $token ? Database::fetchOne(
    "SELECT id, email, first_name FROM users WHERE reset_token=? AND reset_expires > NOW() AND is_active=1",
    [$token]
) : null;

if ($token && !$user) $error = 'This reset link is invalid or has expired. Please request a new one.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_check();
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass) < 8)  $error = 'Password must be at least 8 characters.';
    elseif ($pass !== $pass2) $error = 'Passwords do not match.';
    else {
        Database::update('users', [
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
            'reset_token'   => null,
            'reset_expires' => null,
        ], ['id' => $user['id']]);
        audit_log('password_reset_completed', 'user', $user['id']);
        $done = true;
    }
}

$page_title = 'Reset Password';
include APP_PATH . '/includes/header.php';
?>

<div style="min-height:calc(100vh - 70px);background:var(--pco-grey-50);display:flex;align-items:center;padding:3rem 0;">
<div class="grid-container"><div class="grid-x align-center"><div class="cell large-5 medium-7">

  <div class="text-center" style="margin-bottom:2rem;">
    <a href="<?= APP_URL ?>/" style="font-family:var(--pco-font-serif);font-size:2rem;color:var(--pco-lavender-mid);text-decoration:none;font-weight:600;">P&amp;Co<span style="color:var(--pco-purple);">.</span></a>
    <h1 style="font-size:1.5rem;margin-top:1rem;margin-bottom:.25rem;">Set new password</h1>
  </div>

  <div class="pco-card">
    <div class="pco-card__body" style="padding:2rem;">
      <?php if ($done): ?>
        <div class="pco-alert pco-alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <div><strong>Password updated!</strong> You can now sign in with your new password.</div>
        </div>
        <div style="text-align:center;margin-top:1.25rem;">
          <a href="<?= APP_URL ?>/pages/auth/login.php" class="pco-btn pco-btn--primary">Sign In</a>
        </div>
      <?php elseif ($error && !$user): ?>
        <div class="pco-alert pco-alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error) ?></div>
        <div style="text-align:center;margin-top:1rem;">
          <a href="<?= APP_URL ?>/pages/auth/forgot-password.php" class="pco-btn pco-btn--ghost pco-btn--sm">Request new link</a>
        </div>
      <?php elseif ($user): ?>
        <?php if ($error): ?>
        <div class="pco-alert pco-alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= e($error) ?></div>
        <?php endif; ?>
        <p style="font-size:.875rem;color:var(--pco-grey-500);margin-bottom:1.25rem;">
          Setting new password for <strong><?= e($user['email']) ?></strong>
        </p>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="pco-form-group">
            <label>New password</label>
            <input type="password" name="password" required minlength="8">
            <span class="hint">Minimum 8 characters.</span>
          </div>
          <div class="pco-form-group">
            <label>Confirm new password</label>
            <input type="password" name="password2" required>
          </div>
          <button type="submit" class="pco-btn pco-btn--primary pco-btn--full">Update Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

</div></div></div>
</div>
<?php include APP_PATH . '/includes/footer.php'; ?>
