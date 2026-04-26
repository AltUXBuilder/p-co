<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$page_title = 'Privacy Policy';
include APP_PATH . '/includes/header.php';
?>
<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:3rem 0;">
  <div class="grid-container"><h1 style="color:white;font-size:2rem;">Privacy Policy</h1><p style="color:rgba(255,255,255,.7);">Last updated: <?= date('F Y') ?></p></div>
</section>
<div class="pco-page"><div class="grid-container"><div class="grid-x align-center"><div class="cell large-8">
<div class="pco-card"><div class="pco-card__body" style="font-size:.925rem;line-height:1.8;">

<?php foreach ([
  ['Who We Are', 'Prescribe &amp; Co. (&ldquo;P&amp;Co.&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;) is a GPhC-registered online pharmacy operating under registration number '.GPHC_NUMBER.'. Our registered address is '.PHARMACY_ADDRESS.'. We are committed to protecting and respecting your privacy.'],
  ['What Data We Collect', 'We collect the following categories of personal data: identity data (name, date of birth, gender, NHS number), contact data (email address, phone number, delivery address), health data (consultation answers, prescribed medications, medical history you provide), transaction data (orders placed, payment references), and technical data (IP address, browser type, session data).'],
  ['How We Use Your Data', 'We use your data to provide our online pharmacy service, including processing consultations, issuing prescriptions, fulfilling orders and communicating with you about your healthcare. We may also use aggregated, anonymised data to improve our services. We rely on your explicit consent and legitimate interests as our legal basis for processing health data.'],
  ['Who We Share Data With', 'We share your data only with: our prescribing clinicians (for consultation review), our dispensing pharmacists (for medication preparation), delivery carriers (name and address only), and our technical service providers operating under strict data processing agreements. We never sell your data to third parties or share it for marketing purposes.'],
  ['Your Rights', 'Under UK GDPR you have the right to: access your personal data, correct inaccurate data, request deletion of your data (subject to clinical record-keeping obligations), object to processing, and data portability. To exercise any of these rights, contact us at '.PHARMACY_EMAIL.'.'],
  ['Retention', 'We retain clinical records for a minimum of 8 years in line with NHS guidelines. Non-clinical data is retained for 3 years after your last interaction with us, unless you request earlier deletion.'],
  ['Cookies', 'We use only essential session cookies required for the operation of our service. We do not use tracking, analytics or marketing cookies.'],
  ['Contact', 'For any privacy concerns or to exercise your rights, contact our Data Protection team at '.PHARMACY_EMAIL.' or write to us at '.PHARMACY_ADDRESS.'. You also have the right to lodge a complaint with the ICO at ico.org.uk.'],
] as [$heading, $body]): ?>
<h3 style="font-family:var(--pco-font-body);font-weight:700;font-size:1rem;margin:1.75rem 0 .5rem;color:var(--pco-purple);"><?= $heading ?></h3>
<p style="margin:0;"><?= $body ?></p>
<?php endforeach; ?>

</div></div>
</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
