(function () {
  function base() { var p=(window.location.pathname||'').split('/'); p.pop(); return p.join('/'); }

  // Vista previa de la foto antes de subirla.
  var photoInput = document.querySelector('[data-photo-input]');
  if (photoInput) {
    var prevImg = document.getElementById(photoInput.getAttribute('data-photo-input'));
    photoInput.addEventListener('change', function () {
      if (photoInput.files && photoInput.files[0] && prevImg) {
        prevImg.src = URL.createObjectURL(photoInput.files[0]);
        prevImg.style.display = 'inline-block';
      }
    });
  }

  // Actualizar perfil (AJAX).
  var pForm = document.querySelector('form[data-ajax-client-profile]');
  if (pForm) {
    pForm.addEventListener('submit', function (e) {
      var fields = pForm.querySelectorAll('[data-validate]');
      for (var i = 0; i < fields.length; i++) {
        if (fields[i].classList.contains('is-invalid')) { return; }
      }
      e.preventDefault();
      fetch(pForm.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(pForm)
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (r) {
          window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
          if (r.ok) setTimeout(function () { window.location.reload(); }, 700);
        });
    });
  }

  // Desactivar cuenta (AJAX), sin condiciones (el cliente siempre puede).
  var deact = document.querySelector('form[data-ajax-client-deactivate]');
  if (deact) {
    deact.addEventListener('submit', function (e) {
      e.preventDefault();
      window.App && App.confirmModal(
        '¿Seguro que deseas desactivar tu cuenta? No podrás acceder hasta que un administrador la reactive.',
        'Desactivar cuenta'
      ).then(function (ok) {
        if (!ok) return;
        fetch(deact.getAttribute('action'), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: new FormData(deact)
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
          .then(function (r) {
            window.App && App.toast(r.data.message || 'Cuenta desactivada.', r.ok ? 'success' : 'error');
            if (r.ok) setTimeout(function () { window.location.href = base() + '/index.php?controller=auth&action=showLogin'; }, 800);
          });
      });
    });
  }
})();