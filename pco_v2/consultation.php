<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_auth('patient');

$uid  = current_user_id();
$slug = clean($_GET['condition'] ?? '');
$pid  = (int)($_GET['product'] ?? 0);

if (!$slug) redirect('/pages/conditions.php');

$condition = Database::fetchOne("SELECT * FROM conditions WHERE slug=? AND is_active=1",[$slug]);
if (!$condition) { http_response_code(404); die('Condition not found.'); }

$template = Database::fetchOne(
    "SELECT * FROM questionnaire_templates WHERE condition_id=? AND is_active=1 ORDER BY version DESC LIMIT 1",
    [$condition['id']]
);
if (!$template) { http_response_code(404); die('No questionnaire available for this condition.'); }

$questions = Database::fetchAll(
    "SELECT * FROM questionnaire_questions WHERE template_id=? ORDER BY step_number, sort_order",
    [$template['id']]
);
$steps_grouped = [];
foreach ($questions as $q) $steps_grouped[$q['step_number']][] = $q;
$totalSteps = count($steps_grouped);

$products = Database::fetchAll(
    "SELECT * FROM products WHERE condition_id=? AND is_active=1 ORDER BY sort_order",
    [$condition['id']]
);

$submitError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_submit'])) {
    csrf_check();

    $consultId       = (int)($_POST['consultation_id'] ?? 0);
    $selectedProduct = (int)($_POST['selected_product_id'] ?? 0);

    try {
        Database::beginTransaction();

        if (!$consultId) {
            $consultId = Database::insert('consultations',[
                'patient_id'  => $uid,
                'condition_id'=> $condition['id'],
                'product_id'  => $selectedProduct ?: null,
                'template_id' => $template['id'],
                'status'      => 'draft',
                'ip_address'  => client_ip(),
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $rejected = false; $rejectReason = '';
        foreach ($questions as $q) {
            $key = 'q_'.$q['id'];
            $value = null;
            if ($q['question_type'] === 'checkbox') {
                $vals  = $_POST[$key] ?? [];
                $value = is_array($vals) ? implode(', ', array_map('clean',$vals)) : '';
            } else {
                $value = clean($_POST[$key] ?? '');
            }

            $existing = Database::fetchOne(
                "SELECT id FROM consultation_answers WHERE consultation_id=? AND question_id=?",
                [$consultId,$q['id']]
            );
            if ($existing) {
                Database::update('consultation_answers',['answer_value'=>$value],['id'=>$existing['id']]);
            } else {
                Database::insert('consultation_answers',[
                    'consultation_id'=> $consultId,
                    'question_id'    => $q['id'],
                    'question_key'   => $q['question_key'],
                    'answer_value'   => $value,
                    'answered_at'    => date('Y-m-d H:i:s'),
                ]);
            }

            // Check disqualifying rules
            if ($q['disqualify_if'] && !$rejected) {
                $rule = json_decode($q['disqualify_if'], true);
                $ans  = $q['question_type'] === 'boolean'
                      ? (($value === '1' || $value === 'true') ? 'true' : 'false')
                      : strtolower($value);
                if ($rule && isset($rule['answer']) && $ans === strtolower($rule['answer'])) {
                    $rejected     = true;
                    $rejectReason = 'Based on your answer to "' . $q['question_text'] .
                        '", we are unable to prescribe this treatment online. Please speak to your GP.';
                }
            }
        }

        $status = $rejected ? 'rejected' : 'submitted';
        Database::update('consultations',[
            'status'           => $status,
            'product_id'       => $selectedProduct ?: null,
            'submitted_at'     => date('Y-m-d H:i:s'),
            'rejection_reason' => $rejected ? $rejectReason : null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ],['id'=>$consultId]);

        Database::commit();
        audit_log('consultation_submitted','consultation',$consultId,['condition'=>$slug,'status'=>$status]);

        redirect('/pages/patient/consultation-view.php?id='.$consultId.($rejected?'&rejected=1':'&submitted=1'));

    } catch (Throwable $e) {
        Database::rollback();
        error_log('Consultation error: '.$e->getMessage());
        $submitError = 'An unexpected error occurred. Please try again.';
    }
}

// Stub conditions redirect
$stubConditions = ['hair-loss-men','digestive-health','hair-loss-women','skin-health'];
$isStub = in_array($slug, $stubConditions);

$page_title = 'Consultation — '.$condition['name'];
include APP_PATH . '/includes/header.php';
?>

<div class="pco-page-head">
  <div class="grid-container">
    <div class="pco-breadcrumb">
      <a href="<?= APP_URL ?>/">Home</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <a href="<?= APP_URL ?>/pages/conditions.php">Conditions</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <span><?= e($condition['name']) ?></span>
    </div>
    <h1 class="pco-page-head__title"><?= e($template['title']) ?></h1>
    <p class="pco-page-head__sub"><?= e($template['description']) ?></p>
  </div>
</div>

<div class="pco-page">
<div class="grid-container">
<div class="grid-x align-center">
<div class="cell large-8 medium-10">

<?php if ($isStub): ?>
<!-- STUB MESSAGE -->
<div class="pco-card text-center" style="padding:3rem 2rem;">
  <i class="fa-solid fa-flask" style="font-size:2.5rem;color:var(--pco-lavender-mid);display:block;margin-bottom:1rem;"></i>
  <h2 style="font-size:1.4rem;margin-bottom:.5rem;">Coming Soon</h2>
  <p style="color:var(--pco-grey-500);max-width:400px;margin:0 auto 1.5rem;">
    Our clinical team is finalising the <?= e($condition['name']) ?> questionnaire.
    We'll notify you as soon as it's available.
  </p>
  <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--primary">
    <i class="fa-solid fa-arrow-left"></i> Browse other treatments
  </a>
</div>

<?php else: ?>

<?php if ($submitError): ?>
<div class="pco-alert pco-alert--error"><?= e($submitError) ?></div>
<?php endif; ?>

<!-- Progress bar -->
<div class="pco-progress-bar" style="margin-bottom:0;">
  <div class="pco-progress-bar__fill" id="pcoProgressFill" style="width:<?= round(1/($totalSteps+1+(count($products)?1:0))*100) ?>%;"></div>
</div>

<!-- Step indicators -->
<div class="pco-steps" style="margin-top:1.5rem;margin-bottom:2rem;overflow-x:auto;padding-bottom:.5rem;">
  <?php
  $allSteps = [];
  if (!empty($products)) $allSteps[] = 'Choose treatment';
  for ($i=1;$i<=$totalSteps;$i++) $allSteps[] = 'Step '.$i;
  $allSteps[] = 'Review';
  foreach ($allSteps as $si => $slabel):
  ?>
  <div class="pco-step <?= $si===0?'active':'' ?>">
    <?php if ($si>0): ?><div class="pco-step__line"></div><?php endif; ?>
    <div class="pco-step__bubble">
      <?php if ($si===count($allSteps)-1): ?><i class="fa-solid fa-check fa-xs"></i>
      <?php else: echo $si+1; endif; ?>
    </div>
    <div class="pco-step__label hide-for-small-only"><?= e($slabel) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<form method="POST" id="consultForm">
  <?= csrf_field() ?>
  <input type="hidden" name="_submit" value="1">
  <input type="hidden" name="consultation_id" id="consultationId" value="">

  <!-- Product selection step -->
  <?php if (!empty($products)): ?>
  <div class="pco-wizard-step active" id="stepProduct">
    <div style="margin-bottom:1.5rem;">
      <h2 style="font-size:1.2rem;font-family:var(--pco-font-body);font-weight:700;">Choose your treatment</h2>
      <p style="color:var(--pco-grey-500);font-size:.875rem;">Our prescriber will confirm suitability based on your answers.</p>
    </div>
    <div class="grid-x grid-margin-x">
      <?php foreach ($products as $p): ?>
      <div class="cell large-6 medium-6" style="margin-bottom:1rem;">
        <label style="cursor:pointer;display:block;height:100%;">
          <input type="radio" name="selected_product_id" value="<?= $p['id'] ?>"
                 style="display:none;" <?= $pid===$p['id']?'checked':'' ?>
                 onchange="this.closest('.grid-x').querySelectorAll('.pco-product-card').forEach(c=>c.classList.remove('selected'));this.closest('label').querySelector('.pco-product-card').classList.add('selected')">
          <div class="pco-product-card <?= $pid===$p['id']?'selected':'' ?>">
            <?php if ($p['requires_prescription']): ?>
            <span class="pco-product-card__rx"><i class="fa-solid fa-file-prescription"></i> Prescription required</span>
            <?php endif; ?>
            <h3><?= e($p['name']) ?><?= ($p['brand']!=='Generic'?' ('.e($p['brand']).')':'') ?></h3>
            <div class="pco-product-card__sub"><?= e($p['strength']) ?> · <?= e($p['dosage_form']) ?></div>
            <p style="font-size:.835rem;color:var(--pco-grey-500);margin-top:.6rem;line-height:1.5;"><?= e($p['description']) ?></p>
            <div class="pco-product-card__price"><?= money($p['price']) ?><small> / course</small></div>
          </div>
        </label>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:right;margin-top:1.5rem;">
      <button type="button" class="pco-btn pco-btn--primary" data-next>
        Continue <i class="fa-solid fa-arrow-right"></i>
      </button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Question steps -->
  <?php foreach ($steps_grouped as $stepNum => $stepQs): ?>
  <div class="pco-wizard-step" id="stepQ<?= $stepNum ?>">
    <div style="margin-bottom:1.5rem;">
      <h2 style="font-size:1.15rem;font-family:var(--pco-font-body);font-weight:700;">
        About you — Step <?= $stepNum ?> of <?= $totalSteps ?>
      </h2>
    </div>

    <?php foreach ($stepQs as $q):
      $fname = 'q_'.$q['id'];
      $opts  = $q['options_json'] ? json_decode($q['options_json'],true) : [];
      $req   = $q['is_required'] ? 'required' : '';
    ?>
    <div class="pco-question-card">
      <label class="pco-question-label">
        <?= e($q['question_text']) ?>
        <?php if ($q['is_required']): ?><span class="pco-question-required">*</span><?php endif; ?>
      </label>
      <?php if ($q['help_text']): ?>
      <span class="pco-question-hint"><?= e($q['help_text']) ?></span>
      <?php endif; ?>

      <?php if ($q['question_type'] === 'radio'): ?>
        <div class="pco-choices">
          <?php foreach ($opts as $o): ?>
          <label class="pco-choice">
            <input type="radio" name="<?= $fname ?>" value="<?= e($o) ?>" <?= $req ?>>
            <span><?= e($o) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

      <?php elseif ($q['question_type'] === 'checkbox'): ?>
        <div class="pco-choices">
          <?php foreach ($opts as $o): ?>
          <label class="pco-choice">
            <input type="checkbox" name="<?= $fname ?>[]" value="<?= e($o) ?>">
            <span><?= e($o) ?></span>
          </label>
          <?php endforeach; ?>
        </div>

      <?php elseif ($q['question_type'] === 'boolean'): ?>
        <div class="pco-choices">
          <label class="pco-choice">
            <input type="radio" name="<?= $fname ?>" value="1" <?= $req ?>>
            <span><i class="fa-solid fa-check" style="color:var(--pco-green);width:14px;"></i> Yes</span>
          </label>
          <label class="pco-choice">
            <input type="radio" name="<?= $fname ?>" value="0" <?= $req ?>>
            <span><i class="fa-solid fa-xmark" style="color:var(--pco-red);width:14px;"></i> No</span>
          </label>
        </div>

      <?php elseif ($q['question_type'] === 'textarea'): ?>
        <textarea name="<?= $fname ?>" <?= $req ?> rows="3" placeholder="Please provide details..."></textarea>

      <?php elseif ($q['question_type'] === 'number'): ?>
        <input type="number" name="<?= $fname ?>" <?= $req ?> min="1" step="0.1" style="max-width:180px;">

      <?php else: ?>
        <input type="text" name="<?= $fname ?>" <?= $req ?>>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;">
      <button type="button" class="pco-btn pco-btn--ghost" data-prev
              <?= ($stepNum===1 && empty($products)) ? 'style="visibility:hidden"' : '' ?>>
        <i class="fa-solid fa-arrow-left"></i> Back
      </button>
      <?php if ($stepNum < $totalSteps): ?>
      <button type="button" class="pco-btn pco-btn--primary" data-next>Continue <i class="fa-solid fa-arrow-right"></i></button>
      <?php else: ?>
      <button type="button" class="pco-btn pco-btn--primary" id="btnGoReview">Review answers <i class="fa-solid fa-arrow-right"></i></button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Review step -->
  <div class="pco-wizard-step" id="stepReview">
    <h2 style="font-size:1.15rem;font-family:var(--pco-font-body);font-weight:700;margin-bottom:0.4rem;">Review your answers</h2>
    <p style="color:var(--pco-grey-500);font-size:.875rem;margin-bottom:1.5rem;">Please check everything is correct before submitting.</p>

    <div class="pco-card pco-card--flat" id="reviewTable" style="border:1.5px solid var(--pco-grey-200);margin-bottom:1.5rem;">
      <div class="pco-card__body">
        <p style="color:var(--pco-grey-500);text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Building review...</p>
      </div>
    </div>

    <div class="pco-alert pco-alert--info" style="margin-bottom:1.5rem;">
      <i class="fa-solid fa-circle-info"></i>
      <div>
        <strong>Declaration:</strong> By submitting this consultation you confirm all information is accurate and complete.
        A qualified UK prescriber will review your answers. Providing false information is clinically dangerous.
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <button type="button" class="pco-btn pco-btn--ghost" data-prev>
        <i class="fa-solid fa-arrow-left"></i> Back
      </button>
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--lg" id="btnSubmit">
        <i class="fa-solid fa-paper-plane"></i> Submit Consultation
      </button>
    </div>
  </div>

</form>

<!-- Safety note -->
<div style="margin-top:1.75rem;padding:1rem 1.25rem;background:var(--pco-grey-100);border-radius:var(--pco-r-lg);border:1px solid var(--pco-grey-200);">
  <p style="font-size:.775rem;color:var(--pco-grey-500);margin:0;">
    <i class="fa-solid fa-shield-halved" style="color:var(--pco-purple);"></i>
    <strong>Clinical Safety:</strong> All responses are reviewed by a qualified UK prescriber.
    This service is not suitable for emergencies — call <strong>999</strong> or <strong>111</strong> for urgent medical help.
  </p>
</div>

<?php endif; // not stub ?>

</div>
</div>
</div>
</div>

<script>
const wizard = new PCOWizard('consultForm');

// "Review answers" button
document.getElementById('btnGoReview')?.addEventListener('click', function() {
  if (!wizard._validate()) return;

  // Build review table
  let rows = '';
  document.querySelectorAll('.pco-question-card').forEach(card => {
    const lbl = card.querySelector('.pco-question-label')?.textContent?.replace('*','').trim();
    let ans = '—';
    const checked = card.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
    const text    = card.querySelector('input[type="text"], input[type="number"], input[type="date"], textarea');
    if (checked.length) {
      ans = [...checked].map(c => c.value === '1' ? 'Yes' : c.value === '0' ? 'No' : c.value).join(', ');
    } else if (text?.value) {
      ans = text.value;
    }
    rows += `<div class="pco-answer-row">
      <div class="pco-answer-row__q">${lbl}</div>
      <div class="pco-answer-row__a">${ans}</div>
    </div>`;
  });
  document.getElementById('reviewTable').innerHTML = `<div class="pco-card__body">${rows || '<p style="color:var(--pco-grey-500)">No answers recorded.</p>'}</div>`;

  wizard.cur++;
  wizard._show(wizard.cur);
});

// Submit state
document.getElementById('btnSubmit')?.addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<span class="pco-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:.4rem;"></span> Submitting...';
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
