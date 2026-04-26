<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('prescriber','admin');

$uid          = current_user_id();
$consultId    = (int)($_GET['id'] ?? 0);
if (!$consultId) redirect('/pages/prescriber/dashboard.php');

$consult = Database::fetchOne(
    "SELECT c.*, cn.name cond_name, cn.slug cond_slug,
            CONCAT(u.first_name,' ',u.last_name) patient_name,
            u.first_name, u.last_name, u.date_of_birth, u.gender, u.email patient_email
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     JOIN users u ON u.id=c.patient_id
     WHERE c.id=?", [$consultId]
);
if (!$consult) { http_response_code(404); die('Consultation not found.'); }

// Can only review submitted consultations
if ($consult['status'] !== 'submitted') {
    flash_set('warning','This consultation has already been reviewed.');
    redirect('/pages/prescriber/dashboard.php');
}

// Load patient answers with questions
$answers = Database::fetchAll(
    "SELECT ca.*, qq.question_text, qq.step_number, qq.question_type
     FROM consultation_answers ca
     JOIN questionnaire_questions qq ON qq.id=ca.question_id
     WHERE ca.consultation_id=?
     ORDER BY qq.step_number, qq.sort_order", [$consultId]
);

// Load products for this condition (for Rx item creation)
$products = Database::fetchAll(
    "SELECT * FROM products WHERE condition_id=? AND is_active=1 ORDER BY sort_order",
    [$consult['condition_id']]
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = clean($_POST['action'] ?? ''); // 'approve' | 'reject'

    if ($action === 'reject') {
        $reason = clean($_POST['rejection_reason'] ?? '');
        if (!$reason) $errors[] = 'Please provide a rejection reason.';

        if (!$errors) {
            Database::update('consultations',[
                'status'           => 'rejected',
                'reviewed_by'      => $uid,
                'reviewed_at'      => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason,
                'updated_at'       => date('Y-m-d H:i:s'),
            ],['id'=>$consultId]);
            audit_log('consultation_rejected','consultation',$consultId,['reason'=>$reason]);
            flash_set('success','Consultation rejected successfully.');
            redirect('/pages/prescriber/dashboard.php');
        }
    }

    if ($action === 'approve') {
        // Validate at least one Rx item
        $itemProducts  = $_POST['item_product']  ?? [];
        $itemDosages   = $_POST['item_dosage']   ?? [];
        $itemQtys      = $_POST['item_qty']      ?? [];
        $itemUnits     = $_POST['item_unit']     ?? [];
        $itemDurations = $_POST['item_duration'] ?? [];
        $itemWarnings  = $_POST['item_warning']  ?? [];
        $clinicalNotes = clean($_POST['clinical_notes'] ?? '');
        $expiryDays    = max(7, min(365, (int)($_POST['expiry_days'] ?? 28)));

        if (empty($itemProducts) || !array_filter($itemProducts)) {
            $errors[] = 'Please add at least one medication to the prescription.';
        }

        if (!$errors) {
            try {
                Database::beginTransaction();

                // Update consultation
                Database::update('consultations',[
                    'status'      => 'approved',
                    'reviewed_by' => $uid,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'review_notes'=> $clinicalNotes,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ],['id'=>$consultId]);

                // Create prescription
                $issueDate  = date('Y-m-d');
                $expiryDate = date('Y-m-d', strtotime("+{$expiryDays} days"));

                $rxId = Database::insert('prescriptions',[
                    'consultation_id' => $consultId,
                    'patient_id'      => $consult['patient_id'],
                    'prescriber_id'   => $uid,
                    'prescription_ref'=> rx_ref(0), // temp, update after insert
                    'status'          => 'active',
                    'issue_date'      => $issueDate,
                    'expiry_date'     => $expiryDate,
                    'clinical_notes'  => $clinicalNotes,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);

                // Set proper ref now we have the ID
                Database::update('prescriptions',['prescription_ref'=>rx_ref($rxId)],['id'=>$rxId]);

                // Create prescription items
                foreach ($itemProducts as $idx => $prodId) {
                    if (!$prodId) continue;
                    $prod = Database::fetchOne("SELECT * FROM products WHERE id=?",[(int)$prodId]);
                    if (!$prod) continue;

                    Database::insert('prescription_items',[
                        'prescription_id'     => $rxId,
                        'product_id'          => (int)$prodId,
                        'medication_name'     => $prod['name'].($prod['brand']!=='Generic'?' ('.$prod['brand'].')':''),
                        'strength'            => $prod['strength'],
                        'dosage_form'         => $prod['dosage_form'],
                        'dosage_instructions' => clean($itemDosages[$idx] ?? ''),
                        'quantity'            => max(1,(int)($itemQtys[$idx]??1)),
                        'quantity_unit'       => clean($itemUnits[$idx] ?? 'tablet(s)'),
                        'duration_days'       => $itemDurations[$idx] ? (int)$itemDurations[$idx] : null,
                        'warnings'            => clean($itemWarnings[$idx] ?? ''),
                        'status'              => 'pending',
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                }

                Database::commit();
                audit_log('prescription_approved','prescription',$rxId,[
                    'consultation_id'=>$consultId,'patient_id'=>$consult['patient_id']
                ]);
                flash_set('success','Consultation approved and prescription '.$rxId.' issued.');
                redirect('/pages/prescriber/prescriptions.php?issued=1&rx_id='.$rxId);

            } catch (Throwable $e) {
                Database::rollback();
                error_log('Prescription creation error: '.$e->getMessage());
                $errors[] = 'An error occurred creating the prescription. Please try again.';
            }
        }
    }
}

$patAge = $consult['date_of_birth']
        ? (new DateTime())->diff(new DateTime($consult['date_of_birth']))->y
        : null;

$page_title = 'Review Consultation #'.$consultId;
include APP_PATH . '/includes/header.php';
?>

<div class="pco-page-head">
  <div class="grid-container">
    <div class="pco-breadcrumb">
      <a href="<?= APP_URL ?>/pages/prescriber/dashboard.php">Dashboard</a>
      <i class="fa-solid fa-chevron-right fa-xs"></i>
      <span>Review Consultation</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
      <div>
        <h1 class="pco-page-head__title">Consultation Review</h1>
        <p class="pco-page-head__sub">
          <?= e($consult['patient_name']) ?> &mdash; <?= e($consult['cond_name']) ?>
          <?= $consult['submitted_at'] ? '&mdash; submitted '.time_ago($consult['submitted_at']) : '' ?>
        </p>
      </div>
      <?= status_badge($consult['status']) ?>
    </div>
  </div>
</div>

<div class="pco-page">
<div class="grid-container">

<?php if (!empty($errors)): ?>
<div class="pco-alert pco-alert--error">
  <i class="fa-solid fa-circle-xmark"></i>
  <div><?= count($errors)===1 ? e($errors[0]) : '<ul style="margin:.3rem 0 0 1rem;">'.implode('',array_map(fn($e)=>'<li>'.e($e).'</li>',$errors)).'</ul>' ?></div>
</div>
<?php endif; ?>

<div class="grid-x grid-margin-x">

  <!-- Patient & Answers -->
  <div class="cell large-7">

    <!-- Patient summary -->
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head"><h3><i class="fa-solid fa-user" style="color:var(--pco-purple);margin-right:.4rem;"></i>Patient Summary</h3></div>
      <div class="pco-card__body">
        <div class="grid-x grid-margin-x">
          <?php foreach ([
            ['Name',  $consult['patient_name']],
            ['Email', $consult['patient_email']],
            ['Age',   $patAge ? $patAge.' years old' : 'Not provided'],
            ['Sex',   ucfirst($consult['gender'] ?? 'Not provided')],
            ['Condition', $consult['cond_name']],
          ] as [$lbl,$val]): ?>
          <div class="cell medium-6" style="margin-bottom:.75rem;">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);"><?= $lbl ?></div>
            <div style="font-weight:600;font-size:.9rem;"><?= e($val) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Patient answers -->
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head"><h3><i class="fa-solid fa-clipboard-list" style="color:var(--pco-purple);margin-right:.4rem;"></i>Questionnaire Answers</h3></div>
      <div class="pco-card__body">
        <?php if (empty($answers)): ?>
        <p style="color:var(--pco-grey-500);">No answers on record.</p>
        <?php else:
          $currentStep = null;
          foreach ($answers as $a):
            if ($a['step_number'] !== $currentStep):
              $currentStep = $a['step_number'];
        ?>
        <div style="font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--pco-grey-500);margin:<?= $currentStep>1?'1.25rem':0 ?> 0 .5rem;">Step <?= $currentStep ?></div>
        <?php endif; ?>
        <div class="pco-answer-row">
          <div class="pco-answer-row__q"><?= e($a['question_text']) ?></div>
          <div class="pco-answer-row__a">
            <?php
            $v = $a['answer_value'];
            if ($a['question_type']==='boolean') $v = ($v==='1'||$v==='true') ? '<span style="color:var(--pco-green);font-weight:600;">Yes</span>' : '<span style="color:var(--pco-red);font-weight:600;">No</span>';
            else $v = e($v ?: '—');
            echo $v;
            ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div>

  <!-- Decision panel -->
  <div class="cell large-5">

    <!-- APPROVE FORM -->
    <div class="pco-card" style="margin-bottom:1.5rem;border-color:var(--pco-green);">
      <div class="pco-card__head" style="background:var(--pco-green-light);">
        <h3 style="color:var(--pco-green);"><i class="fa-solid fa-check-circle" style="margin-right:.4rem;"></i>Approve & Issue Prescription</h3>
      </div>
      <div class="pco-card__body">
        <form method="POST" id="approveForm">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="approve">

          <!-- Rx items -->
          <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-weight:700;font-size:.845rem;margin-bottom:.6rem;">
              Medications <span style="color:var(--pco-red)">*</span>
            </label>
            <div id="rxItems">
              <!-- Item template injected by JS -->
            </div>
            <button type="button" id="addRxItem" class="pco-btn pco-btn--ghost pco-btn--sm" style="margin-top:.5rem;">
              <i class="fa-solid fa-plus"></i> Add medication
            </button>
          </div>

          <div class="pco-form-group">
            <label>Clinical notes (optional)</label>
            <textarea name="clinical_notes" rows="3" placeholder="Notes visible to dispenser and patient..."></textarea>
          </div>

          <div class="pco-form-group">
            <label>Prescription valid for (days)</label>
            <input type="number" name="expiry_days" value="28" min="7" max="365" style="max-width:120px;">
          </div>

          <button type="submit" class="pco-btn pco-btn--primary pco-btn--full"
                  data-confirm="Confirm: approve this consultation and issue a prescription?">
            <i class="fa-solid fa-check"></i> Issue Prescription
          </button>
        </form>
      </div>
    </div>

    <!-- REJECT FORM -->
    <div class="pco-card" style="border-color:var(--pco-red);">
      <div class="pco-card__head" style="background:var(--pco-red-light);">
        <h3 style="color:var(--pco-red);"><i class="fa-solid fa-xmark-circle" style="margin-right:.4rem;"></i>Reject Consultation</h3>
      </div>
      <div class="pco-card__body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="reject">
          <div class="pco-form-group">
            <label>Rejection reason <span style="color:var(--pco-red)">*</span></label>
            <textarea name="rejection_reason" rows="4" required
                      placeholder="Explain to the patient why this treatment cannot be prescribed online..."></textarea>
            <span class="hint">This will be visible to the patient.</span>
          </div>
          <button type="submit" class="pco-btn pco-btn--danger pco-btn--full"
                  data-confirm="Confirm: reject this consultation?">
            <i class="fa-solid fa-xmark"></i> Reject Consultation
          </button>
        </form>
      </div>
    </div>

  </div>

</div>
</div>
</div>

<!-- Products JSON for JS -->
<script>
const PRODUCTS = <?= json_encode($products, JSON_HEX_TAG) ?>;

function buildRxItem(idx) {
  const opts = PRODUCTS.map(p => `<option value="${p.id}">${p.name} ${p.brand!=='Generic'?'('+p.brand+')':''} — ${p.strength}</option>`).join('');
  return `
  <div class="pco-rx-item" style="position:relative;" id="rxItem${idx}">
    <button type="button" onclick="document.getElementById('rxItem${idx}').remove()"
            style="position:absolute;top:.5rem;right:.5rem;background:none;border:none;cursor:pointer;color:var(--pco-grey-500);font-size:.8rem;">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pco-grey-500);margin-bottom:.6rem;">Medication ${idx+1}</div>

    <div class="pco-form-group">
      <label>Medication</label>
      <select name="item_product[]" required>
        <option value="">— Select medication —</option>
        ${opts}
      </select>
    </div>
    <div class="pco-form-group">
      <label>Dosage instructions <span style="color:var(--pco-red)">*</span></label>
      <textarea name="item_dosage[]" rows="2" required placeholder="e.g. Take one tablet by mouth once daily with food."></textarea>
    </div>
    <div class="grid-x grid-margin-x">
      <div class="cell medium-4">
        <div class="pco-form-group">
          <label>Quantity</label>
          <input type="number" name="item_qty[]" value="28" min="1" required>
        </div>
      </div>
      <div class="cell medium-4">
        <div class="pco-form-group">
          <label>Unit</label>
          <select name="item_unit[]">
            <option>tablet(s)</option>
            <option>capsule(s)</option>
            <option>ml</option>
            <option>application(s)</option>
            <option>dose(s)</option>
            <option>injection(s)</option>
          </select>
        </div>
      </div>
      <div class="cell medium-4">
        <div class="pco-form-group">
          <label>Duration (days)</label>
          <input type="number" name="item_duration[]" placeholder="28" min="1">
        </div>
      </div>
    </div>
    <div class="pco-form-group" style="margin-bottom:0;">
      <label>Warnings / additional instructions</label>
      <input type="text" name="item_warning[]" placeholder="e.g. Avoid grapefruit juice.">
    </div>
  </div>`;
}

let rxCount = 0;
function addItem() {
  document.getElementById('rxItems').insertAdjacentHTML('beforeend', buildRxItem(rxCount++));
}

document.getElementById('addRxItem').addEventListener('click', addItem);
addItem(); // start with 1

// Pre-select product if consultation had one
<?php if ($consult['product_id']): ?>
setTimeout(() => {
  const sel = document.querySelector('#rxItems select[name="item_product[]"]');
  if (sel) sel.value = '<?= (int)$consult['product_id'] ?>';
},50);
<?php endif; ?>
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
