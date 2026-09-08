(function () {
  // Guardar comisión e IVA (AJAX).
  var cForm = document.querySelector('form[data-ajax-commission-config]');
  if (cForm) {
    cForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var valid = true;
      var fields = cForm.querySelectorAll('[data-validate-number]');
      for (var i = 0; i < fields.length; i++) {
        var el = fields[i];
        var val = parseFloat(el.value);

        el.classList.toggle('is-invalid', isNaN(val) || val < 0 || val > 100);

        if (el.classList.contains('is-invalid')) valid = false;
      }

      if (!valid) {
        var firstInvalid = cForm.querySelector('.is-invalid');
        if (firstInvalid) firstInvalid.focus();
        window.App && App.toast('Los valores deben estar entre 0 y 100.', 'error');
        return;
      }

      fetch(cForm.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(cForm)
      }).then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, data: j }; });
      }).then(function (r) {
        window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
        if (r.ok) setTimeout(function () { window.location.reload(); }, 700);
      }).catch(function () {
        window.App && App.toast('Ocurrió un error al guardar. Inténtalo de nuevo.', 'error');
      });
    });
  }

  // Limpiar el marcado de error al editar.
  var inputs = cForm ? cForm.querySelectorAll('[data-validate-number]') : [];
  for (var j = 0; j < inputs.length; j++) {
    inputs[j].addEventListener('input', function () {
      this.classList.remove('is-invalid');
    });
  }
})();