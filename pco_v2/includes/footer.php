
<footer class="pco-footer">
  <div class="grid-container">
    <div class="grid-x grid-margin-x">

      <div class="cell large-4 medium-6">
        <span class="pco-footer__logo">Prescribe &amp; Co.</span>
        <p style="font-size:.855rem;line-height:1.65;">A GPhC-registered online pharmacy providing clinically-reviewed prescription treatments, delivered discreetly across the UK.</p>
        <div class="pco-footer__badges">
          <span class="pco-footer__badge"><i class="fa-solid fa-shield-halved"></i> GPhC Registered</span>
          <span class="pco-footer__badge"><i class="fa-solid fa-lock"></i> SSL Secured</span>
          <span class="pco-footer__badge"><i class="fa-solid fa-user-doctor"></i> UK Prescribers</span>
        </div>
        <p class="pco-footer__disclaimer" style="margin-top:.75rem;">GPhC Registration: <strong style="color:rgba(255,255,255,.8);"><?= GPHC_NUMBER ?></strong></p>
      </div>

      <div class="cell large-2 medium-3 small-6">
        <h5>Treatments</h5>
        <ul class="pco-footer__links">
          <li><a href="<?= APP_URL ?>/pages/conditions.php?gender=male">Men's Health</a></li>
          <li><a href="<?= APP_URL ?>/pages/conditions.php?gender=female">Women's Health</a></li>
          <li><a href="<?= APP_URL ?>/pages/condition.php?slug=weight-loss">Weight Loss</a></li>
          <li><a href="<?= APP_URL ?>/pages/condition.php?slug=erectile-dysfunction">Erectile Dysfunction</a></li>
          <li><a href="<?= APP_URL ?>/pages/condition.php?slug=skin-health">Skin Health</a></li>
        </ul>
      </div>

      <div class="cell large-2 medium-3 small-6">
        <h5>Help</h5>
        <ul class="pco-footer__links">
          <li><a href="<?= APP_URL ?>/pages/about.php">How It Works</a></li>
          <li><a href="<?= APP_URL ?>/pages/contact.php">Contact Us</a></li>
          <li><a href="<?= APP_URL ?>/pages/auth/login.php">Sign In</a></li>
          <li><a href="<?= APP_URL ?>/pages/auth/register.php">Register</a></li>
        </ul>
      </div>

      <div class="cell large-4 medium-12">
        <h5>Important Information</h5>
        <p class="pco-footer__disclaimer">
          Prescribe &amp; Co. is an online pharmacy registered with the General Pharmaceutical Council. 
          All prescriptions are reviewed by qualified UK prescribers. This service does not replace your regular GP. 
          In an emergency, dial 999 or visit your nearest A&amp;E.
        </p>
        <p class="pco-footer__disclaimer" style="margin-top:.5rem;">
          For non-emergency medical advice call <strong style="color:rgba(255,255,255,.7);">NHS 111</strong>.
        </p>
      </div>

    </div>

    <hr class="pco-footer__sep">

    <div class="grid-x align-middle">
      <div class="cell medium-6">
        <p class="pco-footer__copy">&copy; <?= date('Y') ?> Prescribe &amp; Co. Ltd. All rights reserved.</p>
      </div>
      <div class="cell medium-6 text-right">
        <a href="<?= APP_URL ?>/pages/privacy.php" style="font-size:.78rem;">Privacy Policy</a>&nbsp;&nbsp;
        <a href="<?= APP_URL ?>/pages/terms.php" style="font-size:.78rem;">Terms of Service</a>&nbsp;&nbsp;
        <a href="<?= APP_URL ?>/pages/contact.php" style="font-size:.78rem;">Contact Us</a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.8.1/js/foundation.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<?= $extra_scripts ?? '' ?>

<script>
$(document).foundation();

// User dropdown
const btn = document.getElementById('userMenuBtn');
const menu= document.getElementById('userDropdown');
if (btn && menu) {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', menu.classList.contains('open'));
  });
  document.addEventListener('click', () => menu.classList.remove('open'));
}

// Mobile nav
document.getElementById('mobileToggle')?.addEventListener('click', () => {
  document.getElementById('mobileNav')?.classList.toggle('open');
});

// Auto-dismiss flash messages
setTimeout(() => {
  document.querySelectorAll('.flash-message').forEach(el => {
    el.style.transition = 'opacity .4s'; el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  });
}, 4500);
</script>
</body>
</html>
