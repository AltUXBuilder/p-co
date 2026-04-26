<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$success = false;
$errors  = [];
$d       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        'name'    => clean($_POST['name']    ?? ''),
        'email'   => strtolower(clean($_POST['email'] ?? '')),
        'subject' => clean($_POST['subject'] ?? ''),
        'message' => clean($_POST['message'] ?? ''),
    ];
    if (!$d['name'])                                   $errors[] = 'Name is required.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address required.';
    if (!$d['subject'])                                $errors[] = 'Subject is required.';
    if (strlen($d['message']) < 20)                    $errors[] = 'Message must be at least 20 characters.';

    if (!$errors) {
        Database::insert('contact_messages', [
            'name'       => $d['name'],
            'email'      => $d['email'],
            'subject'    => $d['subject'],
            'message'    => $d['message'],
            'ip_address' => client_ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        audit_log('contact_form_submitted', 'contact_message', null, ['email' => $d['email']]);
        $success = true;
        $d = [];
    }
}

$page_title = 'Contact Us';
$active_nav = '';
include APP_PATH . '/includes/header.php';
?>

<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:3.5rem 0;">
  <div class="grid-container">
    <h1 style="color:white;font-size:2.2rem;margin-bottom:.4rem;">Contact Us</h1>
    <p style="color:rgba(255,255,255,.7);">Get in touch with the Prescribe &amp; Co. team.</p>
  </div>
</section>

<div class="pco-page">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <div class="cell large-7" style="margin-bottom:2rem;">
    <div class="pco-card">
      <div class="pco-card__head"><h3>Send a message</h3></div>
      <div class="pco-card__body">

        <?php if ($success): ?>
        <div class="pco-alert pco-alert--success">
          <i class="fa-solid fa-circle-check"></i>
          <div><strong>Message received.</strong> We'll get back to you within 1–2 business days.</div>
        </div>
        <?php else: ?>

        <?php if (!empty($errors)): ?>
        <div class="pco-alert pco-alert--error">
          <i class="fa-solid fa-circle-xmark"></i>
          <div><?= implode('<br>', array_map('e', $errors)) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST">
          <?= csrf_field() ?>
          <div class="grid-x grid-margin-x">
            <div class="cell medium-6">
              <div class="pco-form-group">
                <label>Your name <span style="color:var(--pco-red)">*</span></label>
                <input type="text" name="name" value="<?= e($d['name']??'') ?>" required>
              </div>
            </div>
            <div class="cell medium-6">
              <div class="pco-form-group">
                <label>Email address <span style="color:var(--pco-red)">*</span></label>
                <input type="email" name="email" value="<?= e($d['email']??'') ?>" required>
              </div>
            </div>
          </div>
          <div class="pco-form-group">
            <label>Subject <span style="color:var(--pco-red)">*</span></label>
            <select name="subject">
              <option value="">— Select a topic —</option>
              <?php foreach (['General enquiry','My consultation','My prescription','My order','Technical issue','Medical question','Other'] as $s): ?>
              <option value="<?= e($s) ?>" <?= ($d['subject']??'')===$s?'selected':'' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pco-form-group">
            <label>Message <span style="color:var(--pco-red)">*</span></label>
            <textarea name="message" rows="6" required minlength="20"><?= e($d['message']??'') ?></textarea>
          </div>
          <div class="pco-alert pco-alert--warning" style="margin-bottom:1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>For medical emergencies call <strong>999</strong>. For non-urgent medical advice call <strong>NHS 111</strong>. Do not use this form for urgent health concerns.</span>
          </div>
          <button type="submit" class="pco-btn pco-btn--primary">
            <i class="fa-solid fa-paper-plane"></i> Send Message
          </button>
        </form>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="cell large-5">
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head"><h3>Other ways to reach us</h3></div>
      <div class="pco-card__body" style="font-size:.875rem;">
        <?php foreach ([
          ['envelope',   'Email',   PHARMACY_EMAIL],
          ['phone',      'Phone',   PHARMACY_PHONE],
          ['location-dot','Address', PHARMACY_ADDRESS],
          ['shield-halved','GPhC Reg.', GPHC_NUMBER],
        ] as [$ico,$lbl,$val]): ?>
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--pco-grey-100);">
          <i class="fa-solid fa-<?= $ico ?>" style="color:var(--pco-purple);width:16px;margin-top:.15rem;flex-shrink:0;"></i>
          <div>
            <div style="font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--pco-grey-500);margin-bottom:.2rem;"><?= $lbl ?></div>
            <div><?= e($val) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="pco-card">
      <div class="pco-card__head"><h3>Response times</h3></div>
      <div class="pco-card__body" style="font-size:.855rem;color:var(--pco-grey-700);">
        <p style="margin-bottom:.75rem;">We aim to respond to all messages within <strong>1–2 business days</strong>.</p>
        <p style="margin-bottom:.75rem;">Consultation reviews are typically completed within <strong>a few hours</strong> during working hours.</p>
        <p style="margin:0;font-size:.8rem;color:var(--pco-grey-500);">Monday–Friday: 9am–6pm<br>Saturday: 10am–2pm<br>Sunday: Closed</p>
      </div>
    </div>
  </div>

</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
