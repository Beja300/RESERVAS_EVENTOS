(function () {
    function base() { var p=(window.location.pathname||'').split('/'); p.pop(); return p.join('/'); }

    // Cambio de contraseña (mostrar/ocultar)
    var pwCheck = document.getElementById('changePasswordCheck');
    var pwFields = document.getElementById('passwordFields');
    if (pwCheck && pwFields) {
      pwCheck.addEventListener('change', function () {
        pwFields.style.display = pwCheck.checked ? '' : 'none';
        if (!pwCheck.checked) {
          document.getElementById('currentPassword').value = '';
          document.getElementById('newPassword').value = '';
        }
      });
    }
    [['currentPassword', 'currentPasswordToggle'], ['newPassword', 'newPasswordToggle']].forEach(function (pair) {
      var input = document.getElementById(pair[0]);
      var toggle = document.getElementById(pair[1]);
      if (input && toggle) {
        toggle.addEventListener('click', function () {
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          toggle.textContent = show ? 'Ocultar' : 'Mostrar';
          toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
          input.focus();
        });
      }
    });

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
    var pForm = document.querySelector('form[data-ajax-owner-profile]');
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

    // Eliminar foto de perfil (AJAX).
    var phForm = document.querySelector('form[data-ajax-owner-photo]');
    if (phForm) {
      phForm.addEventListener('submit', function (e) {
        e.preventDefault();
        window.App && App.confirmModal('¿Eliminar tu foto de perfil?', 'Eliminar foto')
          .then(function (ok) {
            if (!ok) return;
            fetch(phForm.getAttribute('action'), {
              method: 'POST',
              headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
              body: new FormData(phForm)
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
              .then(function (r) {
                window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
                if (r.ok) setTimeout(function () { window.location.reload(); }, 700);
              });
          });
      });
    }

    // Desactivar cuenta (AJAX)
    var deact = document.querySelector('form[data-ajax-owner-deactivate]');
    if (deact) {
      deact.addEventListener('submit', function (e) {
        e.preventDefault();
        window.App && App.confirmModal(
          '¿Seguro que deseas desactivar tu perfil? No podrás acceder hasta que un administrador te reactive.',
          'Desactivar cuenta'
        ).then(function (ok) {
          if (!ok) return;
          fetch(deact.getAttribute('action'), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(deact)
          }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
            .then(function (r) {
              window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
              if (r.ok) setTimeout(function () { window.location.href = base() + '/index.php?controller=auth&action=showLogin'; }, 800);
            });
        });
      });
    }
  })();
