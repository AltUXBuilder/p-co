<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'toggle') {
        $id = (int)($_POST['cond_id'] ?? 0);
        $c  = Database::fetchOne("SELECT * FROM conditions WHERE id=?",[$id]);
        if ($c) { Database::update('conditions',['is_active'=>$c['is_active']?0:1],['id'=>$id]); flash_set('success','Condition updated.'); }
        redirect('/pages/admin/conditions.php');
    }

    if (in_array($action,['add','edit'])) {
        $id     = (int)($_POST['cond_id'] ?? 0);
        $errors = [];
        $d = [
            'name'        => clean($_POST['name']        ?? ''),
            'slug'        => strtolower(preg_replace('/[^a-z0-9]+/','-',clean($_POST['slug'] ?? ''))),
            'gender'      => clean($_POST['gender']      ?? 'all'),
            'description' => clean($_POST['description'] ?? ''),
            'icon'        => clean($_POST['icon']        ?? 'stethoscope'),
            'sort_order'  => (int)($_POST['sort_order']  ?? 0),
            'is_active'   => (int)!empty($_POST['is_active']),
        ];
        if (!$d['name']) $errors[] = 'Name required.';
        if (!$d['slug']) $errors[] = 'Slug required.';

        $dupSlug = Database::fetchOne("SELECT id FROM conditions WHERE slug=?".($id?" AND id!=?":''), $id?[$d['slug'],$id]:[$d['slug']]);
        if ($dupSlug) $errors[] = 'Slug already in use.';

        if (!$errors) {
            // Handle image upload
            $img = handle_image_upload('condition_image','conditions');
            if ($img['error']) { $errors[] = $img['error']; }
            else {
                if ($img['path']) {
                    // Delete old image if editing
                    if ($id) { $old = Database::fetchOne("SELECT image_path FROM conditions WHERE id=?",[$id]); if ($old['image_path']) delete_upload($old['image_path']); }
                    $d['image_path'] = $img['path'];
                }
                if ($action === 'add') {
                    $d['created_at'] = date('Y-m-d H:i:s');
                    $newId = Database::insert('conditions', $d);
                    audit_log('condition_created','condition',$newId,['name'=>$d['name']]);
                    flash_set('success','Condition "'.$d['name'].'" created.');
                } else {
                    Database::update('conditions', $d, ['id'=>$id]);
                    audit_log('condition_updated','condition',$id);
                    flash_set('success','Condition updated.');
                }
                redirect('/pages/admin/conditions.php');
            }
        }
    }
}

$conditions = Database::fetchAll(
    "SELECT c.*, COUNT(p.id) prod_count FROM conditions c
     LEFT JOIN products p ON p.condition_id=c.id AND p.is_active=1
     GROUP BY c.id ORDER BY c.sort_order, c.name"
);

$editCond = isset($_GET['edit']) ? Database::fetchOne("SELECT * FROM conditions WHERE id=?",[(int)$_GET['edit']]) : null;
$showForm = isset($_GET['add']) || $editCond;

$page_title = 'Conditions';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Conditions</h1>
    <?php if (!$showForm): ?>
    <a href="?add=1" class="pco-btn pco-btn--primary pco-btn--sm"><i class="fa-solid fa-plus"></i> Add Condition</a>
    <?php endif; ?>
  </div>

  <?= flash_render() ?>

  <?php if ($showForm): ?>
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3><?= $editCond ? 'Edit Condition' : 'New Condition' ?></h3></div>
    <div class="pco-card__body">
      <?php if (!empty($errors)): ?>
      <div class="pco-alert pco-alert--error"><?= implode('<br>',array_map('e',$errors)) ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editCond?'edit':'add' ?>">
        <?php if ($editCond): ?><input type="hidden" name="cond_id" value="<?= $editCond['id'] ?>"><?php endif; ?>

        <div class="grid-x grid-margin-x">
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Name *</label>
              <input type="text" name="name" value="<?= e($editCond['name']??'') ?>" required id="condName">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Slug * <span style="font-size:.75rem;color:var(--pco-grey-500);">(URL-safe, e.g. weight-loss-men)</span></label>
              <input type="text" name="slug" value="<?= e($editCond['slug']??'') ?>" required id="condSlug">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Gender</label>
              <select name="gender">
                <?php foreach (['male'=>'Male','female'=>'Female','all'=>'All'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($editCond['gender']??'all')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Font Awesome Icon <span style="font-size:.75rem;color:var(--pco-grey-500);">(e.g. heart-pulse)</span></label>
              <input type="text" name="icon" value="<?= e($editCond['icon']??'stethoscope') ?>" placeholder="stethoscope">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Sort Order</label>
              <input type="number" name="sort_order" value="<?= e($editCond['sort_order']??0) ?>" min="0">
            </div>
          </div>
          <div class="cell small-12">
            <div class="pco-form-group">
              <label>Description</label>
              <textarea name="description" rows="2"><?= e($editCond['description']??'') ?></textarea>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Condition Image</label>
              <input type="file" name="condition_image" accept="image/jpeg,image/png,image/webp" style="padding:.4rem;">
              <span class="hint">JPG, PNG or WebP · Max 5 MB</span>
              <?php if ($editCond && $editCond['image_path']): ?>
              <div style="margin-top:.5rem;">
                <img src="<?= APP_URL.e($editCond['image_path']) ?>" style="height:60px;border-radius:8px;border:1px solid var(--pco-grey-200);">
                <span style="font-size:.75rem;color:var(--pco-grey-500);display:block;margin-top:.2rem;">Current image — upload new to replace</span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group" style="padding-top:1.5rem;">
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;">
                <input type="checkbox" name="is_active" value="1" style="width:auto;accent-color:var(--pco-purple);"
                       <?= ($editCond['is_active']??1)?'checked':'' ?>>
                Active (visible to patients)
              </label>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button type="submit" class="pco-btn pco-btn--primary"><?= $editCond ? 'Save Changes' : 'Create Condition' ?></button>
          <a href="<?= APP_URL ?>/pages/admin/conditions.php" class="pco-btn pco-btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="pco-card">
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Image</th><th>Name</th><th>Slug</th><th>Gender</th><th>Products</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($conditions as $c): ?>
          <tr>
            <td style="width:56px;">
              <?php if ($c['image_path']): ?>
              <img src="<?= APP_URL.e($c['image_path']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--pco-grey-200);">
              <?php else: ?>
              <div style="width:44px;height:44px;background:var(--pco-lavender-tint);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--pco-purple);">
                <i class="fa-solid fa-<?= e($c['icon']??'stethoscope') ?>"></i>
              </div>
              <?php endif; ?>
            </td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td style="font-size:.8rem;font-family:monospace;color:var(--pco-grey-500);"><?= e($c['slug']) ?></td>
            <td><?= ucfirst($c['gender']) ?></td>
            <td><?= $c['prod_count'] ?> active</td>
            <td><?= $c['is_active'] ? '<span class="pco-badge badge--green">Active</span>' : '<span class="pco-badge badge--grey">Hidden</span>' ?></td>
            <td>
              <div style="display:flex;gap:.3rem;">
                <a href="?edit=<?= $c['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pen"></i></a>
                <a href="<?= APP_URL ?>/pages/admin/products.php?condition_id=<?= $c['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pills"></i></a>
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="cond_id" value="<?= $c['id'] ?>">
                  <button class="pco-btn pco-btn--ghost pco-btn--sm" data-confirm="Toggle visibility for '<?= e($c['name']) ?>'?">
                    <?= $c['is_active'] ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>' ?>
                  </button>
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

<script>
// Auto-generate slug from name
document.getElementById('condName')?.addEventListener('input', function() {
  const slug = document.getElementById('condSlug');
  if (slug && !slug.dataset.manual) {
    slug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  }
});
document.getElementById('condSlug')?.addEventListener('input', function() {
  this.dataset.manual = '1';
});
</script>

<?php include APP_PATH . '/includes/footer.php'; ?>
