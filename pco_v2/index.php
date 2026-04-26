<?php
require_once __DIR__ . '/includes/bootstrap.php';
$page_title = 'Online Prescription Pharmacy';
$active_nav = 'home';

$men_conds    = Database::fetchAll("SELECT * FROM conditions WHERE (gender='male' OR gender='all')   AND is_active=1 ORDER BY sort_order LIMIT 3");
$women_conds  = Database::fetchAll("SELECT * FROM conditions WHERE (gender='female' OR gender='all') AND is_active=1 ORDER BY sort_order LIMIT 4");

$icon_map = ['weight-scale'=>'weight-scale','heart-pulse'=>'heart-pulse','cut'=>'scissors','leaf'=>'leaf','sparkles'=>'wand-sparkles','scissors'=>'scissors'];

include __DIR__ . '/includes/header.php';
?>

<!-- ── HERO ─────────────────────────────────────────────────── -->
<section class="pco-hero">
  <div class="grid-container" style="position:relative;z-index:1;">
    <div class="grid-x">
      <div class="cell large-7 medium-9">
        <div class="pco-hero__eyebrow">
          <i class="fa-solid fa-shield-halved"></i> GPhC-Registered Online Pharmacy
        </div>
        <h1>Expert prescriptions,<br><em style="font-style:italic;color:var(--pco-lavender);">delivered to your door.</em></h1>
        <p>Clinically-reviewed consultations by qualified UK prescribers. Discreet, fast and designed around you.</p>
        <div class="pco-hero__actions">
          <a href="<?= APP_URL ?>/pages/conditions.php?gender=male"   class="pco-btn pco-btn--primary pco-btn--xl">
            <i class="fa-solid fa-mars"></i> Men's Health
          </a>
          <a href="<?= APP_URL ?>/pages/conditions.php?gender=female" class="pco-btn pco-btn--xl"
             style="background:rgba(255,255,255,.1);border-color:rgba(196,168,224,.4);color:white;">
            <i class="fa-solid fa-venus"></i> Women's Health
          </a>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:2rem;margin-top:2.75rem;">
          <?php foreach ([
            ['fa-user-doctor','Qualified UK prescribers'],
            ['fa-truck-fast', '24–48hr delivery'],
            ['fa-lock',       '100% confidential'],
          ] as [$ico, $lbl]): ?>
          <span style="font-size:.845rem;display:flex;align-items:center;gap:.45rem;color:rgba(255,255,255,.8);">
            <i class="fa-solid <?= $ico ?>" style="color:var(--pco-lavender);"></i> <?= $lbl ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── HOW IT WORKS ─────────────────────────────────────────── -->
<section style="padding:5rem 0;background:var(--pco-white);">
  <div class="grid-container">
    <div class="text-center" style="margin-bottom:3.5rem;">
      <p style="font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--pco-purple);margin-bottom:.6rem;">The Process</p>
      <h2 style="font-size:2.4rem;">Treatment in three steps</h2>
    </div>
    <div class="grid-x grid-margin-x">
      <?php
      $steps = [
        ['01','clipboard-list', 'Complete a consultation',     'Answer a short medical questionnaire. Confidential, clinically designed, and takes under five minutes.'],
        ['02','user-doctor',    'Reviewed by a prescriber',    'A qualified UK prescriber reviews your answers and issues an appropriate prescription — usually within hours.'],
        ['03','truck-fast',     'Discreet doorstep delivery',  'Dispensed by our GPhC-registered pharmacy and delivered in plain, unmarked packaging.'],
      ];
      foreach ($steps as [$num, $ico, $title, $desc]):
      ?>
      <div class="cell large-4 medium-4 text-center" style="padding:1rem;">
        <div style="width:68px;height:68px;background:var(--pco-lavender-tint);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.6rem;color:var(--pco-purple);">
          <i class="fa-solid fa-<?= $ico ?>"></i>
        </div>
        <div style="font-size:.65rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--pco-lavender-mid);margin-bottom:.5rem;"><?= $num ?></div>
        <h3 style="font-size:1.2rem;font-family:var(--pco-font-body);font-weight:700;margin-bottom:.5rem;"><?= $title ?></h3>
        <p style="font-size:.875rem;color:var(--pco-grey-500);max-width:260px;margin:0 auto;"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:2.5rem;">
      <a href="<?= APP_URL ?>/pages/auth/register.php" class="pco-btn pco-btn--primary pco-btn--lg">Start your consultation</a>
    </div>
  </div>
</section>

<!-- ── MEN / WOMEN SPLIT ────────────────────────────────────── -->
<section class="pco-split">
  <a href="<?= APP_URL ?>/pages/conditions.php?gender=male" class="pco-split__panel pco-split__panel--men">
    <div style="position:relative;z-index:1;">
      <div class="pco-split__label">For Him</div>
      <h2>Men's Health</h2>
      <p>Weight loss, erectile dysfunction and hair loss support — treated with discretion and clinical expertise.</p>
      <div class="pco-split__tags">
        <?php foreach ($men_conds as $c): ?>
        <span class="pco-split__tag"><?= e($c['name']) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="pco-btn pco-btn--outline" style="margin-top:2rem;border-color:rgba(196,168,224,.4);color:var(--pco-lavender);">
        View treatments <i class="fa-solid fa-arrow-right fa-sm"></i>
      </div>
    </div>
    <div class="pco-split__bg-letter">♂</div>
  </a>
  <a href="<?= APP_URL ?>/pages/conditions.php?gender=female" class="pco-split__panel pco-split__panel--women">
    <div style="position:relative;z-index:1;">
      <div class="pco-split__label">For Her</div>
      <h2>Women's Health</h2>
      <p>Weight loss, skin health, hair loss and digestive wellness — evidence-based prescriptions tailored to you.</p>
      <div class="pco-split__tags">
        <?php foreach ($women_conds as $c): ?>
        <span class="pco-split__tag"><?= e($c['name']) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="pco-btn pco-btn--outline" style="margin-top:2rem;border-color:rgba(196,168,224,.4);color:var(--pco-lavender);">
        View treatments <i class="fa-solid fa-arrow-right fa-sm"></i>
      </div>
    </div>
    <div class="pco-split__bg-letter">♀</div>
  </a>
</section>

<!-- ── TRUST BAR ────────────────────────────────────────────── -->
<section style="background:var(--pco-grey-100);border-top:1px solid var(--pco-grey-200);border-bottom:1px solid var(--pco-grey-200);padding:2.5rem 0;">
  <div class="grid-container">
    <div class="grid-x grid-margin-x align-middle text-center">
      <?php
      $trust = [
        ['fa-shield-halved',    'GPhC Registered',           'Registration #'.GPHC_NUMBER],
        ['fa-user-doctor',      'UK-qualified prescribers',  'Every consultation reviewed'],
        ['fa-lock',             'GDPR Compliant',            'Your data stays private'],
        ['fa-rotate',           'Easy repeat prescriptions', 'Seamless ongoing care'],
      ];
      foreach ($trust as [$ico, $heading, $sub]): ?>
      <div class="cell large-3 medium-6" style="padding:1rem;">
        <i class="fa-solid <?= $ico ?>" style="font-size:1.65rem;color:var(--pco-purple);margin-bottom:.7rem;display:block;"></i>
        <div style="font-weight:700;font-size:.9rem;"><?= $heading ?></div>
        <div style="font-size:.78rem;color:var(--pco-grey-500);"><?= $sub ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA BOTTOM ───────────────────────────────────────────── -->
<section style="padding:5rem 0;background:var(--pco-white);">
  <div class="grid-container text-center">
    <h2 style="font-size:2.2rem;margin-bottom:0.9rem;">Ready to feel better?</h2>
    <p style="color:var(--pco-grey-500);max-width:480px;margin:0 auto 2rem;line-height:1.7;">
      Create a free account, complete a short consultation and receive your prescription — often the same day.
    </p>
    <a href="<?= APP_URL ?>/pages/auth/register.php" class="pco-btn pco-btn--primary pco-btn--xl">
      <i class="fa-solid fa-user-plus"></i> Create your free account
    </a>
    <p style="font-size:.8rem;color:var(--pco-grey-500);margin-top:1rem;">
      Already registered? <a href="<?= APP_URL ?>/pages/auth/login.php">Sign in here</a>
    </p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
