<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $keys = $_POST['keys'] ?? [];
    $vals = $_POST['vals'] ?? [];
    foreach ($keys as $i => $key) {
        $k = clean($key);
        $v = clean($vals[$i] ?? '');
        if ($k) Database::update('system_settings', ['setting_value'=>$v,'updated_at'=>date('Y-m-d H:i:s')], ['setting_key'=>$k]);
    }
    audit_log('settings_updated','system_settings',null,['count'=>count($keys)]);
    flash_set('success','Settings saved.');
    redirect('/pages/admin/settings.php');
}

$settings = Database::fetchAll("SELECT * FROM system_settings ORDER BY setting_key");

// Group settings
$groups = [
    'Pharmacy Identity'  => ['pharmacy_name','pharmacy_name_short','pharmacy_address','pharmacy_phone','pharmacy_email','gphc_number','site_tagline'],
    'Prescriptions'      => ['prescription_expiry_days','repeat_rx_enabled'],
    'Orders & Delivery'  => ['shipping_cost','free_shipping_threshold','orders_enabled'],
    'Labels'             => ['label_footer_text'],
    'Uploads'            => ['max_upload_size_mb','allowed_image_types'],
    'Payments'           => ['stripe_public_key','stripe_secret_key'],
    'Contact'            => ['contact_email'],
    'System'             => ['maintenance_mode'],
];

$settingMap = array_column($settings, null, 'setting_key');

$page_title = 'System Settings';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">
  <h1 style="font-size:1.55rem;margin-bottom:1.5rem;">System Settings</h1>
  <?= flash_render() ?>

  <form method="POST">
    <?= csrf_field() ?>
    <?php foreach ($groups as $groupName => $keys): ?>
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head"><h3><?= $groupName ?></h3></div>
      <div class="pco-card__body">
        <?php foreach ($keys as $key):
          $s = $settingMap[$key] ?? null;
          if (!$s) continue;
          $isSecret  = str_contains($key,'secret');
          $isBool    = in_array($key,['orders_enabled','repeat_rx_enabled','maintenance_mode']);
        ?>
        <div class="pco-form-group">
          <label><?= ucwords(str_replace('_',' ',$key)) ?>
            <?php if ($s['description']): ?>
            <span style="font-weight:400;color:var(--pco-grey-500);font-size:.78rem;">— <?= e($s['description']) ?></span>
            <?php endif; ?>
          </label>
          <input type="hidden" name="keys[]" value="<?= e($key) ?>">
          <?php if ($isBool): ?>
          <select name="vals[]">
            <option value="1" <?= $s['setting_value']=='1'?'selected':'' ?>>Enabled</option>
            <option value="0" <?= $s['setting_value']=='0'?'selected':'' ?>>Disabled</option>
          </select>
          <?php elseif ($isSecret): ?>
          <input type="password" name="vals[]" value="<?= e($s['setting_value']??'') ?>" placeholder="Leave blank to keep current" autocomplete="off">
          <?php else: ?>
          <input type="text" name="vals[]" value="<?= e($s['setting_value']??'') ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="display:flex;gap:.75rem;padding-bottom:2rem;">
      <button type="submit" class="pco-btn pco-btn--primary pco-btn--lg">
        <i class="fa-solid fa-floppy-disk"></i> Save All Settings
      </button>
    </div>
  </form>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
