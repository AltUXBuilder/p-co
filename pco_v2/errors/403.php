<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
http_response_code(403);
$page_title = '403 — Access Denied';
include APP_PATH . '/includes/header.php';
?>
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:3rem;">
  <div class="text-center">
    <div style="font-size:5rem;font-family:var(--pco-font-serif);color:var(--pco-lavender-mid);line-height:1;">403</div>
    <h1 style="font-size:1.8rem;margin:.5rem 0 .75rem;">Access Denied</h1>
    <p style="color:var(--pco-grey-500);max-width:380px;margin:0 auto 1.5rem;">You don't have permission to view this page.</p>
    <a href="<?= APP_URL ?>/" class="pco-btn pco-btn--primary"><i class="fa-solid fa-house"></i> Go Home</a>
  </div>
</div>
<?php include APP_PATH . '/includes/footer.php'; ?>
