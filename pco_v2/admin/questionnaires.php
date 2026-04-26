<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');
    if ($action === 'toggle') {
        $id = (int)($_POST['tmpl_id'] ?? 0);
        $t  = Database::fetchOne("SELECT is_active FROM questionnaire_templates WHERE id=?",[$id]);
        if ($t) { Database::update('questionnaire_templates',['is_active'=>$t['is_active']?0:1],['id'=>$id]); flash_set('success','Template updated.'); }
        redirect('/pages/admin/questionnaires.php');
    }
    if ($action === 'add_template') {
        $condId = (int)($_POST['condition_id'] ?? 0);
        $title  = clean($_POST['title'] ?? '');
        $desc   = clean($_POST['description'] ?? '');
        if (!$condId || !$title) { flash_set('error','Condition and title required.'); redirect('/pages/admin/questionnaires.php?add=1'); }
        $version = (Database::fetchOne("SELECT MAX(version) v FROM questionnaire_templates WHERE condition_id=?",[$condId])['v'] ?? 0) + 1;
        $newId = Database::insert('questionnaire_templates',['condition_id'=>$condId,'version'=>$version,'title'=>$title,'description'=>$desc,'is_active'=>1,'created_at'=>date('Y-m-d H:i:s')]);
        audit_log('questionnaire_created','questionnaire_template',$newId,['title'=>$title]);
        flash_set('success','Template created. Now add questions.');
        redirect('/pages/admin/questionnaire-edit.php?id='.$newId);
    }
}

$templates = Database::fetchAll(
    "SELECT qt.*, c.name cond_name, c.gender, COUNT(qq.id) q_count
     FROM questionnaire_templates qt
     JOIN conditions c ON c.id=qt.condition_id
     LEFT JOIN questionnaire_questions qq ON qq.template_id=qt.id
     GROUP BY qt.id ORDER BY c.sort_order, qt.version DESC"
);

$conditions = Database::fetchAll("SELECT id, name, gender FROM conditions WHERE is_active=1 ORDER BY sort_order");
$showAdd    = isset($_GET['add']);

$page_title = 'Questionnaires';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Questionnaire Templates</h1>
    <?php if (!$showAdd): ?>
    <a href="?add=1" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-plus"></i> New Template</a>
    <?php endif; ?>
  </div>

  <?= flash_render() ?>

  <?php if ($showAdd): ?>
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3>Create New Questionnaire Template</h3></div>
    <div class="pco-card__body">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_template">
        <div class="grid-x grid-margin-x">
          <div class="cell medium-5">
            <div class="pco-form-group">
              <label>Condition *</label>
              <select name="condition_id" required>
                <option value="">— Select condition —</option>
                <?php foreach ($conditions as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= ucfirst($c['gender']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-7">
            <div class="pco-form-group">
              <label>Title *</label>
              <input type="text" name="title" required placeholder="e.g. Men's Weight Loss Assessment">
            </div>
          </div>
          <div class="cell small-12">
            <div class="pco-form-group">
              <label>Description / intro text</label>
              <textarea name="description" rows="2" placeholder="Shown to patients at the start of the questionnaire…"></textarea>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button type="submit" class="pco-btn pco-btn--primary">Create &amp; Add Questions</button>
          <a href="<?= APP_URL ?>/pages/admin/questionnaires.php" class="pco-btn pco-btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Template</th><th>Condition</th><th>Ver.</th><th>Questions</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($templates as $t): ?>
          <tr>
            <td>
              <strong><?= e($t['title']) ?></strong>
              <?php if ($t['description']): ?>
              <div style="font-size:.78rem;color:var(--pco-grey-500);max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($t['description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.845rem;"><?= e($t['cond_name']) ?> <span style="color:var(--pco-grey-500);">(<?= $t['gender'] ?>)</span></td>
            <td>v<?= $t['version'] ?></td>
            <td><span class="pco-badge <?= $t['q_count']>0?'badge--green':'badge--amber' ?>"><?= $t['q_count'] ?> question<?= $t['q_count']!=1?'s':'' ?></span></td>
            <td><?= $t['is_active']?'<span class="pco-badge badge--green">Active</span>':'<span class="pco-badge badge--grey">Draft</span>' ?></td>
            <td>
              <div style="display:flex;gap:.3rem;">
                <a href="<?= APP_URL ?>/pages/admin/questionnaire-edit.php?id=<?= $t['id'] ?>" class="pco-btn pco-btn--primary pco-btn--sm">Edit Questions</a>
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="tmpl_id" value="<?= $t['id'] ?>">
                  <button class="pco-btn pco-btn--ghost pco-btn--sm"><?= $t['is_active']?'Deactivate':'Activate' ?></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
