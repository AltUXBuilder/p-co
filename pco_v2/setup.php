<?php
/**
 * Prescribe & Co. — One-Click Setup Script
 * Run once: https://yourdomain.co.uk/setup.php
 * DELETE THIS FILE AFTER SETUP IS COMPLETE.
 */

define('SETUP_KEY', 'CHANGE_THIS_SECRET_KEY'); // ← Change before running!

$errors   = [];
$warnings = [];
$success  = [];
$done     = false;

// ── Verify secret key ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['setup_key'] ?? '') !== SETUP_KEY) {
        $errors[] = 'Invalid setup key.';
    }
}

// ── Check PHP requirements ─────────────────────────────────────────
$checks = [
    ['PDO extension',     extension_loaded('pdo'),             true],
    ['PDO MySQL',         extension_loaded('pdo_mysql'),        true],
    ['JSON extension',    extension_loaded('json'),             true],
    ['OpenSSL extension', extension_loaded('openssl'),          true],
    ['PHP >= 8.1',        version_compare(PHP_VERSION,'8.1','>='), true],
    ['mbstring',          extension_loaded('mbstring'),         false],
];

// ── Try DB connection ──────────────────────────────────────────────
function try_db(string $host, string $db, string $user, string $pass): array {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return ['ok'=>true,'pdo'=>$pdo];
    } catch (PDOException $e) {
        return ['ok'=>false,'error'=>$e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && empty($errors)) {
    // Read config values from POST
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';

    $result = try_db($dbHost,$dbName,$dbUser,$dbPass);
    if (!$result['ok']) {
        $errors[] = 'Database connection failed: '.$result['error'];
    } else {
        $pdo = $result['pdo'];
        // Run schema
        $sql = file_get_contents(__DIR__.'/database.sql');
        if (!$sql) {
            $errors[] = 'Could not read database.sql';
        } else {
            try {
                $pdo->exec($sql);
                $success[] = 'Database schema imported successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(),'already exists') !== false) {
                    $warnings[] = 'Some tables already exist — schema may have been partially applied.';
                } else {
                    $errors[] = 'Schema import error: '.$e->getMessage();
                }
            }
        }

        if (empty($errors)) {
            // Write config
            $appUrl  = rtrim($_POST['app_url']??'','/');;
            $secret  = bin2hex(random_bytes(32));
            $configContent = file_get_contents(__DIR__.'/config/config.php');
            $configContent = str_replace("'your_db_user'",    "'".addslashes($dbUser)."'", $configContent);
            $configContent = str_replace("'your_db_password'","'".addslashes($dbPass)."'", $configContent);
            $configContent = str_replace("prescribeco_db",     $dbName,                     $configContent);
            $configContent = str_replace('https://yourdomain.co.uk', $appUrl,               $configContent);
            $configContent = str_replace('REPLACE_WITH_64_RANDOM_CHARS_MINIMUM', $secret,   $configContent);

            if (file_put_contents(__DIR__.'/config/config.php', $configContent)) {
                $success[] = 'Config file updated with your database credentials and a fresh secret key.';
            } else {
                $warnings[] = 'Could not write config.php automatically. Please update it manually with your DB credentials.';
            }

            // Create uploads / logs dirs
            foreach (['uploads','logs'] as $dir) {
                $path = __DIR__.'/'.$dir;
                if (!is_dir($path)) mkdir($path, 0755, true);
                file_put_contents($path.'/.htaccess', "Order allow,deny\nDeny from all\n");
            }
            $success[] = 'Uploads and logs directories created.';
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup — Prescribe &amp; Co.</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:system-ui,sans-serif;background:#1A1A2E;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
    .box{background:#fff;color:#1A1A2E;border-radius:14px;padding:2.5rem;max-width:600px;width:100%;}
    h1{font-size:1.6rem;margin-bottom:.25rem;}
    p.sub{color:#8A8A9E;font-size:.875rem;margin-bottom:1.75rem;}
    .badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:700;margin:2px;}
    .ok{background:#E8F5ED;color:#1A7A4A;}
    .fail{background:#FDEEEC;color:#C0392B;}
    .warn{background:#FEF3C7;color:#B45309;}
    .group{margin-bottom:1.1rem;}
    label{display:block;font-weight:600;font-size:.845rem;margin-bottom:.35rem;}
    input{width:100%;padding:.55rem .85rem;border:1.5px solid #D0D0DC;border-radius:8px;font-size:.9rem;}
    input:focus{outline:none;border-color:#A884D4;}
    .btn{display:inline-block;padding:.65rem 1.8rem;background:#7B5EA7;color:#fff;border:none;border-radius:999px;font-weight:600;cursor:pointer;font-size:.9rem;}
    .alert{padding:.8rem 1rem;border-radius:8px;margin-bottom:.75rem;font-size:.875rem;}
    .alert-error{background:#FDEEEC;color:#7f1d1d;border-left:3px solid #C0392B;}
    .alert-success{background:#E8F5ED;color:#065f46;border-left:3px solid #1A7A4A;}
    .alert-warning{background:#FEF3C7;color:#78350f;border-left:3px solid #B45309;}
    .check-table{width:100%;border-collapse:collapse;margin-bottom:1.25rem;font-size:.845rem;}
    .check-table td{padding:.4rem .6rem;border-bottom:1px solid #E8E8EE;}
    code{background:#F4F4F6;padding:2px 6px;border-radius:4px;font-size:.8rem;}
  </style>
</head>
<body>
<div class="box">
  <h1>Prescribe &amp; Co. Setup</h1>
  <p class="sub">One-time installer. Delete <code>setup.php</code> immediately after completion.</p>

  <!-- PHP checks -->
  <h3 style="font-size:.9rem;margin-bottom:.75rem;font-weight:700;">System Requirements</h3>
  <table class="check-table">
    <?php foreach ($checks as [$name,$ok,$required]): ?>
    <tr>
      <td><?= $name ?><?= $required ? ' <span style="color:#C0392B">*</span>' : '' ?></td>
      <td><?= $ok ? '<span class="badge ok">✓ OK</span>' : '<span class="badge '.($required?'fail':'warn').'">'.((!$ok&&!$required)?'Optional':'✗ Missing').'</span>' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <?php foreach ($errors as $e): ?>
  <div class="alert alert-error">✗ <?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
  <?php foreach ($warnings as $w): ?>
  <div class="alert alert-warning">⚠ <?= htmlspecialchars($w) ?></div>
  <?php endforeach; ?>
  <?php foreach ($success as $s): ?>
  <div class="alert alert-success">✓ <?= htmlspecialchars($s) ?></div>
  <?php endforeach; ?>

  <?php if ($done): ?>
  <div class="alert alert-success" style="margin-top:1rem;font-size:1rem;">
    <strong>✓ Setup complete!</strong><br>
    <strong style="color:#C0392B;">Delete <code>setup.php</code> immediately.</strong><br>
    Default passwords are <code>PrescribeCo@2024!</code> — change them now.
  </div>
  <?php else: ?>

  <form method="POST">
    <div class="group">
      <label>Application URL (no trailing slash)</label>
      <input name="app_url" value="<?= htmlspecialchars($_POST['app_url']??'https://') ?>" required placeholder="https://yourdomain.co.uk">
    </div>
    <div class="group">
      <label>DB Host</label>
      <input name="db_host" value="<?= htmlspecialchars($_POST['db_host']??'localhost') ?>" required>
    </div>
    <div class="group">
      <label>DB Name</label>
      <input name="db_name" value="<?= htmlspecialchars($_POST['db_name']??'prescribeco_db') ?>" required>
    </div>
    <div class="group">
      <label>DB Username</label>
      <input name="db_user" value="<?= htmlspecialchars($_POST['db_user']??'') ?>" required>
    </div>
    <div class="group">
      <label>DB Password</label>
      <input type="password" name="db_pass" value="">
    </div>
    <div class="group">
      <label>Setup Key <small>(set in setup.php line 10)</small></label>
      <input type="password" name="setup_key" required>
    </div>
    <button type="submit" class="btn">Run Setup</button>
  </form>

  <?php endif; ?>
</div>
</body>
</html>
