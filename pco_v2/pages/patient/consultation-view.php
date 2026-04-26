<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('patient');

$uid       = current_user_id();
$consultId = (int)($_GET['id'] ?? 0);
if (!$consultId) redirect('/pages/patient/consultations.php');

$consult = Database::fetchOne(
    "SELECT c.*, cn.name cond_name,
            CONCAT(u.first_name,' ',u.last_name) prescriber_name
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     LEFT JOIN users u ON u.id=c.reviewed_by
     WHERE c.id=? AND c.patient_id=?", [$consultId,$uid]
);
if (!$consult) { http_response_code(404); die('Not found.'); }

$prescription = $consult['status'] === 'approved'
    ? Database::fetchOne("SELECT * FROM prescriptions WHERE consultation_id=?",[$consultId])
    : null;
$rxItems = $prescription
    ? Database::fetchAll("SELECT * FROM prescription_items WHERE prescription_id=?",[$prescription['id']])
    : [];

$answers = Database::fetchAll(
    "SELECT ca.*, qq.question_text, qq.step_number
     FROM consultation_answers ca
     JOIN questionnaire_questions qq ON qq.id=ca.question_id
     WHERE ca.consultation_id=? ORDER BY qq.step_number, qq.sort_order",
    [$consultId]
);

$submitted = !empty($_GET['submitted']);
$rejected  = !empty($_GET['rejected']);

$page_title = 'Consultation — '.$consult['cond_name'];
include APP_PATH . '/includes/header.php';
?>

<div class="pco-page-head">
  <div class="grid-container">
    <div class="pco-breadcrumb">
      <a href="<?= APP_URL ?>/pages/patient/dashboard.php">Dashboard</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <a href="<?= APP_URL ?>/pages/patient/consultations.php">Consultations</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <span><?= e($consult['cond_name']) ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
      <h1 class="pco-page-head__title"><?= e($consult['cond_name']) ?> Consultation</h1>
      <?= status_badge($consult['status']) ?>
    </div>
  </div>
</div>

<div class="pco-page">
<div class="grid-container">
<div class="grid-x align-center">
<div class="cell large-8">

<?php if ($submitted): ?>
<div class="pco-alert pco-alert--success">
  <i class="fa-solid fa-circle-check"></i>
  <div>
    <strong>Consultation submitted successfully!</strong><br>
    A qualified UK prescriber will review your answers — usually within a few hours.
    We'll update your dashboard when a decision has been made.
  </div>
</div>
<?php elseif ($rejected): ?>
<div class="pco-alert pco-alert--error">
  <i class="fa-solid fa-circle-xmark"></i>
  <div>
    <strong>We cannot prescribe this treatment online.</strong><br>
    Please see the rejection reason below and speak to your GP for further advice.
  </div>
</div>
<?php endif; ?>

<?php if ($consult['status'] === 'approved' && $prescription): ?>
<!-- APPROVED — show prescription -->
<div class="pco-card" style="margin-bottom:1.5rem;">
  <div class="pco-rx-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
      <div>
        <div style="font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pco-lavender);opacity:.8;margin-bottom:.35rem;">Prescription Issued</div>
        <div class="pco-rx-ref"><?= e($prescription['prescription_ref']) ?></div>
      </div>
      <?= status_badge($prescription['status']) ?>
    </div>
    <div style="margin-top:.9rem;display:flex;flex-wrap:wrap;gap:1.5rem;font-size:.8rem;color:rgba(255,255,255,.7);">
      <span><i class="fa-solid fa-calendar"></i> Issued <?= date('d M Y',strtotime($prescription['issue_date'])) ?></span>
      <span><i class="fa-solid fa-calendar-xmark"></i> Expires <?= date('d M Y',strtotime($prescription['expiry_date'])) ?></span>
      <?php if ($consult['prescriber_name']): ?>
      <span><i class="fa-solid fa-user-doctor"></i> <?= e($consult['prescriber_name']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="pco-card__body">
    <h4 style="font-family:var(--pco-font-body);font-weight:700;font-size:.875rem;margin-bottom:.9rem;">Prescribed Medications</h4>
    <?php foreach ($rxItems as $ri): ?>
    <div class="pco-rx-item">
      <div class="pco-rx-item__name"><?= e($ri['medication_name']) ?> <?= $ri['strength'] ? '— '.e($ri['strength']) : '' ?></div>
      <div class="pco-rx-item__detail"><?= e($ri['dosage_form']) ?> · Qty: <?= $ri['quantity'] ?> <?= e($ri['quantity_unit']) ?><?= $ri['duration_days']?' · '.e($ri['duration_days']).' days':'' ?></div>
      <?php if ($ri['dosage_instructions']): ?>
      <div style="margin-top:.4rem;font-size:.855rem;"><?= e($ri['dosage_instructions']) ?></div>
      <?php endif; ?>
      <?php if ($ri['warnings']): ?>
      <div style="margin-top:.35rem;font-size:.78rem;color:var(--pco-amber);"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($ri['warnings']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if ($prescription['clinical_notes']): ?>
    <div style="margin-top:1rem;padding:1rem;background:var(--pco-grey-50);border-radius:var(--pco-r-lg);">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin-bottom:.4rem;">Prescriber Notes</div>
      <p style="margin:0;font-size:.875rem;"><?= e($prescription['clinical_notes']) ?></p>
    </div>
    <?php endif; ?>

    <div style="margin-top:1.5rem;">
      <a href="<?= APP_URL ?>/pages/patient/orders.php?rx=<?= $prescription['id'] ?>" class="pco-btn pco-btn--primary">
        <i class="fa-solid fa-truck-fast"></i> Order &amp; Deliver
      </a>
    </div>
  </div>
</div>

<?php elseif ($consult['status'] === 'rejected'): ?>
<!-- REJECTED -->
<div class="pco-card" style="margin-bottom:1.5rem;border-color:var(--pco-red);">
  <div class="pco-card__head" style="background:var(--pco-red-light);">
    <h3 style="color:var(--pco-red);">Consultation Not Approved</h3>
  </div>
  <div class="pco-card__body">
    <?php if ($consult['rejection_reason']): ?>
    <p><?= e($consult['rejection_reason']) ?></p>
    <?php endif; ?>
    <div style="margin-top:1rem;padding:1rem;background:var(--pco-grey-50);border-radius:var(--pco-r-lg);font-size:.855rem;color:var(--pco-grey-700);">
      <strong><i class="fa-solid fa-circle-info"></i> What to do next:</strong>
      Speak to your GP or call <strong>NHS 111</strong> for further medical advice.
    </div>
    <div style="margin-top:1.25rem;">
      <a href="<?= APP_URL ?>/pages/conditions.php" class="pco-btn pco-btn--outline">
        Browse other treatments
      </a>
    </div>
  </div>
</div>

<?php elseif (in_array($consult['status'],['submitted','under_review'])): ?>
<!-- PENDING -->
<div class="pco-card" style="margin-bottom:1.5rem;border-color:var(--pco-purple);">
  <div class="pco-card__body text-center" style="padding:2.5rem;">
    <div class="pco-spinner" style="margin:0 auto 1rem;"></div>
    <h3 style="font-family:var(--pco-font-body);font-size:1.1rem;margin-bottom:.4rem;">Under Review</h3>
    <p style="color:var(--pco-grey-500);">A qualified prescriber is reviewing your consultation. We'll update your dashboard when ready.</p>
  </div>
</div>
<?php endif; ?>

<!-- Answers accordion -->
<div class="pco-card">
  <div class="pco-card__head" style="cursor:pointer;" id="answersToggle">
    <h3><i class="fa-solid fa-list-check" style="color:var(--pco-purple);margin-right:.4rem;"></i>Your Answers</h3>
    <i class="fa-solid fa-chevron-down" id="answersChevron"></i>
  </div>
  <div class="pco-card__body" id="answersBody" style="display:none;">
    <?php if (empty($answers)): ?>
    <p style="color:var(--pco-grey-500);">No answers on record.</p>
    <?php else:
      $currentStep = null;
      foreach ($answers as $a):
        if ($a['step_number']!==$currentStep):
          $currentStep=$a['step_number'];
    ?>
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin:<?=$currentStep>1?'1rem':0?> 0 .4rem;">Step <?=$currentStep?></div>
    <?php endif; ?>
    <div class="pco-answer-row">
      <div class="pco-answer-row__q"><?= e($a['question_text']) ?></div>
      <div class="pco-answer-row__a"><?= e($a['answer_value'] ?: '—') ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

</div>
</div>
</div>
</div>

<script>
document.getElementById('answersToggle')?.addEventListener('click',function(){
  const body = document.getElementById('answersBody');
  const chevron = document.getElementById('answersChevron');
  const open = body.style.display==='none';
  body.style.display = open ? 'block' : 'none';
  chevron.className = open ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
