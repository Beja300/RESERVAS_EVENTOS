(function () {
  function base() { var p=(window.location.pathname||'').split('/'); p.pop(); return p.join('/'); }

  // ---------- Máscara de moneda (₡ colones) ----------
  function onlyDigitsAndDot(value) {
    return (value || '').replace(/[^\d.]/g, '');
  }

  function stripCurrency(value) {
    return onlyDigitsAndDot(value);
  }

  function formatCurrency(input) {
    var raw = onlyDigitsAndDot(input.value);
    if (raw === '') { input.value = ''; return; }

    var parts = raw.split('.');
    var intPart = parts.shift().replace(/^0+(?=\d)/, '');
    var dec = parts.length ? '.' + parts.join('').slice(0, 2) : '';
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    input.value = '₡ ' + intPart + dec;
  }

  function countDigits(value, end) {
    var n = 0;
    for (var i = 0; i < end && i < value.length; i++) {
      if (/[\d.]/.test(value[i])) n++;
    }
    return n;
  }

  function caretByDigits(input, digitIndex) {
    var v = input.value;
    var n = 0;
    for (var i = 0; i < v.length; i++) {
      if (n === digitIndex) { input.setSelectionRange(i, i); return; }
      if (/[\d.]/.test(v[i])) n++;
    }
    input.setSelectionRange(v.length, v.length);
  }

  var currencyInputs = Array.prototype.slice.call(document.querySelectorAll('[data-currency-mask]'));
  currencyInputs.forEach(function (input) {
    if (input.value.trim() !== '') formatCurrency(input);
    input.addEventListener('input', function () {
      var digitsBefore = countDigits(input.value, input.selectionStart);
      formatCurrency(input);
      caretByDigits(input, digitsBefore);
    });
    input.addEventListener('blur', function () { formatCurrency(input); });
  });

  var form = document.querySelector('form[data-ajax-venue-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      // Normaliza el precio (quita ₡, comas y espacios) para validar y enviar.
      var maskedValues = currencyInputs.map(function (input) {
        var masked = input.value;
        input.value = stripCurrency(input.value);
        return { input: input, masked: masked };
      });

      function restoreMasks() {
        maskedValues.forEach(function (item) { item.input.value = item.masked; });
      }

      var valid = true;
      form.querySelectorAll('[data-validate]').forEach(function (f) {
        if (window.App && App.validateField && !App.validateField(f)) valid = false;
      });
      if (!valid) {
        restoreMasks();
        var firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) firstInvalid.focus();
        window.App && App.toast('Revisa los campos marcados.', 'error');
        return;
      }

      // La FormData captura los valores ya normalizados.
      var data = new FormData(form);
      restoreMasks();

      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: data
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (r) {
          window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
          if (r.ok) setTimeout(function () { window.location.href = base() + '/index.php?controller=venue&action=list'; }, 700);
        })
        .catch(function (err) {
          window.App && App.toast(err && err.message ? err.message : 'No se pudo crear el local.', 'error');
        });
    });
  }
})();
