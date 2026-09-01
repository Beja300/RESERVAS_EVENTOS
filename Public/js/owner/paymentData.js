
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
  function attach() {
    var save = document.querySelector('form[data-ajax-payment-save]');
    if (save) {
      save.addEventListener('submit', function (e) {
        e.preventDefault();
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
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attach);
  else attach();
})();
