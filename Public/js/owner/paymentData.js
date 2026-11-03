
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

    function applyMethodUi() {
      if (!form) return;
      var select = form.querySelector('[name="paymentMethodId"]');
      var account = form.querySelector('#account');
      var label = form.querySelector('label[for="account"]');
      var hint = form.querySelector('[data-account-hint]');
      if (!select || !account) return;

      var name = (select.options[select.selectedIndex] && select.options[select.selectedIndex].text || '').toLowerCase();
      var isCash = name.indexOf('efectivo') !== -1;
      var isSinpe = name.indexOf('sinpe') !== -1;
      var isTransfer = name.indexOf('transfer') !== -1;

      if (isCash) {
        account.removeAttribute('required');
        account.setAttribute('data-validate', '');
        account.placeholder = 'Opcional (ej. caja del local)';
        if (label) label.textContent = 'Referencia de cobro';
        if (hint) hint.textContent = 'En efectivo puedes dejar instrucciones para pagar el día del evento.';
      } else if (isSinpe) {
        account.setAttribute('required', 'required');
        account.setAttribute('data-validate', 'required phone');
        account.placeholder = 'Ej: 88887777';
        if (label) label.textContent = 'Teléfono SINPE Móvil *';
        if (hint) hint.textContent = 'Número de 8 dígitos al que el cliente enviará el SINPE.';
      } else if (isTransfer) {
        account.setAttribute('required', 'required');
        account.setAttribute('data-validate', 'required account');
        account.placeholder = 'Ej: CR12 1234 5678 9012 3456 7';
        if (label) label.textContent = 'IBAN / cuenta bancaria *';
        if (hint) hint.textContent = 'Cuenta IBAN donde el cliente hará la transferencia.';
      } else {
        account.setAttribute('required', 'required');
        account.setAttribute('data-validate', 'required account');
        account.placeholder = 'Ej: 88887777 o CR12 1234 5678 9012 3456 7';
        if (label) label.textContent = 'Cuenta bancaria / teléfono SINPE *';
        if (hint) hint.textContent = 'Teléfono SINPE de 8 dígitos o IBAN de la cuenta.';
      }
    }

    function resetForm() {
      if (!form) return;
      form.reset();
      var idField = form.querySelector('[data-payment-id]');
      if (idField) idField.value = '0';
      var active = form.querySelector('[name="active"]');
      if (active) active.checked = true;
      applyMethodUi();
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

        applyMethodUi();
        setOpen(true);
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    var methodSelect = form ? form.querySelector('[name="paymentMethodId"]') : null;
    if (methodSelect) {
      methodSelect.addEventListener('change', applyMethodUi);
      applyMethodUi();
    }
  }

  // Guardar y eliminar métodos de cobro.
  function initActions() {
    var save = document.querySelector('form[data-ajax-payment-save]');
    if (save) {
      save.addEventListener('submit', function (e) {
        e.preventDefault();

        var valid = true;
        save.querySelectorAll('[data-validate]').forEach(function (f) {
          if (!(f.getAttribute('data-validate') || '').trim()) return;
          if (window.App && App.validateField && !App.validateField(f)) valid = false;
        });
        if (!valid) {
          var first = save.querySelector('.is-invalid');
          if (first) first.focus();
          window.App && App.toast('Revisa los campos marcados.', 'error');
          return;
        }

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
