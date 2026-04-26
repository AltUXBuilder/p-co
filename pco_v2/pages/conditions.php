<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$gender = clean($_GET['gender'] ?? '');
if (!in_array($gender,['male','female',''])) $gender = '';

$where  = $gender ? "AND gender='{$gender}'" : "";
$conditions = Database::fetchAll(
    "SELECT c.*, COUNT(p.id) prod_count
     FROM conditions c
     LEFT JOIN products p ON p.condition_id=c.id AND p.is_active=1
     WHERE c.is_active=1 $where
     GROUP BY c.id ORDER BY c.sort_order"
);

$page_title  = ($gender ? ucfirst($gender)."'s Health — " : '') . 'All Treatments';
$active_nav  = $gender === 'male' ? 'men' : ($gender === 'female' ? 'women' : '');
include APP_PATH . '/includes/header.php';
?>

<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:3rem 0 2.75rem;">
  <div class="grid-container">
    <h1 style="color:white;font-size:2.2rem;margin-bottom:.5rem;">
      <?= $gender === 'male' ? "Men's Health" : ($gender === 'female' ? "Women's Health" : "All Treatments") ?>
    </h1>
    <p style="color:rgba(255,255,255,.75);max-width:500px;">
      Browse our clinically-reviewed prescription treatments. Start a free consultation today.
    </p>
  </div>
</section>

<section style="background:var(--pco-grey-100);border-bottom:1px solid var(--pco-grey-200);padding:.75rem 0;">
  <div class="grid-container">
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/pages/conditions.php"
         class="pco-btn pco-btn--sm <?= !$gender ? 'pco-btn--primary' : 'pco-btn--ghost' ?>">All</a>
      <a href="<?= APP_URL ?>/pages/conditions.php?gender=male"
         class="pco-btn pco-btn--sm <?= $gender==='male' ? 'pco-btn--primary' : 'pco-btn--ghost' ?>">
        <i class="fa-solid fa-mars"></i> Men
      </a>
      <a href="<?= APP_URL ?>/pages/conditions.php?gender=female"
         class="pco-btn pco-btn--sm <?= $gender==='female' ? 'pco-btn--primary' : 'pco-btn--ghost' ?>">
        <i class="fa-solid fa-venus"></i> Women
      </a>
    </div>
  </div>
</section>

<div class="pco-page">
  <div class="grid-container">
    <div class="grid-x grid-margin-x">
      <?php if (empty($conditions)): ?>
      <div class="cell text-center" style="padding:3rem;">
        <p style="color:var(--pco-grey-500);">No conditions available for this filter.</p>
      </div>
      <?php else: ?>
      <?php foreach ($conditions as $c): ?>
      <div class="cell large-4 medium-6 small-12" style="margin-bottom:1.5rem;">
        <a href="<?= APP_URL ?>/pages/condition.php?slug=<?= e($c['slug']) ?>" class="pco-condition-card">
          <div class="pco-condition-card__icon">
            <i class="fa-solid fa-<?= e($c['icon'] ?? 'stethoscope') ?>"></i>
          </div>
          <h3><?= e($c['name']) ?></h3>
          <p><?= e($c['description']) ?></p>
          <?php if ($c['gender'] !== 'all'): ?>
          <span style="display:inline-block;margin-top:.6rem;font-size:.7rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--pco-purple);background:var(--pco-lavender-tint);padding:2px 8px;border-radius:var(--pco-r-pill);">
            <?= ucfirst($c['gender']) ?>
          </span>
          <?php endif; ?>
          <?php if ($c['prod_count'] > 0): ?>
          <span style="display:block;margin-top:.4rem;font-size:.8rem;color:var(--pco-grey-500);">
            <?= $c['prod_count'] ?> treatment<?= $c['prod_count']!=1?'s':'' ?> available
          </span>
          <?php endif; ?>
          <div class="pco-condition-card__arrow">
            Start consultation <i class="fa-solid fa-arrow-right fa-sm"></i>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
