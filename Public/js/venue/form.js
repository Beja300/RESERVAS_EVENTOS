(function () {
  function base() { var p=(window.location.pathname||'').split('/'); p.pop(); return p.join('/'); }

  var VenueForm = (function () {
    var MAX = 6;

    function limit() {
      var c = document.getElementById('venueImages');
      return c && c.getAttribute('data-max') ? parseInt(c.getAttribute('data-max'), 10) : MAX;
    }

    function total() {
      var c = document.getElementById('venueImages');
      var input = document.getElementById('image');
      if (!c) return 0;
      var existing = c.querySelectorAll('.venue-thumb').length;
      var fresh = input && input.files ? input.files.length : 0;
      return existing + fresh;
    }

    function updateCount() {
      var el = document.getElementById('venueImageCount');
      if (!el) return;
      var t = total();
      var max = limit();
      el.textContent = t + ' de ' + max;
      el.classList.toggle('is-over', t > max);
    }

    function buildThumb(file) {
      var thumb = document.createElement('div');
      thumb.className = 'venue-thumb is-new';

      var img = document.createElement('img');
      img.alt = 'Nueva foto';
      var reader = new FileReader();
      reader.onload = (function (node) { return function (e) { node.src = e.target.result; }; })(img);
      reader.readAsDataURL(file);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'thumb-remove';
      btn.setAttribute('aria-label', 'Quitar foto');
      btn.textContent = '\u00d7';

      btn.addEventListener('click', function () { thumb.remove(); updateCount(); });

      thumb.appendChild(img);
      thumb.appendChild(btn);
      return thumb;
    }

    function onFilesChosen(input) {
      var c = document.getElementById('venueImages');
      if (!c || !input.files) return;

      c.querySelectorAll('.venue-thumb.is-new').forEach(function (n) { n.remove(); });

      var frag = document.createDocumentFragment();
      Array.prototype.forEach.call(input.files, function (file) { frag.appendChild(buildThumb(file)); });
      c.appendChild(frag);
      updateCount();
    }

    function onRemoveClick(btn) {
      var thumb = btn.closest('.venue-thumb');
      if (thumb) thumb.remove();
      updateCount();
    }

    function valid() {
      var t = total();
      if (t === 0) {
        window.App && App.toast('Debes conservar al menos una foto.', 'error');
        return false;
      }
      if (t > limit()) {
        window.App && App.toast('El local puede tener máximo 6 fotos.', 'error');
        return false;
      }
      return true;
    }

    function init() {
      var input = document.getElementById('image');
      if (input) input.addEventListener('change', function () { onFilesChosen(input); });

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.thumb-remove');
        if (btn) onRemoveClick(btn);
      });

      updateCount();
    }

    document.addEventListener('DOMContentLoaded', init);

    return { valid: valid, total: total };
  })();

  var form = document.querySelector('form[data-ajax-venue-form]');
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

      if (window.VenueForm && !VenueForm.valid()) return;

      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form)
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (r) {
          window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
          if (r.ok) setTimeout(function () { window.location.href = base() + '/index.php?controller=venue&action=list'; }, 700);
        })
        .catch(function (err) {
          window.App && App.toast(err && err.message ? err.message : 'No se pudo guardar el local.', 'error');
        });
    });
  }

  window.VenueForm = VenueForm;
})();