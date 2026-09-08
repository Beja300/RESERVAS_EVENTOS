(function () {
    // Detección de ubicación aproximada al iniciar sesión.
    // Solo actúa si el cliente aún no tiene ubicación configurada.
    var module = document.getElementById('geo-module');
    if (!module) return;

    if (module.getAttribute('data-has-location') === '1') return;

    var geoUrl = module.getAttribute('data-geo-url');
    var saveUrl = module.getAttribute('data-save-url');
    var csrf = module.getAttribute('data-csrf');
    if (!geoUrl || !saveUrl || !csrf) return;

    fetch(geoUrl, {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json || !json.ok) {
          window.App && App.toast(
            (json && json.message) || 'No pudimos detectar tu ubicación; configúrala en Mi perfil.',
            'info'
          );
          return;
        }

        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('province', json.province);
        fd.append('canton', json.canton);
        fd.append('district', json.district);

        return fetch(saveUrl, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: fd
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
          .then(function (r) {
            if (r.data && r.data.ok && r.data.saved) {
              window.App && App.toast(r.data.message, 'success');
              setTimeout(function () { window.location.reload(); }, 900);
            } else {
              window.App && App.toast(
                (r.data && r.data.message) || 'Ya tienes una ubicación configurada.',
                'info'
              );
            }
          });
      })
      .catch(function () {
        window.App && App.toast('No pudimos detectar tu ubicación; configúrala en Mi perfil.', 'info');
      });
  })();