<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if (is_logged_in()) redirect(match(current_role()){
    'admin'=>'/pages/admin/dashboard.php',
    'prescriber'=>'/pages/prescriber/dashboard.php',
    'dispenser'=>'/pages/dispenser/dashboard.php',
    default=>'/pages/patient/dashboard.php'
});

$error    = '';
$redirect = clean($_GET['redirect'] ?? '');
if (!str_starts_with($redirect,'/')) $redirect = '/pages/patient/dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(clean($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please enter your email address and password.';
    } else {
        $user = Database::fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
        if ($user && password_verify($pass, $user['password_hash'])) {
            login_user($user);
            audit_log('user_login','user',$user['id'],['email'=>$email],$user['id']);
            redirect(match($user['role']){
                'admin'=>'/pages/admin/dashboard.php',
                'prescriber'=>'/pages/prescriber/dashboard.php',
                'dispenser'=>'/pages/dispenser/dashboard.php',
                default=>$redirect
            });
        }
        $error = 'Incorrect email address or password.';
        audit_log('failed_login','user',null,['email'=>$email]);
    }
}

$page_title = 'Sign In';
include APP_PATH . '/includes/header.php';
?>

<div style="min-height:calc(100vh - 70px);background:var(--pco-grey-50);display:flex;align-items:center;padding:3rem 0;">
<div class="grid-container" style="width:100%;">
<div class="grid-x align-center">
<div class="cell large-5 medium-7 small-12">

  <!-- Brand mark -->
  <div class="text-center" style="margin-bottom:2rem;">
    <a href="<?= APP_URL ?>/" style="font-family:var(--pco-font-serif);font-size:2rem;color:var(--pco-lavender-mid);text-decoration:none;font-weight:600;">
      P&amp;Co<span style="color:var(--pco-purple);">.</span>
    </a>
    <h1 style="font-size:1.5rem;margin-top:1rem;margin-bottom:.2rem;">Welcome back</h1>
    <p style="color:var(--pco-grey-500);font-size:.9rem;">Sign in to your Prescribe &amp; Co. account</p>
  </div>

  <div class="pco-card">
    <div class="pco-card__body" style="padding:2rem;">

      <?php if ($error): ?>
      <div class="pco-alert pco-alert--error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

        <div class="pco-form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="pco-form-group">
          <label for="password">Password</label>
          <div style="position:relative;">
            <input type="password" id="password" name="password" placeholder="••••••••"
                   required autocomplete="current-password">
            <button type="button" id="togglePass"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--pco-grey-500);cursor:pointer;font-size:.9rem;">
              <i class="fa-solid fa-eye" id="eyeIco"></i>
            </button>
          </div>
        </div>

        <div style="text-align:right;margin-bottom:1.25rem;">
          <a href="<?= APP_URL ?>/pages/auth/forgot-password.php" style="font-size:.835rem;">Forgot password?</a>
        </div>

        <button type="submit" class="pco-btn pco-btn--primary pco-btn--full pco-btn--lg">
          Sign In <i class="fa-solid fa-arrow-right"></i>
        </button>
      </form>

    </div>
    <div class="pco-card__foot text-center">
      <p style="margin:0;font-size:.875rem;color:var(--pco-grey-500);">
        New to P&amp;Co? <a href="<?= APP_URL ?>/pages/auth/register.php" style="font-weight:600;color:var(--pco-purple);">Create a free account</a>
      </p>
    </div>
  </div>

  <p style="text-align:center;font-size:.75rem;color:var(--pco-grey-500);margin-top:1.25rem;">
    <i class="fa-solid fa-lock"></i> Encrypted and GDPR compliant
  </p>

</div>
</div>
</div>
</div>

<script>
document.getElementById('togglePass')?.addEventListener('click', function() {
  const f = document.getElementById('password');
  const i = document.getElementById('eyeIco');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'text' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
