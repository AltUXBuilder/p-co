<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'toggle') {
        $id = (int)($_POST['prod_id'] ?? 0);
        $p  = Database::fetchOne("SELECT is_active FROM products WHERE id=?",[$id]);
        if ($p) { Database::update('products',['is_active'=>$p['is_active']?0:1,'updated_at'=>date('Y-m-d H:i:s')],['id'=>$id]); flash_set('success','Product updated.'); }
        redirect('/pages/admin/products.php' . (isset($_GET['condition_id'])?'?condition_id='.(int)$_GET['condition_id']:''));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['prod_id'] ?? 0);
        $p  = Database::fetchOne("SELECT image_path FROM products WHERE id=?",[$id]);
        if ($p) { if ($p['image_path']) delete_upload($p['image_path']); Database::query("DELETE FROM products WHERE id=?",[$id]); flash_set('success','Product deleted.'); audit_log('product_deleted','product',$id); }
        redirect('/pages/admin/products.php');
    }

    if (in_array($action,['add','edit'])) {
        $id = (int)($_POST['prod_id'] ?? 0);
        $errors = [];
        $d = [
            'condition_id'          => (int)($_POST['condition_id'] ?? 0),
            'sku'                   => strtoupper(clean($_POST['sku'] ?? '')),
            'name'                  => clean($_POST['name'] ?? ''),
            'brand'                 => clean($_POST['brand'] ?? 'Generic'),
            'description'           => clean($_POST['description'] ?? ''),
            'dosage_form'           => clean($_POST['dosage_form'] ?? ''),
            'strength'              => clean($_POST['strength'] ?? ''),
            'price'                 => (float)str_replace(',','',clean($_POST['price'] ?? '0')),
            'requires_prescription' => (int)!empty($_POST['requires_prescription']),
            'stock_qty'             => (int)($_POST['stock_qty'] ?? 0),
            'sort_order'            => (int)($_POST['sort_order'] ?? 0),
            'is_active'             => (int)!empty($_POST['is_active']),
        ];
        if (!$d['condition_id']) $errors[] = 'Condition required.';
        if (!$d['name'])         $errors[] = 'Product name required.';
        if (!$d['sku'])          $errors[] = 'SKU required.';
        if ($d['price'] < 0)     $errors[] = 'Price cannot be negative.';

        $dupSku = Database::fetchOne("SELECT id FROM products WHERE sku=?".($id?" AND id!=?":''), $id?[$d['sku'],$id]:[$d['sku']]);
        if ($dupSku) $errors[] = 'SKU already exists.';

        if (!$errors) {
            $img = handle_image_upload('product_image','products');
            if ($img['error']) { $errors[] = $img['error']; }
            else {
                if ($img['path']) {
                    if ($id) { $old = Database::fetchOne("SELECT image_path FROM products WHERE id=?",[$id]); if ($old['image_path']) delete_upload($old['image_path']); }
                    $d['image_path'] = $img['path'];
                }
                $d['updated_at'] = date('Y-m-d H:i:s');
                if ($action === 'add') {
                    $d['created_at'] = date('Y-m-d H:i:s');
                    $newId = Database::insert('products', $d);
                    audit_log('product_created','product',$newId,['name'=>$d['name'],'sku'=>$d['sku']]);
                    flash_set('success','Product "'.$d['name'].'" created.');
                } else {
                    Database::update('products', $d, ['id'=>$id]);
                    audit_log('product_updated','product',$id);
                    flash_set('success','Product updated.');
                }
                redirect('/pages/admin/products.php' . ($d['condition_id']?'?condition_id='.$d['condition_id']:''));
            }
        }
    }
}

$conditionId = (int)($_GET['condition_id'] ?? 0);
$search      = clean($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($conditionId) { $where .= " AND p.condition_id=?"; $params[] = $conditionId; }
if ($search)      { $where .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }

$products = Database::fetchAll(
    "SELECT p.*, c.name cond_name FROM products p
     JOIN conditions c ON c.id=p.condition_id
     $where ORDER BY c.sort_order, p.sort_order, p.name", $params
);

$conditions  = Database::fetchAll("SELECT id, name, gender FROM conditions ORDER BY sort_order, name");
$editProduct = isset($_GET['edit']) ? Database::fetchOne("SELECT * FROM products WHERE id=?",[(int)$_GET['edit']]) : null;
$showForm    = isset($_GET['add']) || $editProduct;

$page_title = 'Products';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Products</h1>
    <?php if (!$showForm): ?>
    <a href="?add=1<?= $conditionId?'&condition_id='.$conditionId:'' ?>" class="pco-btn pco-btn--primary pco-btn--sm">
      <i class="fa-solid fa-plus"></i> Add Product
    </a>
    <?php endif; ?>
  </div>

  <?= flash_render() ?>

  <!-- Add/Edit form -->
  <?php if ($showForm): ?>
  <div class="pco-card" style="margin-bottom:1.5rem;">
    <div class="pco-card__head"><h3><?= $editProduct ? 'Edit Product' : 'New Product' ?></h3></div>
    <div class="pco-card__body">
      <?php if (!empty($errors)): ?>
      <div class="pco-alert pco-alert--error"><?= implode('<br>',array_map('e',$errors)) ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editProduct?'edit':'add' ?>">
        <?php if ($editProduct): ?><input type="hidden" name="prod_id" value="<?= $editProduct['id'] ?>"><?php endif; ?>
        <div class="grid-x grid-margin-x">
          <div class="cell medium-8">
            <div class="pco-form-group">
              <label>Condition *</label>
              <select name="condition_id" required>
                <option value="">— Select condition —</option>
                <?php foreach ($conditions as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editProduct['condition_id']??$conditionId)==$c['id']?'selected':'' ?>>
                  <?= e($c['name']) ?> (<?= ucfirst($c['gender']) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>SKU *</label>
              <input type="text" name="sku" value="<?= e($editProduct['sku']??'') ?>" required placeholder="WLM-SEM-1">
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Product Name *</label>
              <input type="text" name="name" value="<?= e($editProduct['name']??'') ?>" required>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Brand</label>
              <input type="text" name="brand" value="<?= e($editProduct['brand']??'Generic') ?>" placeholder="Generic">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Dosage Form</label>
              <select name="dosage_form">
                <?php foreach (['Tablet','Capsule','Solution','Cream','Gel','Injection','Spray','Patch','Other'] as $f): ?>
                <option <?= ($editProduct['dosage_form']??'')===$f?'selected':'' ?>><?= $f ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Strength</label>
              <input type="text" name="strength" value="<?= e($editProduct['strength']??'') ?>" placeholder="50mg">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Price (£) *</label>
              <input type="number" name="price" value="<?= e($editProduct['price']??'0.00') ?>" min="0" step="0.01" required>
            </div>
          </div>
          <div class="cell small-12">
            <div class="pco-form-group">
              <label>Description</label>
              <textarea name="description" rows="2"><?= e($editProduct['description']??'') ?></textarea>
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Stock Qty</label>
              <input type="number" name="stock_qty" value="<?= e($editProduct['stock_qty']??0) ?>" min="0">
            </div>
          </div>
          <div class="cell medium-4">
            <div class="pco-form-group">
              <label>Sort Order</label>
              <input type="number" name="sort_order" value="<?= e($editProduct['sort_order']??0) ?>" min="0">
            </div>
          </div>
          <div class="cell medium-4" style="padding-top:1.5rem;">
            <div class="pco-form-group">
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;margin-bottom:.5rem;">
                <input type="checkbox" name="requires_prescription" value="1" style="width:auto;accent-color:var(--pco-purple);" <?= ($editProduct['requires_prescription']??1)?'checked':'' ?>>
                Requires prescription
              </label>
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:400;">
                <input type="checkbox" name="is_active" value="1" style="width:auto;accent-color:var(--pco-purple);" <?= ($editProduct['is_active']??1)?'checked':'' ?>>
                Active / visible
              </label>
            </div>
          </div>
          <div class="cell medium-6">
            <div class="pco-form-group">
              <label>Product Image</label>
              <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp" style="padding:.4rem;">
              <span class="hint">JPG, PNG or WebP · Max 5 MB</span>
              <?php if ($editProduct && $editProduct['image_path']): ?>
              <div style="margin-top:.5rem;">
                <img src="<?= APP_URL.e($editProduct['image_path']) ?>" style="height:60px;border-radius:8px;border:1px solid var(--pco-grey-200);">
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button type="submit" class="pco-btn pco-btn--primary"><?= $editProduct ? 'Save Changes' : 'Create Product' ?></button>
          <a href="<?= APP_URL ?>/pages/admin/products.php<?= $conditionId?'?condition_id='.$conditionId:'' ?>" class="pco-btn pco-btn--ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter bar -->
  <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;align-items:center;">
    <form method="GET" style="display:flex;gap:.4rem;flex-wrap:wrap;">
      <select name="condition_id" onchange="this.form.submit()" style="font-size:.855rem;padding:.4rem .7rem;border:1.5px solid var(--pco-grey-200);border-radius:var(--pco-r);">
        <option value="">All conditions</option>
        <?php foreach ($conditions as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $conditionId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products…" style="font-size:.855rem;padding:.4rem .7rem;max-width:220px;">
      <button type="submit" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-search"></i></button>
    </form>
    <span style="font-size:.8rem;color:var(--pco-grey-500);margin-left:auto;"><?= count($products) ?> product<?= count($products)!=1?'s':'' ?></span>
  </div>

  <div class="pco-card">
    <?php if (empty($products)): ?>
    <div class="pco-card__body text-center" style="padding:2.5rem;">
      <p style="color:var(--pco-grey-500);">No products found.</p>
    </div>
    <?php else: ?>
    <div class="pco-table-wrap">
      <table class="pco-table">
        <thead><tr><th>Image</th><th>Name</th><th>SKU</th><th>Condition</th><th>Price</th><th>Stock</th><th>Rx</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td style="width:50px;">
              <?php if ($p['image_path']): ?>
              <img src="<?= APP_URL.e($p['image_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--pco-grey-200);">
              <?php else: ?>
              <div style="width:40px;height:40px;background:var(--pco-grey-100);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--pco-grey-300);">
                <i class="fa-solid fa-pills"></i>
              </div>
              <?php endif; ?>
            </td>
            <td><strong><?= e($p['name']) ?></strong><?= $p['brand']&&$p['brand']!=='Generic'?' <span style="color:var(--pco-grey-500);font-size:.8rem;">('.e($p['brand']).')</span>':'' ?><br><span style="font-size:.78rem;color:var(--pco-grey-500);"><?= e($p['strength']) ?> · <?= e($p['dosage_form']) ?></span></td>
            <td style="font-family:monospace;font-size:.8rem;"><?= e($p['sku']) ?></td>
            <td style="font-size:.82rem;"><?= e($p['cond_name']) ?></td>
            <td style="font-weight:600;"><?= money($p['price']) ?></td>
            <td><?= $p['stock_qty'] ?></td>
            <td><?= $p['requires_prescription']?'<span class="pco-badge badge--purple">Rx</span>':'<span class="pco-badge badge--grey">OTC</span>' ?></td>
            <td><?= $p['is_active']?'<span class="pco-badge badge--green">Active</span>':'<span class="pco-badge badge--grey">Hidden</span>' ?></td>
            <td>
              <div style="display:flex;gap:.3rem;">
                <a href="?edit=<?= $p['id'] ?>" class="pco-btn pco-btn--ghost pco-btn--sm"><i class="fa-solid fa-pen"></i></a>
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
                  <button class="pco-btn pco-btn--ghost pco-btn--sm"><?= $p['is_active']?'<i class="fa-solid fa-eye-slash"></i>':'<i class="fa-solid fa-eye"></i>' ?></button>
                </form>
                <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
                  <button class="pco-btn pco-btn--danger pco-btn--sm" data-confirm="Delete '<?= e($p['name']) ?>'? This cannot be undone."><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
