/**
 * service-review.js — Reseñas de los servicios del local (App/View/Venue/Detail.php)
 *
 * - Reemplaza el POST clásico de cada formulario .service-rate-form por un
 *   envío AJAX (fetch): no se recarga la página.
 * - Al guardar actualiza la calificación promedio de ese servicio y la lista
 *   de reseñas vía api/serviceComments, mostrando un aviso de éxito.
 * - El cliente ya tiene UNA reseña por servicio (upsert); tras publicar, el
 *   botón pasa a "Actualizar calificación".
 */
(function (window, document) {
  'use strict';

  if (!window.App) return;

  var forms = document.querySelectorAll('.service-rate-form');
  if (!forms.length) return;

  function currentStars(form) {
    var input = form.querySelector('.star-widget input[type="hidden"]');
    return input ? parseInt(input.value || '0', 10) || 0 : 0;
  }

  function renderAverageInto(el, avg) {
    if (!el) return;
    if (typeof avg === 'number' && avg > 0) {
      var filled = Math.min(Math.max(Math.round(avg), 1), 5);
      el.innerHTML = '<span class="rating-stars">'
        + String.fromCharCode(9733).repeat(filled)
        + String.fromCharCode(9734).repeat(5 - filled)
        + '</span><span class="muted">'
        + App.escape(avg.toFixed(1)) + ' / 5</span>';
    } else {
      el.textContent = 'Sin calificaciones';
    }
  }

  function refreshComments(form) {
    var url = form.getAttribute('data-comments-url');
    var cell = document.getElementById(form.getAttribute('data-comments-id') || '');
    if (!url || !cell) return Promise.resolve();
    return fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && typeof data.html === 'string') {
          cell.innerHTML = data.html;
        }
      });
  }

  forms.forEach(function (form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    var okEl = form.querySelector('.service-rate-ok');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var stars = currentStars(form);
      if (!stars) {
        App.toast('Selecciona una calificación de 1 a 5 estrellas.', 'error');
        return;
      }

      if (okEl) { okEl.hidden = true; }

      var original = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Guardando...';

      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(form)
      })
        .then(function (res) {
          return res.json().catch(function () {
            return { ok: false, message: 'El servidor no devolvió una respuesta válida.' };
          });
        })
        .then(function (payload) {
          if (!payload.ok) {
            throw new Error(payload.message || 'No se pudo guardar la reseña.');
          }

          var avgCell = document.getElementById(form.getAttribute('data-avg-id') || '');
          renderAverageInto(avgCell, payload.avg);

          return refreshComments(form).then(function () {
            if (okEl) { okEl.hidden = false; }
            if (submitBtn) { submitBtn.textContent = 'Actualizar calificación'; }
            App.toast(payload.message || 'Reseña publicada.', 'success');
          });
        })
        .catch(function (err) {
          App.toast(err.message || 'Ocurrió un error al guardar la reseña.', 'error');
        })
        .then(function () {
          submitBtn.disabled = false;
          if (submitBtn.textContent === 'Guardando...') {
            submitBtn.textContent = original;
          }
        });
    });
  });
})(window, document);