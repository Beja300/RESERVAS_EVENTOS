(function () {
  function base() { var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/'); }
  var form = document.querySelector('form[data-ajax-service-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var valid = true;
      form.querySelectorAll('[data-validate]').forEach(function (f) {
        if (window.App && App.validateField && !App.validateField(f)) valid = false;
      });
      if (!valid) {
        var firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) firstInvalid.focus();
        window.App && App.toast('Revisa los campos marcados.', 'error');
        return;
      }

      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form)
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (r) {
          window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
          if (r.ok) {
            var venueIdInput = form.querySelector('[name="venueId"]');
            var vid = venueIdInput ? venueIdInput.value : '';
            setTimeout(function () { window.location.href = base() + '/index.php?controller=service&action=list&venueId=' + vid; }, 700);
          }
        });
    });
  }
})();