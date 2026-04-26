'use strict';

// ── Global namespace ─────────────────────────────────────────────────
const PCO = {
  csrfToken() {
    return document.querySelector(`input[name="${document.body.dataset.csrf || '_pco_csrf'}"]`)?.value
        || document.querySelector('input[name*="csrf"]')?.value || '';
  },

  async post(url, data = {}) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ ...data, _pco_csrf: this.csrfToken() }),
    });
    return res.json();
  },

  notify(msg, type = 'info') {
    let c = document.getElementById('flash-global');
    if (!c) {
      c = document.createElement('div');
      c.id = 'flash-global';
      c.style.cssText = 'position:fixed;top:80px;right:16px;z-index:9000;min-width:280px;max-width:400px;';
      document.body.appendChild(c);
    }
    const icons = { success:'circle-check', error:'circle-xmark', warning:'triangle-exclamation', info:'circle-info' };
    const el = document.createElement('div');
    el.className = `pco-alert pco-alert--${type} flash-message`;
    el.style.cssText = 'box-shadow:0 4px 16px rgba(26,26,46,.15);';
    el.innerHTML = `<i class="fa-solid fa-${icons[type]||'circle-info'}"></i><span>${msg}</span>`;
    c.appendChild(el);
    setTimeout(() => { el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 4500);
  },

  confirm(msg, fn) { if (window.confirm(msg)) fn(); },
};

// ── Consultation Wizard ──────────────────────────────────────────────
class PCOWizard {
  constructor(formId) {
    this.form  = document.getElementById(formId);
    if (!this.form) return;
    this.steps = [...this.form.querySelectorAll('.pco-wizard-step')];
    this.cur   = 0;
    this._init();
  }

  _init() {
    this._show(0);

    this.form.addEventListener('click', e => {
      if (e.target.closest('[data-next]')) this._next();
      if (e.target.closest('[data-prev]')) this._prev();
    });

    // Visual selection for choice cards
    this.form.querySelectorAll('.pco-choice').forEach(opt => {
      const input = opt.querySelector('input');
      if (!input) return;
      const update = () => {
        if (input.type === 'radio') {
          opt.closest('.pco-choices')?.querySelectorAll('.pco-choice')
             .forEach(o => o.classList.remove('selected'));
        }
        opt.classList.toggle('selected', input.checked);
      };
      input.addEventListener('change', update);
    });
  }

  _validate() {
    const step = this.steps[this.cur];
    let ok = true;

    step.querySelectorAll('[required]').forEach(el => {
      el.classList.remove('is-error');
      const name = el.name;
      if (el.type === 'radio' || el.type === 'checkbox') {
        if (!step.querySelector(`input[name="${name}"]:checked`)) {
          ok = false;
          step.querySelectorAll(`input[name="${name}"]`).forEach(i => {
            i.closest('.pco-choice')?.classList.add('is-error');
          });
        }
      } else if (!el.value.trim()) {
        el.classList.add('is-error'); ok = false;
      }
    });

    if (!ok) PCO.notify('Please answer all required questions before continuing.', 'warning');
    return ok;
  }

  _show(i) {
    this.steps.forEach((s, idx) => s.classList.toggle('active', idx === i));
    this._updateProgress();
    this.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  _next() {
    if (!this._validate()) return;
    if (this.cur < this.steps.length - 1) { this.cur++; this._show(this.cur); }
  }

  _prev() {
    if (this.cur > 0) { this.cur--; this._show(this.cur); }
  }

  _updateProgress() {
    const pct = Math.round(((this.cur + 1) / this.steps.length) * 100);
    const bar = document.getElementById('pcoProgressFill');
    if (bar) bar.style.width = pct + '%';

    document.querySelectorAll('.pco-step').forEach((el, i) => {
      el.classList.remove('active','complete');
      if (i < this.cur) el.classList.add('complete');
      else if (i === this.cur) el.classList.add('active');
    });
  }

  goToStep(i) { this.cur = i; this._show(i); }
}

// ── Active sidebar link ──────────────────────────────────────────────
(function() {
  const path = window.location.pathname;
  document.querySelectorAll('.pco-sidebar__nav a').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href && path.endsWith(href.split('/').pop())) a.classList.add('active');
  });
})();

// ── Confirm-action links ─────────────────────────────────────────────
document.addEventListener('click', e => {
  const el = e.target.closest('[data-confirm]');
  if (!el) return;
  e.preventDefault();
  PCO.confirm(el.dataset.confirm, () => {
    if (el.href) window.location.href = el.href;
    else if (el.dataset.href) window.location.href = el.dataset.href;
    else el.closest('form')?.submit();
  });
});

// ── Future-only date fields ──────────────────────────────────────────
document.querySelectorAll('input[type="date"][data-future]').forEach(el => {
  const today = new Date().toISOString().split('T')[0];
  el.min = today;
  if (!el.value) el.value = today;
});

// ── Table search ─────────────────────────────────────────────────────
document.getElementById('tblSearch')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('[data-searchable] tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// ── Print labels ─────────────────────────────────────────────────────
window.printLabels = () => window.print();

// ── Expose globals ───────────────────────────────────────────────────
window.PCO       = PCO;
window.PCOWizard = PCOWizard;
