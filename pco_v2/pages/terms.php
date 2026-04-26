<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$page_title = 'Terms of Service';
include APP_PATH . '/includes/header.php';
?>
<section style="background:linear-gradient(150deg,var(--pco-black) 0%,var(--pco-purple-deep) 100%);padding:3rem 0;">
  <div class="grid-container"><h1 style="color:white;font-size:2rem;">Terms of Service</h1><p style="color:rgba(255,255,255,.7);">Last updated: <?= date('F Y') ?></p></div>
</section>
<div class="pco-page"><div class="grid-container"><div class="grid-x align-center"><div class="cell large-8">
<div class="pco-card"><div class="pco-card__body" style="font-size:.925rem;line-height:1.8;">

<?php foreach ([
  ['1. Acceptance', 'By using the Prescribe &amp; Co. service you agree to these Terms of Service. If you do not agree, please do not use the service. We may update these terms from time to time and will notify you of material changes.'],
  ['2. Eligibility', 'You must be at least 18 years of age and resident in the United Kingdom to use this service. By registering you confirm you meet these requirements. You must provide accurate information at all times.'],
  ['3. The Service', 'Prescribe &amp; Co. provides an online consultation platform connecting patients with qualified UK prescribers. We do not guarantee that a prescription will be issued following any consultation. All prescribing decisions are made by independent clinicians.'],
  ['4. Medical Disclaimer', 'This service does not replace your NHS GP or regular healthcare provider. It is intended for specific, self-limiting conditions suitable for online assessment. In any medical emergency, dial 999 immediately. For non-urgent medical advice, contact NHS 111.'],
  ['5. Your Responsibilities', 'You must provide honest, accurate and complete answers to all consultation questions. Providing false information is dangerous and may result in clinically inappropriate medication being prescribed. We accept no liability for harm arising from inaccurate information provided by you.'],
  ['6. Prescriptions', 'All prescriptions are issued solely at the professional discretion of our prescribers. Prescriptions are valid for the period stated and cannot be transferred. Misuse of prescription medication is illegal and dangerous.'],
  ['7. Payments', 'Prices are displayed inclusive of VAT where applicable. Payment is due at time of ordering. Refunds are provided in accordance with our Refund Policy. Prescription medication cannot be returned once dispensed for safety reasons.'],
  ['8. Intellectual Property', 'All content on this site including text, images, branding and software is the intellectual property of Prescribe &amp; Co. Ltd. You may not reproduce, distribute or create derivative works without our written consent.'],
  ['9. Limitation of Liability', 'To the maximum extent permitted by law, our liability for any loss or damage arising from use of the service is limited to the amount you paid for the relevant transaction. We are not liable for indirect or consequential losses.'],
  ['10. Governing Law', 'These terms are governed by the laws of England and Wales. Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.'],
  ['11. Contact', 'Questions about these terms should be directed to '.PHARMACY_EMAIL.' or '.PHARMACY_ADDRESS.'.'],
] as [$heading,$body]): ?>
<h3 style="font-family:var(--pco-font-body);font-weight:700;font-size:1rem;margin:1.75rem 0 .5rem;color:var(--pco-purple);"><?= $heading ?></h3>
<p style="margin:0;"><?= $body ?></p>
<?php endforeach; ?>

</div></div>
</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
