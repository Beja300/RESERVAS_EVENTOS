(function () {
  function base() { var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/'); }

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

  function bindCurrencyInput(input) {
    if (!input || input.dataset.currencyBound === '1') return;
    input.dataset.currencyBound = '1';
    if (input.value.trim() !== '') formatCurrency(input);
    input.addEventListener('input', function () {
      var digitsBefore = countDigits(input.value, input.selectionStart);
      formatCurrency(input);
      caretByDigits(input, digitsBefore);
    });
    input.addEventListener('blur', function () { formatCurrency(input); });
  }

  Array.prototype.slice.call(document.querySelectorAll('[data-currency-mask]')).forEach(bindCurrencyInput);

  // ---------- Selección de locales (crear en varios) ----------
  var allVenues = document.getElementById('allVenues');
  var venueChecks = document.querySelectorAll('input[name="venueIds[]"]');
  if (allVenues) {
    allVenues.addEventListener('change', function () {
      venueChecks.forEach(function (box) {
        box.checked = allVenues.checked;
        box.disabled = allVenues.checked;
      });
    });
  }

  var form = document.querySelector('form[data-ajax-service-form]');
  if (!form) return;

  function hasGalleryFile() {
    var input = form.querySelector('input[name="gallery[]"]');
    return !!(input && input.files && input.files.length > 0);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    // Se requiere al menos una imagen: una ya guardada que no se elimine,
    // o un archivo nuevo que se suba en este envío.
    var galleryInput = form.querySelector('input[name="gallery[]"]');
    var existing = galleryInput ? parseInt(galleryInput.getAttribute('data-images-existing') || '0', 10) : 0;
    var removed = form.querySelectorAll('input[name="removeGallery[]"]:checked').length;
    if (existing - removed <= 0 && !hasGalleryFile()) {
      window.App && App.toast('Debes subir al menos una imagen del servicio.', 'error');
      if (galleryInput) galleryInput.focus();
      return;
    }

    // Normaliza el precio (quita ₡, comas y espacios) para validar y enviar.
    var maskedValues = Array.prototype.slice.call(form.querySelectorAll('[data-currency-mask]')).map(function (input) {
      var masked = input.value;
      input.value = stripCurrency(input.value);
      return { input: input, masked: masked };
    });

    function restoreMasks() {
      maskedValues.forEach(function (item) { item.input.value = item.masked; });
    }

    var valid = true;
    form.querySelectorAll('[data-validate]').forEach(function (field) {
      if (field.closest('[hidden]')) return;
      if (window.App && App.validateField && !App.validateField(field)) valid = false;
    });
    if (!valid) {
      restoreMasks();
      var firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
      window.App && App.toast('Revisa los campos marcados.', 'error');
      return;
    }

    var venueIdInput = form.querySelector('[name="venueId"]');
    var isEdit = !!form.querySelector('[name="idService"]');
    var target = (isEdit || venueIdInput)
      ? base() + '/index.php?controller=service&action=list&venueId=' + (venueIdInput ? venueIdInput.value : '')
      : base() + '/index.php?controller=service&action=groups';

    var data = new FormData(form);
    restoreMasks();

    fetch(form.getAttribute('action'), {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: data
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
      .then(function (r) {
        window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
        if (r.ok) {
          setTimeout(function () { window.location.href = target; }, 700);
        }
      });
  });
})();
