<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$page_title = 'How It Works';
$active_nav = 'about';
include APP_PATH . '/includes/header.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:4.5rem 0 4rem;">
  <div class="grid-container text-center" style="position:relative;z-index:1;">
    <p style="font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--pco-lavender);margin-bottom:.9rem;">About Prescribe &amp; Co.</p>
    <h1 style="color:white;font-size:clamp(2rem,4vw,3rem);margin-bottom:1rem;">Healthcare that fits your life</h1>
    <p style="color:rgba(255,255,255,.75);max-width:560px;margin:0 auto;font-size:1rem;line-height:1.75;">
      We're a GPhC-registered online pharmacy connecting patients with qualified UK prescribers — quickly, confidentially, and without the wait.
    </p>
  </div>
</section>

<!-- How it works steps -->
<section style="padding:5rem 0;background:var(--pco-white);">
  <div class="grid-container">
    <div class="text-center" style="margin-bottom:3.5rem;">
      <h2 style="font-size:2.2rem;margin-bottom:.5rem;">How it works</h2>
      <p style="color:var(--pco-grey-500);max-width:480px;margin:0 auto;">From consultation to delivery — the whole process is designed around you.</p>
    </div>
    <div class="grid-x grid-margin-x">
      <?php
      $steps = [
        ['01','user-plus',      'Create your account',         'Register for free in under a minute. No credit card required. Your data is encrypted and stored securely in line with GDPR.'],
        ['02','clipboard-list', 'Complete a consultation',      'Answer a short, clinically designed questionnaire for your chosen condition. Completely confidential and takes under 5 minutes.'],
        ['03','user-doctor',    'Reviewed by a UK prescriber',  'A qualified, GPhC-registered prescriber reviews your answers and determines the safest, most suitable treatment for you.'],
        ['04','file-prescription','Prescription issued',         'If appropriate, a prescription is issued electronically by your prescriber and passed directly to our dispensary.'],
        ['05','pills',          'Dispensed & quality-checked',  'Your medication is dispensed by our GPhC-registered pharmacist, labelled correctly and quality-checked before dispatch.'],
        ['06','truck-fast',     'Delivered to your door',       'Dispatched in plain, unmarked packaging within 24–48 hours. Tracked delivery to your chosen address.'],
      ];
      foreach ($steps as [$num,$ico,$title,$desc]):
      ?>
      <div class="cell large-4 medium-6" style="margin-bottom:2.5rem;">
        <div style="display:flex;gap:1rem;">
          <div style="flex-shrink:0;">
            <div style="width:52px;height:52px;background:var(--pco-lavender-tint);border-radius:var(--pco-r-lg);display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:var(--pco-purple);">
              <i class="fa-solid fa-<?= $ico ?>"></i>
            </div>
          </div>
          <div>
            <div style="font-size:.62rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--pco-lavender-mid);margin-bottom:.3rem;"><?= $num ?></div>
            <h3 style="font-size:1rem;font-family:var(--pco-font-body);font-weight:700;margin-bottom:.4rem;"><?= $title ?></h3>
            <p style="font-size:.855rem;color:var(--pco-grey-500);line-height:1.6;"><?= $desc ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Why P&Co -->
<section style="padding:4.5rem 0;background:var(--pco-grey-50);">
  <div class="grid-container">
    <div class="grid-x grid-margin-x align-middle">
      <div class="cell large-5" style="margin-bottom:2rem;">
        <h2 style="font-size:2rem;margin-bottom:1rem;">Why Prescribe &amp; Co.?</h2>
        <p style="color:var(--pco-grey-500);line-height:1.75;margin-bottom:1.5rem;">
          We believe access to quality healthcare shouldn't mean a two-week GP wait. Our service is designed to be fast, private, and genuinely clinical — not a rubber-stamp service.
        </p>
        <?php foreach ([
          ['shield-halved', 'GPhC Registered',          'Our pharmacy is fully registered with the General Pharmaceutical Council.'],
          ['user-doctor',   'Qualified UK Prescribers',  'Every consultation is reviewed by a UK-registered prescriber.'],
          ['lock',          'GDPR Compliant',            'Your data is encrypted, never sold, and stored to UK data standards.'],
          ['truck-fast',    'Fast Discreet Delivery',    'Plain, unmarked packaging dispatched within 24–48 hours.'],
          ['rotate',        'Ongoing Care',              'Easy repeat prescriptions and a full order history in your account.'],
        ] as [$ico,$heading,$sub]):
        ?>
        <div style="display:flex;gap:.9rem;margin-bottom:1.1rem;align-items:flex-start;">
          <div style="width:36px;height:36px;background:var(--pco-lavender-tint);border-radius:var(--pco-r);display:flex;align-items:center;justify-content:center;color:var(--pco-purple);flex-shrink:0;font-size:.9rem;">
            <i class="fa-solid fa-<?= $ico ?>"></i>
          </div>
          <div>
            <div style="font-weight:700;font-size:.875rem;"><?= $heading ?></div>
            <div style="font-size:.825rem;color:var(--pco-grey-500);"><?= $sub ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="cell large-6 large-offset-1">
        <div style="background:linear-gradient(145deg,var(--pco-black),var(--pco-purple-deep));border-radius:var(--pco-r-xl);padding:2.5rem;color:white;">
          <h3 style="font-size:1.4rem;color:white;margin-bottom:1.5rem;">Our commitments</h3>
          <?php foreach ([
            'We will never issue a prescription that is clinically inappropriate.',
            'We will never share your data with third parties for marketing.',
            'We will always provide a clear rejection reason if we cannot help.',
            'We support you in returning to your GP for ongoing care.',
            'Our prescribers are fully registered and subject to regular audit.',
          ] as $c): ?>
          <div style="display:flex;gap:.6rem;margin-bottom:.9rem;font-size:.875rem;color:rgba(255,255,255,.82);">
            <i class="fa-solid fa-circle-check" style="color:var(--pco-lavender);margin-top:.15rem;flex-shrink:0;"></i>
            <span><?= $c ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Trust / accreditation bar -->
<section style="background:var(--pco-white);border-top:1px solid var(--pco-grey-200);border-bottom:1px solid var(--pco-grey-200);padding:2.5rem 0;">
  <div class="grid-container">
    <div class="grid-x grid-margin-x align-middle text-center">
      <?php foreach ([
        ['shield-halved', 'GPhC Registered',     'Reg. #'.GPHC_NUMBER],
        ['user-doctor',   'UK Prescribers',       'Fully registered'],
        ['lock',          'SSL Encrypted',        '256-bit TLS'],
        ['building',      'UK Based',             'London, England'],
        ['heart-pulse',   'Clinical Standards',   'NICE guidelines'],
      ] as [$ico,$h,$s]): ?>
      <div class="cell large-2 medium-4 small-6" style="padding:1rem 0;">
        <i class="fa-solid fa-<?= $ico ?>" style="font-size:1.5rem;color:var(--pco-purple);display:block;margin-bottom:.5rem;"></i>
        <div style="font-weight:700;font-size:.855rem;"><?= $h ?></div>
        <div style="font-size:.78rem;color:var(--pco-grey-500);"><?= $s ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="padding:5rem 0;background:var(--pco-grey-50);">
  <div class="grid-container text-center">
    <h2 style="font-size:2.2rem;margin-bottom:.75rem;">Ready to get started?</h2>
    <p style="color:var(--pco-grey-500);max-width:440px;margin:0 auto 2rem;">Create a free account and complete your first consultation in minutes.</p>
    <a href="<?= APP_URL ?>/pages/auth/register.php" class="pco-btn pco-btn--primary pco-btn--xl">
      <i class="fa-solid fa-user-plus"></i> Create free account
    </a>
    <p style="margin-top:1rem;font-size:.8rem;color:var(--pco-grey-500);">
      Already registered? <a href="<?= APP_URL ?>/pages/auth/login.php">Sign in</a>
    </p>
  </div>
</section>

<?php include APP_PATH . '/includes/footer.php'; ?>
