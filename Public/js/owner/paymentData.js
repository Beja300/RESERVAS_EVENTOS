
(function () {
  function base() {
    var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/');
  }
  function post(url, data, cb) {
    fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: data
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
      .then(function (r) { cb(r.ok, r.data); })
      .catch(function () { cb(false, { message: 'Error de red.' }); });
  }

  // Plegar/desplegar el formulario y editar métodos existentes.
  function initForm() {
    var wrap = document.querySelector('[data-payment-form-wrap]');
    var toggle = document.querySelector('[data-toggle-payment-form]');
    var form = document.querySelector('[data-payment-form]');

    if (!wrap || !toggle) return;

    var openLabel = '+ Agregar método de pago nuevo';
    var closeLabel = 'Cerrar formulario';

    function setOpen(open) {
      wrap.hidden = !open;
      toggle.textContent = open ? closeLabel : openLabel;
      if (open && form) {
        var first = form.querySelector('select, input[type="text"], textarea');
        if (first) first.focus();
      }
    }

    function resetForm() {
      if (!form) return;
      form.reset();
      var idField = form.querySelector('[data-payment-id]');
      if (idField) idField.value = '0';
      var active = form.querySelector('[name="active"]');
      if (active) active.checked = true;
    }

    toggle.addEventListener('click', function () {
      setOpen(wrap.hidden);
      if (!wrap.hidden) resetForm();
    });

    document.querySelectorAll('[data-edit-payment]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!form) return;

        var select = form.querySelector('[name="paymentMethodId"]');
        var methodId = btn.getAttribute('data-method-id');
        if (select && select.querySelector('option[value="' + methodId + '"]')) {
          select.value = methodId;
        }

        var holder = form.querySelector('#holder');
        if (holder) holder.value = btn.getAttribute('data-holder') || '';
        var account = form.querySelector('#account');
        if (account) account.value = btn.getAttribute('data-account') || '';
        var instructions = form.querySelector('#instructions');
        if (instructions) instructions.value = btn.getAttribute('data-instructions') || '';

        var active = form.querySelector('[name="active"]');
        if (active) active.checked = btn.getAttribute('data-active') === '1';

        var idField = form.querySelector('[data-payment-id]');
        if (idField) idField.value = btn.getAttribute('data-id');

        setOpen(true);
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  // Guardar y eliminar métodos de cobro.
  function initActions() {
    var save = document.querySelector('form[data-ajax-payment-save]');
    if (save) {
      save.addEventListener('submit', function (e) {
        e.preventDefault();
        post(save.getAttribute('action'), new FormData(save), function (ok, d) {
          window.App && App.toast(d.message, ok ? 'success' : 'error');
          if (ok) setTimeout(function () { window.location.reload(); }, 600);
        });
      });
    }
    document.querySelectorAll('form[data-ajax-payment-remove]').forEach(function (f) {
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        window.App && App.confirmModal('¿Eliminar este método de cobro?', 'Eliminar método').then(function (ok) {
          if (!ok) return;
          post(f.getAttribute('action'), new FormData(f), function (ok, d) {
            window.App && App.toast(d.message, ok ? 'success' : 'error');
            if (ok) setTimeout(function () { window.location.reload(); }, 600);
          });
        });
      });
    });
  }

  function attach() {
    initForm();
    initActions();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attach);
  else attach();
})();
