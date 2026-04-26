<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if (is_logged_in()) redirect('/pages/patient/dashboard.php');

$success = false;
$error   = '';
$token   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(clean($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = Database::fetchOne("SELECT id, first_name FROM users WHERE email=? AND is_active=1", [$email]);
        if ($user) {
            $token   = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            Database::update('users', [
                'reset_token'   => $token,
                'reset_expires' => $expires,
            ], ['id' => $user['id']]);
            audit_log('password_reset_requested', 'user', $user['id'], ['email' => $email]);
        }
        // Always show success to prevent email enumeration
        $success = true;
    }
}

$page_title = 'Forgot Password';
include APP_PATH . '/includes/header.php';
?>

<div style="min-height:calc(100vh - 70px);background:var(--pco-grey-50);display:flex;align-items:center;padding:3rem 0;">
<div class="grid-container"><div class="grid-x align-center"><div class="cell large-5 medium-7">

  <div class="text-center" style="margin-bottom:2rem;">
    <a href="<?= APP_URL ?>/" style="font-family:var(--pco-font-serif);font-size:2rem;color:var(--pco-lavender-mid);text-decoration:none;font-weight:600;">P&amp;Co<span style="color:var(--pco-purple);">.</span></a>
    <h1 style="font-size:1.5rem;margin-top:1rem;margin-bottom:.25rem;">Reset your password</h1>
    <p style="color:var(--pco-grey-500);font-size:.9rem;">Enter your email and we'll generate a reset link.</p>
  </div>

  <div class="pco-card">
    <div class="pco-card__body" style="padding:2rem;">

      <?php if ($success): ?>
        <div class="pco-alert pco-alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <div>If an account exists for that email, a reset link has been generated below.</div>
        </div>
        <?php if ($token): ?>
        <div style="background:var(--pco-lavender-tint);border:1.5px solid var(--pco-lavender-mid);border-radius:var(--pco-r-lg);padding:1rem;margin-top:1rem;">
          <p style="font-size:.78rem;font-weight:700;color:var(--pco-purple);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.07em;">Your Reset Link</p>
          <a href="<?= APP_URL ?>/pages/auth/reset-password.php?token=<?= urlencode($token) ?>" style="font-size:.845rem;word-break:break-all;color:var(--pco-purple);">
            <?= APP_URL ?>/pages/auth/reset-password.php?token=<?= e($token) ?>
          </a>
          <p style="font-size:.75rem;color:var(--pco-grey-500);margin-top:.5rem;margin-bottom:0;">This link expires in 1 hour. Click it to set a new password.</p>
        </div>
        <?php endif; ?>
        <div style="margin-top:1.25rem;text-align:center;">
          <a href="<?= APP_URL ?>/pages/auth/login.php" class="pco-btn pco-btn--ghost pco-btn--sm">Back to login</a>
        </div>

      <?php else: ?>
        <?php if ($error): ?>
        <div class="pco-alert pco-alert--error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="pco-form-group">
            <label>Email address</label>
            <input type="email" name="email" value="<?= e($_POST['email']??'') ?>" required placeholder="you@example.com" autocomplete="email">
          </div>
          <button type="submit" class="pco-btn pco-btn--primary pco-btn--full">Generate reset link</button>
        </form>
      <?php endif; ?>

    </div>
    <div class="pco-card__foot text-center">
      <a href="<?= APP_URL ?>/pages/auth/login.php" style="font-size:.875rem;color:var(--pco-grey-500);">
        <i class="fa-solid fa-arrow-left fa-xs"></i> Back to sign in
      </a>
    </div>
  </div>

</div></div></div>
</div>
<?php include APP_PATH . '/includes/footer.php'; ?>
