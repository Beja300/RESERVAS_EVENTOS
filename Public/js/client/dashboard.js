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

    App.ajax(geoUrl, { method: 'GET' }).then(function (r) {
        var json = r.data;
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

        return App.ajax(saveUrl, { body: fd }).then(function (r) {
          var data = r.data;
          if (data && data.ok && data.saved) {
            window.App && App.toast(data.message, 'success');
            setTimeout(function () { window.location.reload(); }, 900);
          } else {
            window.App && App.toast(
              (data && data.message) || 'Ya tienes una ubicación configurada.',
              'info'
            );
          }
        });
      });
  })();