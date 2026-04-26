<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user   = current_user();
$tmplId = (int)($_GET['id'] ?? 0);
if (!$tmplId) redirect('/pages/admin/questionnaires.php');

$template = Database::fetchOne(
    "SELECT qt.*, c.name cond_name FROM questionnaire_templates qt
     JOIN conditions c ON c.id=qt.condition_id WHERE qt.id=?", [$tmplId]
);
if (!$template) { flash_set('error','Template not found.'); redirect('/pages/admin/questionnaires.php'); }

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    // Save question order after drag-drop
    if ($action === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (is_array($order)) {
            foreach ($order as $pos => $qid) {
                Database::update('questionnaire_questions',
                    ['sort_order' => (int)$pos],
                    ['id' => (int)$qid, 'template_id' => $tmplId]
                );
            }
        }
        json_ok([], 'Order saved.');
    }

    // Save / update a single question
    if (in_array($action, ['add_question', 'edit_question'])) {
        $qid = (int)($_POST['question_id'] ?? 0);
        $opts = clean($_POST['options_raw'] ?? '');
        $optsArr = array_filter(array_map('trim', explode("\n", $opts)));
        $optsJson = !empty($optsArr) ? json_encode(array_values($optsArr)) : null;

        $disqVal = clean($_POST['disqualify_value'] ?? '');
        $disqJson = $disqVal !== '' ? json_encode(['answer' => $disqVal]) : null;

        $d = [
            'template_id'    => $tmplId,
            'question_key'   => preg_replace('/[^a-z0-9_]/', '_', strtolower(clean($_POST['question_key'] ?? ''))),
            'question_text'  => clean($_POST['question_text'] ?? ''),
            'question_type'  => clean($_POST['question_type'] ?? 'text'),
            'options_json'   => $optsJson,
            'is_required'    => (int)!empty($_POST['is_required']),
            'step_number'    => max(1, (int)($_POST['step_number'] ?? 1)),
            'sort_order'     => (int)($_POST['sort_order'] ?? 99),
            'help_text'      => clean($_POST['help_text'] ?? '') ?: null,
            'disqualify_if'  => $disqJson,
            'validation_rule'=> clean($_POST['validation_rule'] ?? '') ?: null,
        ];

        if (!$d['question_key']) $d['question_key'] = 'q_'.time();
        if (!$d['question_text']) { flash_set('error','Question text required.'); redirect('/pages/admin/questionnaire-edit.php?id='.$tmplId); }

        if ($action === 'add_question') {
            $maxSort = Database::fetchOne("SELECT MAX(sort_order) m FROM questionnaire_questions WHERE template_id=?",[$tmplId])['m'] ?? 0;
            $d['sort_order'] = $maxSort + 1;
            Database::insert('questionnaire_questions', $d);
            flash_set('success', 'Question added.');
        } else {
            Database::update('questionnaire_questions', $d, ['id' => $qid, 'template_id' => $tmplId]);
            flash_set('success', 'Question updated.');
        }
        redirect('/pages/admin/questionnaire-edit.php?id='.$tmplId);
    }

    // Delete a question
    if ($action === 'delete_question') {
        $qid = (int)($_POST['question_id'] ?? 0);
        Database::query("DELETE FROM questionnaire_questions WHERE id=? AND template_id=?", [$qid, $tmplId]);
        flash_set('success', 'Question deleted.');
        redirect('/pages/admin/questionnaire-edit.php?id='.$tmplId);
    }

    // Update template meta
    if ($action === 'update_template') {
        Database::update('questionnaire_templates', [
            'title'       => clean($_POST['title'] ?? $template['title']),
            'description' => clean($_POST['description'] ?? ''),
            'is_active'   => (int)!empty($_POST['is_active']),
        ], ['id' => $tmplId]);
        flash_set('success', 'Template updated.');
        redirect('/pages/admin/questionnaire-edit.php?id='.$tmplId);
    }
}

$questions = Database::fetchAll(
    "SELECT * FROM questionnaire_questions WHERE template_id=? ORDER BY step_number, sort_order",
    [$tmplId]
);

$editQ = isset($_GET['edit_q']) ? Database::fetchOne("SELECT * FROM questionnaire_questions WHERE id=? AND template_id=?",[(int)$_GET['edit_q'],$tmplId]) : null;

$maxStep = !empty($questions) ? max(array_column($questions,'step_number')) : 1;

$page_title = 'Edit Questionnaire — '.$template['title'];
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash"><div class="grid-container">

  <div class="pco-breadcrumb" style="margin:1rem 0 .5rem;">
    <a href="<?= APP_URL ?>/pages/admin/questionnaires.php">Questionnaires</a>
    <i class="fa-solid fa-chevron-right fa-xs"></i>
    <span><?= e($template['title']) ?></span>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
      <h1 style="font-size:1.4rem;margin-bottom:.2rem;"><?= e($template['title']) ?></h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;"><?= e($template['cond_name']) ?> · <?= count($questions) ?> question<?= count($questions)!=1?'s':'' ?> · <?= $maxStep ?> step<?= $maxStep!=1?'s':'' ?></p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <span class="pco-badge <?= $template['is_active']?'badge--green':'badge--grey' ?>"><?= $template['is_active']?'Active':'Draft' ?></span>
      <button onclick="document.getElementById('tmplMetaModal').style.display='flex'" class="pco-btn pco-btn--ghost pco-btn--sm">
        <i class="fa-solid fa-pen"></i> Edit Template Info
      </button>
    </div>
  </div>

  <?= flash_render() ?>

  <div class="grid-x grid-margin-x">

    <!-- Questions list (drag-drop) -->
    <div class="cell large-7">
      <div class="pco-card" style="margin-bottom:1.5rem;">
        <div class="pco-card__head">
          <h3>Questions <span style="font-size:.75rem;font-weight:400;color:var(--pco-grey-500);">— drag to reorder</span></h3>
          <a href="?id=<?= $tmplId ?>&add_q=1" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-plus"></i> Add Question</a>
        </div>
        <div class="pco-card__body" style="padding:0;">

          <?php if (empty($questions)): ?>
          <div style="padding:2.5rem;text-align:center;">
            <p style="color:var(--pco-grey-500);margin:0;">No questions yet. Add your first question →</p>
          </div>
          <?php else: ?>

          <!-- Group by step -->
          <?php
          $byStep = [];
          foreach ($questions as $q) $byStep[$q['step_number']][] = $q;
          ?>
          <div id="questionsList">
            <?php foreach ($byStep as $stepNum => $stepQs): ?>
            <div style="background:var(--pco-grey-50);padding:.4rem 1rem;border-bottom:1px solid var(--pco-grey-200);display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--pco-grey-500);">Step <?= $stepNum ?></span>
              <span style="font-size:.72rem;color:var(--pco-grey-500);"><?= count($stepQs) ?> question<?= count($stepQs)!=1?'s':'' ?></span>
            </div>
            <?php foreach ($stepQs as $q): ?>
            <div class="q-row" data-id="<?= $q['id'] ?>" style="display:flex;align-items:flex-start;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid var(--pco-grey-100);cursor:grab;transition:background var(--pco-t);">
              <i class="fa-solid fa-grip-vertical" style="color:var(--pco-grey-300);margin-top:.2rem;flex-shrink:0;cursor:grab;"></i>
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem;">
                  <span class="pco-badge badge--grey" style="font-size:.62rem;"><?= e($q['question_type']) ?></span>
                  <?php if ($q['is_required']): ?><span class="pco-badge badge--red" style="font-size:.62rem;">Required</span><?php endif; ?>
                  <?php if ($q['disqualify_if']): ?><span class="pco-badge badge--amber" style="font-size:.62rem;">Disqualifier</span><?php endif; ?>
                </div>
                <div style="font-size:.875rem;font-weight:600;line-height:1.4;"><?= e($q['question_text']) ?></div>
                <?php if ($q['help_text']): ?><div style="font-size:.775rem;color:var(--pco-grey-500);margin-top:.15rem;"><?= e($q['help_text']) ?></div><?php endif; ?>
                <?php if ($q['options_json']): ?>
                <div style="font-size:.75rem;color:var(--pco-purple);margin-top:.2rem;">
                  <?= implode(' · ', array_slice(json_decode($q['options_json'],true),0,3)) ?>
                  <?php if (count(json_decode($q['options_json'],true))>3): ?>+<?= count(json_decode($q['options_json'],true))-3 ?> more<?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
              <div style="display:flex;gap:.3rem;flex-shrink:0;">
                <a href="?id=<?= $tmplId ?>&edit_q=<?= $q['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pen"></i></a>
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_question">
                  <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                  <button class="pco-btn pco-btn--danger pco-btn--sm" data-confirm="Delete this question?"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
          </div>

          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Add / Edit question panel -->
    <div class="cell large-5">
      <?php if (isset($_GET['add_q']) || $editQ): ?>
      <div class="pco-card" style="position:sticky;top:80px;">
        <div class="pco-card__head">
          <h3><?= $editQ ? 'Edit Question' : 'Add Question' ?></h3>
          <a href="?id=<?= $tmplId ?>" style="font-size:.8rem;color:var(--pco-grey-500);">Cancel</a>
        </div>
        <div class="pco-card__body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editQ?'edit_question':'add_question' ?>">
            <?php if ($editQ): ?><input type="hidden" name="question_id" value="<?= $editQ['id'] ?>"><?php endif; ?>

            <div class="pco-form-group">
              <label>Question Text *</label>
              <textarea name="question_text" rows="2" required><?= e($editQ['question_text']??'') ?></textarea>
            </div>

            <div class="grid-x grid-margin-x">
              <div class="cell medium-6">
                <div class="pco-form-group">
                  <label>Question Type</label>
                  <select name="question_type" id="qType" onchange="toggleOptions()">
                    <?php foreach (['text','textarea','radio','checkbox','select','number','date','boolean'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($editQ['question_type']??'text')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="cell medium-3">
                <div class="pco-form-group">
                  <label>Step #</label>
                  <input type="number" name="step_number" value="<?= $editQ['step_number']??1 ?>" min="1" max="10">
                </div>
              </div>
              <div class="cell medium-3">
                <div class="pco-form-group" style="padding-top:1.5rem;">
                  <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-weight:400;font-size:.82rem;">
                    <input type="checkbox" name="is_required" value="1" style="width:auto;accent-color:var(--pco-purple);" <?= ($editQ['is_required']??1)?'checked':'' ?>>
                    Required
                  </label>
                </div>
              </div>
            </div>

            <div class="pco-form-group">
              <label>Question Key <span style="font-size:.75rem;color:var(--pco-grey-500);">(unique identifier)</span></label>
              <input type="text" name="question_key" value="<?= e($editQ['question_key']??'') ?>" placeholder="e.g. current_weight_kg">
            </div>

            <div class="pco-form-group" id="optionsGroup" style="<?= in_array($editQ['question_type']??'text',['radio','checkbox','select'])?'':'display:none;' ?>">
              <label>Options <span style="font-size:.75rem;color:var(--pco-grey-500);">(one per line)</span></label>
              <textarea name="options_raw" rows="5" placeholder="Option 1&#10;Option 2&#10;Option 3"><?php
                if (!empty($editQ['options_json'])) echo implode("\n", json_decode($editQ['options_json'],true));
              ?></textarea>
            </div>

            <div class="pco-form-group">
              <label>Help / Hint Text</label>
              <input type="text" name="help_text" value="<?= e($editQ['help_text']??'') ?>" placeholder="Shown below the question">
            </div>

            <div class="pco-form-group">
              <label>Disqualify If Answer Equals <span style="font-size:.75rem;color:var(--pco-grey-500);">(leave blank for none)</span></label>
              <input type="text" name="disqualify_value"
                     value="<?php echo $editQ['disqualify_if'] ? (json_decode($editQ['disqualify_if'],true)['answer']??'') : '' ?>"
                     placeholder="e.g. true  or  Yes — option text">
              <span class="hint">For boolean questions use: <code>true</code> or <code>false</code></span>
            </div>

            <div class="pco-form-group">
              <label>Validation Rule <span style="font-size:.75rem;color:var(--pco-grey-500);">(optional)</span></label>
              <input type="text" name="validation_rule" value="<?= e($editQ['validation_rule']??'') ?>" placeholder="e.g. min:18|max:120">
            </div>

            <button type="submit" class="pco-btn pco-btn--primary pco-btn--full">
              <?= $editQ ? 'Save Changes' : 'Add Question' ?>
            </button>
          </form>
        </div>
      </div>

      <?php else: ?>
      <!-- Help card when no form open -->
      <div class="pco-card">
        <div class="pco-card__head"><h3>Quick Reference</h3></div>
        <div class="pco-card__body" style="font-size:.845rem;">
          <div style="margin-bottom:1rem;">
            <strong>Question Types</strong>
            <?php foreach ([
              'text'     => 'Single-line free text',
              'textarea' => 'Multi-line free text',
              'radio'    => 'Single choice (one answer)',
              'checkbox' => 'Multiple choice',
              'select'   => 'Dropdown list',
              'number'   => 'Numeric input',
              'date'     => 'Date picker',
              'boolean'  => 'Yes / No toggle',
            ] as $t=>$d): ?>
            <div style="display:flex;gap:.5rem;margin-top:.4rem;">
              <code style="background:var(--pco-grey-100);padding:1px 6px;border-radius:4px;font-size:.75rem;min-width:70px;"><?= $t ?></code>
              <span style="color:var(--pco-grey-600);"><?= $d ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div>
            <strong>Disqualifiers</strong>
            <p style="color:var(--pco-grey-500);margin:.3rem 0 0;line-height:1.55;">If a patient gives the disqualifying answer, the consultation is automatically rejected with a safe clinical message.</p>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>

<!-- Template meta modal -->
<div id="tmplMetaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;border-radius:var(--pco-r-xl);max-width:500px;width:100%;box-shadow:var(--pco-shadow-lg);">
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--pco-grey-200);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;font-family:var(--pco-font-body);font-weight:700;">Edit Template Info</h3>
      <button onclick="document.getElementById('tmplMetaModal').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--pco-grey-500);">✕</button>
    </div>
    <div style="padding:1.5rem;">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_template">
        <div class="pco-form-group">
          <label>Title</label>
          <input type="text" name="title" value="<?= e($template['title']) ?>" required>
        </div>
        <div class="pco-form-group">
          <label>Description / intro</label>
          <textarea name="description" rows="3"><?= e($template['description']) ?></textarea>
        </div>
        <div class="pco-form-group">
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;">
            <input type="checkbox" name="is_active" value="1" style="width:auto;accent-color:var(--pco-purple);" <?= $template['is_active']?'checked':'' ?>>
            Active (patients can start this questionnaire)
          </label>
        </div>
        <button type="submit" class="pco-btn pco-btn--primary">Save</button>
      </form>
    </div>
  </div>
</div>

<!-- SortableJS for drag-and-drop -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
// Drag-and-drop reorder
const list = document.getElementById('questionsList');
if (list) {
  Sortable.create(list, {
    handle: '.fa-grip-vertical',
    animation: 150,
    ghostClass: 'bg-grey',
    onEnd: function() {
      const ids = [...document.querySelectorAll('.q-row')].map(el => el.dataset.id);
      fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(ids))
             + '&_pco_csrf=<?= csrf_token() ?>'
      }).then(r => r.json()).then(d => {
        if (d.success) PCO.notify('Order saved', 'success');
      });
    }
  });
}

// Show/hide options textarea based on question type
function toggleOptions() {
  const t   = document.getElementById('qType')?.value;
  const grp = document.getElementById('optionsGroup');
  if (grp) grp.style.display = ['radio','checkbox','select'].includes(t) ? '' : 'none';
}
toggleOptions();

// Auto-generate question key from question text
document.querySelector('[name="question_text"]')?.addEventListener('input', function() {
  const kf = document.querySelector('[name="question_key"]');
  if (kf && !kf.dataset.manual) {
    kf.value = this.value.toLowerCase()
      .replace(/[^a-z0-9\s]/g,'').replace(/\s+/g,'_').slice(0,60);
  }
});
document.querySelector('[name="question_key"]')?.addEventListener('input', function() {
  this.dataset.manual = '1';
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
