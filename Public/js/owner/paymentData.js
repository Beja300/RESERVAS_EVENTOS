(function () {
  function post(url, data, cb) {
    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: data })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
      .then(function (r) { cb(r.ok, r.data); }).catch(function () { cb(false, { message: 'Error de red.' }); });
  }
  function init() {
    var wrap = document.querySelector('[data-payment-form-wrap]');
    var toggle = document.querySelector('[data-toggle-payment-form]');
    var form = document.querySelector('[data-payment-form]');
    if (!wrap || !toggle || !form) return;
    var method = form.querySelector('[name="paymentMethodId"]');
    var holder = form.querySelector('#holder');
    var account = form.querySelector('#account');
    var instructions = form.querySelector('#instructions');
    var active = form.querySelector('[name="active"]');
    function type() {
      var name = method.options[method.selectedIndex].text.toLowerCase();
      if (name.indexOf('sinpe') >= 0) return 'sinpe';
      if (name.indexOf('transfer') >= 0 || name.indexOf('iban') >= 0) return 'iban';
      if (/efectivo|lugar|local|cash|tarjeta/.test(name)) return 'onsite';
      return 'other';
    }
    function format(value, kind) {
      var raw = String(value || '').toUpperCase();
      if (kind === 'sinpe') {
        var digits = raw.replace(/\D/g, '').slice(-8);
        return digits;
      }
      if (kind === 'iban') {
        raw = raw.replace(/[^A-Z0-9]/g, '');
        if (raw === 'C') return raw;
        if (/^\d/.test(raw)) raw = 'CR' + raw;
        else if (raw.slice(0, 2) !== 'CR') raw = 'CR' + raw.replace(/^[A-Z]{1,2}/, '');
        return raw.slice(0, 22).replace(/(.{4})/g, '$1 ').trim();
      }
      return value;
    }
    function configure(clearOnOnsite) {
      var kind = type();
      var onsite = kind === 'onsite';
      var hg = form.querySelector('[data-payment-holder-group]');
      var ag = form.querySelector('[data-payment-account-group]');
      var ig = form.querySelector('[data-payment-instructions-group]');
      var sinpePrefix = form.querySelector('[data-sinpe-prefix]');
      hg.hidden = ag.hidden = ig.hidden = onsite;
      if (sinpePrefix) sinpePrefix.hidden = kind !== 'sinpe';
      if (sinpePrefix) sinpePrefix.style.display = kind === 'sinpe' ? 'flex' : 'none';
      if (onsite) {
        if (clearOnOnsite) { holder.value = ''; account.value = ''; }
        holder.required = account.required = false;
        instructions.value = /tarjeta/i.test(method.options[method.selectedIndex].text) ? 'Pago con tarjeta en el lugar.' : 'Pago en el lugar.';
      } else {
        if (instructions.value === 'Pago en el lugar.' || instructions.value === 'Pago con tarjeta en el lugar.') instructions.value = '';
        holder.required = account.required = true;
        ag.querySelector('label').textContent = kind === 'sinpe' ? 'Número SINPE Móvil *' : (kind === 'iban' ? 'IBAN de Costa Rica *' : 'Cuenta bancaria / teléfono *');
        account.placeholder = kind === 'sinpe' ? '88888888' : (kind === 'iban' ? 'CR05 0152 0200 1026 2840 66' : 'Número de cuenta o teléfono');
        account.inputMode = kind === 'sinpe' ? 'numeric' : 'text';
        if (kind === 'sinpe') account.value = account.value.replace(/\D/g, '').slice(-8);
        else if (kind === 'iban') account.value = format(account.value, kind);
      }
      account.dataset.paymentType = kind;
    }
    function setOpen(open) { wrap.hidden = !open; toggle.textContent = open ? 'Cerrar formulario' : '+ Agregar método de pago nuevo'; }
    function resetForm() { form.reset(); form.querySelector('[data-payment-id]').value = '0'; active.value = '1'; configure(true); }
    method.addEventListener('change', function () { configure(true); });
    account.addEventListener('focus', function () {
      if (account.dataset.paymentType === 'sinpe') account.value = account.value.replace(/\D/g, '');
    });
    account.addEventListener('input', function () {
      if (account.dataset.paymentType === 'sinpe') account.value = account.value.replace(/\D/g, '').slice(0, 8);
      else if (account.dataset.paymentType === 'iban') account.value = format(account.value, 'iban');
    });
    account.addEventListener('blur', function () {
      if (account.dataset.paymentType === 'sinpe') {
        var digits = account.value.replace(/\D/g, '').slice(0, 8);
        account.value = digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
      }
    });
    toggle.addEventListener('click', function () { var open = wrap.hidden; setOpen(open); if (open) resetForm(); });
    document.querySelectorAll('[data-edit-payment]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        method.value = btn.dataset.methodId;
        holder.value = btn.dataset.holder || '';
        account.value = btn.dataset.account || '';
        instructions.value = btn.dataset.instructions || '';
        active.value = btn.dataset.active === '1' ? '1' : '0';
        form.querySelector('[data-payment-id]').value = btn.dataset.id;
        configure(false); setOpen(true); form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
    configure(false);

    var save = document.querySelector('form[data-ajax-payment-save]');
    save.addEventListener('submit', function (e) {
      e.preventDefault(); configure(false);
      var kind = account.dataset.paymentType;
      if (kind === 'onsite') { holder.value = ''; account.value = ''; instructions.value = /tarjeta/i.test(method.options[method.selectedIndex].text) ? 'Pago con tarjeta en el lugar.' : 'Pago en el lugar.'; }
      if (kind === 'sinpe') {
        account.value = account.value.replace(/\D/g, '');
        if (!/^\d{8}$/.test(account.value)) { window.App.toast('El número SINPE Móvil debe tener 8 dígitos.', 'error'); account.focus(); return; }
      }
      if (kind === 'iban') account.value = account.value.replace(/\s+/g, '').toUpperCase();
      if (kind !== 'onsite' && (!holder.value.trim() || !account.value.trim())) { window.App.toast('Completa el titular y la cuenta solicitados.', 'error'); return; }
      post(save.action, new FormData(save), function (ok, d) { window.App && App.toast(d.message, ok ? 'success' : 'error'); if (ok) setTimeout(function () { location.reload(); }, 600); });
    });
    document.querySelectorAll('form[data-ajax-payment-remove]').forEach(function (f) {
      f.addEventListener('submit', function (e) { e.preventDefault(); App.confirmModal('¿Eliminar este método de cobro?', 'Eliminar método').then(function (ok) { if (ok) post(f.action, new FormData(f), function (success, d) { App.toast(d.message, success ? 'success' : 'error'); if (success) setTimeout(function () { location.reload(); }, 600); }); }); });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
