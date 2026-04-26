<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$slug = clean($_GET['slug'] ?? '');
if (!$slug) redirect('/pages/conditions.php');

$condition = Database::fetchOne("SELECT * FROM conditions WHERE slug=? AND is_active=1", [$slug]);
if (!$condition) { http_response_code(404); include APP_PATH.'/pages/errors/404.php'; exit; }

$products = Database::fetchAll(
    "SELECT * FROM products WHERE condition_id=? AND is_active=1 ORDER BY sort_order",
    [$condition['id']]
);

$related = Database::fetchAll(
    "SELECT * FROM conditions WHERE gender=? AND slug!=? AND is_active=1 ORDER BY sort_order LIMIT 3",
    [$condition['gender'], $slug]
);

$page_title = e($condition['name']);
$active_nav = $condition['gender'] === 'male' ? 'men' : 'women';
include APP_PATH . '/includes/header.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:3.5rem 0 3rem;position:relative;overflow:hidden;">
  <div class="grid-container" style="position:relative;z-index:1;">
    <div class="pco-breadcrumb" style="margin-bottom:1rem;">
      <a href="<?= APP_URL ?>/" style="color:rgba(255,255,255,.5);">Home</a>
      <i class="fa-solid fa-chevron-right fa-xs" style="color:rgba(255,255,255,.3);"></i>
      <a href="<?= APP_URL ?>/pages/conditions.php?gender=<?= e($condition['gender']) ?>" style="color:rgba(255,255,255,.5);">
        <?= ucfirst($condition['gender']) ?>'s Health
      </a>
      <i class="fa-solid fa-chevron-right fa-xs" style="color:rgba(255,255,255,.3);"></i>
      <span style="color:rgba(255,255,255,.7);"><?= e($condition['name']) ?></span>
    </div>

    <div class="grid-x grid-margin-x align-middle">
      <div class="cell large-7">
        <?php if ($condition['image_path']): ?>
        <div style="width:64px;height:64px;border-radius:var(--pco-r-xl);overflow:hidden;margin-bottom:1.25rem;border:2px solid rgba(196,168,224,.3);">
          <?= img_tag($condition['image_path'], $condition['name'], '', 'width:100%;height:100%;object-fit:cover;') ?>
        </div>
        <?php else: ?>
        <div style="width:64px;height:64px;background:rgba(196,168,224,.15);border-radius:var(--pco-r-xl);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;font-size:1.8rem;color:var(--pco-lavender);">
          <i class="fa-solid fa-<?= e($condition['icon'] ?? 'stethoscope') ?>"></i>
        </div>
        <?php endif; ?>
        <span style="font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pco-lavender);opacity:.8;">
          <?= ucfirst($condition['gender']) ?>'s Health
        </span>
        <h1 style="color:white;font-size:clamp(2rem,4vw,3rem);margin:.4rem 0 .9rem;"><?= e($condition['name']) ?></h1>
        <p style="color:rgba(255,255,255,.75);font-size:1rem;max-width:520px;line-height:1.7;"><?= e($condition['description']) ?></p>
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:2rem;">
          <a href="<?= APP_URL ?>/pages/consultation.php?condition=<?= e($slug) ?>" class="pco-btn pco-btn--primary pco-btn--lg">
            <i class="fa-solid fa-clipboard-list"></i> Start Free Consultation
          </a>
          <a href="#treatments" class="pco-btn pco-btn--lg" style="background:rgba(255,255,255,.1);border-color:rgba(196,168,224,.35);color:white;">
            <i class="fa-solid fa-pills"></i> View Treatments
          </a>
        </div>
      </div>
      <div class="cell large-5 hide-for-small-only">
        <div style="display:flex;flex-direction:column;gap:.75rem;">
          <?php foreach ([
            ['fa-user-doctor','Reviewed by UK prescribers'],
            ['fa-truck-fast', 'Discreet delivery in 24–48hrs'],
            ['fa-rotate',     'Easy repeat prescriptions'],
            ['fa-lock',       'Fully confidential'],
          ] as [$ico,$lbl]): ?>
          <div style="display:flex;align-items:center;gap:.75rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:var(--pco-r-lg);padding:.75rem 1rem;">
            <i class="fa-solid <?= $ico ?>" style="color:var(--pco-lavender);width:18px;text-align:center;"></i>
            <span style="font-size:.875rem;color:rgba(255,255,255,.8);"><?= $lbl ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Treatments -->
<section id="treatments" style="padding:4rem 0;background:var(--pco-grey-50);">
  <div class="grid-container">
    <div style="margin-bottom:2.5rem;">
      <h2 style="font-size:1.9rem;margin-bottom:.4rem;">Available Treatments</h2>
      <p style="color:var(--pco-grey-500);">Select a treatment to begin your consultation. Our prescribers will confirm suitability.</p>
    </div>

    <?php if (empty($products)): ?>
    <div class="pco-card text-center" style="padding:3rem;">
      <p style="color:var(--pco-grey-500);">No treatments listed yet — please check back soon.</p>
    </div>
    <?php else: ?>
    <div class="grid-x grid-margin-x">
      <?php foreach ($products as $p): ?>
      <div class="cell large-4 medium-6" style="margin-bottom:1.5rem;">
        <div class="pco-card pco-card--hover" style="height:100%;display:flex;flex-direction:column;">
          <?php if ($p['image_path']): ?>
          <div style="height:160px;overflow:hidden;border-radius:var(--pco-r-xl) var(--pco-r-xl) 0 0;">
            <?= img_tag($p['image_path'], $p['name'], '', 'width:100%;height:100%;object-fit:cover;') ?>
          </div>
          <?php endif; ?>
          <div class="pco-card__body" style="flex:1;display:flex;flex-direction:column;">
            <?php if ($p['requires_prescription']): ?>
            <span style="font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--pco-purple);background:var(--pco-lavender-tint);padding:2px 9px;border-radius:var(--pco-r-pill);display:inline-block;margin-bottom:.75rem;width:fit-content;">
              <i class="fa-solid fa-file-prescription"></i> Prescription required
            </span>
            <?php endif; ?>
            <h3 style="font-size:1.05rem;font-family:var(--pco-font-body);font-weight:700;margin-bottom:.2rem;">
              <?= e($p['name']) ?><?= ($p['brand'] && $p['brand']!=='Generic') ? ' <span style="font-weight:400;color:var(--pco-grey-500);font-size:.875rem;">('.e($p['brand']).')</span>' : '' ?>
            </h3>
            <div style="font-size:.82rem;color:var(--pco-grey-500);margin-bottom:.6rem;"><?= e($p['strength']) ?> · <?= e($p['dosage_form']) ?></div>
            <p style="font-size:.845rem;color:var(--pco-grey-600);line-height:1.55;flex:1;"><?= e($p['description']) ?></p>
            <div style="font-size:1.6rem;font-family:var(--pco-font-serif);font-weight:600;color:var(--pco-purple);margin:1rem 0 .75rem;">
              <?= money($p['price']) ?><span style="font-size:.85rem;color:var(--pco-grey-500);font-weight:400;font-family:var(--pco-font-body);"> / course</span>
            </div>
            <a href="<?= APP_URL ?>/pages/consultation.php?condition=<?= e($slug) ?>&product=<?= $p['id'] ?>" class="pco-btn pco-btn--primary pco-btn--full">
              Start consultation <i class="fa-solid fa-arrow-right fa-sm"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- How it works for this condition -->
<section style="padding:4rem 0;background:var(--pco-white);">
  <div class="grid-container">
    <div class="grid-x grid-margin-x align-middle">
      <div class="cell large-5" style="margin-bottom:2rem;">
        <h2 style="font-size:2rem;margin-bottom:1rem;">How it works</h2>
        <?php foreach ([
          ['1','clipboard-list','Complete a short questionnaire','Answer a few confidential questions about your health and goals. Takes under 5 minutes.'],
          ['2','user-doctor',   'Reviewed by a UK prescriber',  'A qualified prescriber reviews your answers and issues a prescription if appropriate.'],
          ['3','truck-fast',    'Delivered to your door',        'Your medication is dispensed by our GPhC-registered pharmacy and delivered discreetly.'],
        ] as [$num,$ico,$title,$desc]): ?>
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
          <div style="width:40px;height:40px;background:var(--pco-lavender-tint);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;color:var(--pco-purple);font-weight:700;flex-shrink:0;"><?= $num ?></div>
          <div>
            <div style="font-weight:700;font-size:.9rem;margin-bottom:.2rem;"><?= $title ?></div>
            <div style="font-size:.845rem;color:var(--pco-grey-500);"><?= $desc ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <a href="<?= APP_URL ?>/pages/consultation.php?condition=<?= e($slug) ?>" class="pco-btn pco-btn--primary" style="margin-top:.5rem;">
          Start your free consultation
        </a>
      </div>
      <div class="cell large-6 large-offset-1">
        <div style="background:var(--pco-lavender-tint);border-radius:var(--pco-r-xl);padding:2rem;">
          <h4 style="font-family:var(--pco-font-body);font-weight:700;margin-bottom:1rem;font-size:.9rem;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-purple);">Important Information</h4>
          <?php foreach ([
            'This service is for adults aged 18 and over.',
            'All consultations are reviewed by a qualified UK prescriber.',
            'Some conditions cannot be treated online — your prescriber will advise.',
            'This does not replace your regular GP relationship.',
            'In an emergency always dial 999 or visit A&E.',
          ] as $note): ?>
          <div style="display:flex;gap:.6rem;margin-bottom:.7rem;font-size:.855rem;color:var(--pco-grey-700);">
            <i class="fa-solid fa-circle-check" style="color:var(--pco-purple);margin-top:.15rem;flex-shrink:0;"></i>
            <span><?= $note ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Related conditions -->
<?php if (!empty($related)): ?>
<section style="padding:3.5rem 0;background:var(--pco-grey-50);border-top:1px solid var(--pco-grey-200);">
  <div class="grid-container">
    <h3 style="font-size:1.4rem;margin-bottom:1.75rem;">Related treatments</h3>
    <div class="grid-x grid-margin-x">
      <?php foreach ($related as $r): ?>
      <div class="cell large-4 medium-4" style="margin-bottom:1rem;">
        <a href="<?= APP_URL ?>/pages/condition.php?slug=<?= e($r['slug']) ?>" class="pco-condition-card">
          <div class="pco-condition-card__icon">
            <?php if ($r['image_path']): ?>
            <?= img_tag($r['image_path'], $r['name'], '', 'width:100%;height:100%;object-fit:cover;border-radius:var(--pco-r-lg);') ?>
            <?php else: ?>
            <i class="fa-solid fa-<?= e($r['icon'] ?? 'stethoscope') ?>"></i>
            <?php endif; ?>
          </div>
          <h3><?= e($r['name']) ?></h3>
          <p><?= e(substr($r['description'],0,80)) ?>...</p>
          <div class="pco-condition-card__arrow">Learn more <i class="fa-solid fa-arrow-right fa-sm"></i></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include APP_PATH . '/includes/footer.php'; ?>
