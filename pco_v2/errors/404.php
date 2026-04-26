<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
http_response_code(404);
$page_title = '404 — Page Not Found';
include APP_PATH . '/includes/header.php';
?>
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:3rem;">
  <div class="text-center">
    <div style="font-size:5rem;font-family:var(--pco-font-serif);color:var(--pco-lavender-mid);line-height:1;">404</div>
    <h1 style="font-size:1.8rem;margin:.5rem 0 .75rem;">Page Not Found</h1>
    <p style="color:var(--pco-grey-500);max-width:380px;margin:0 auto 1.5rem;">The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?= APP_URL ?>/" class="pco-btn pco-btn--primary"><i class="fa-solid fa-house"></i> Go Home</a>
    <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--outline" style="margin-left:.5rem;">Browse Treatments</a>
  </div>
</div>
<?php include APP_PATH . '/includes/footer.php'; ?>
