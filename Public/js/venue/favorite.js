/**
 * favorite.js — Botón de favorito de un local (App/View/Venue/Detail.php)
 *
 * - Marca/desmarca el local como favorito mediante venue/favorite (toggle).
 * - Actualiza el corazón (&#9829; / &#9825;) y la clase "is-favorite" sin recargar.
 * - La accion queda registrada en tbuserhistory como FAVORITE.
 */
(function (window, document) {
  'use strict';

  var btn = document.getElementById('favoriteBtn');
  if (!btn || !window.App) return;

  var venueId = parseInt(btn.getAttribute('data-venue-id') || '0', 10) || 0;
  var url = btn.getAttribute('data-url');
  var csrf = btn.getAttribute('data-csrf');
  var iconEl = btn.querySelector('.fav-icon');
  var labelEl = btn.querySelector('.fav-label');

  function setFavorite(state) {
    btn.classList.toggle('is-favorite', state);
    btn.setAttribute('data-favorite', state ? '1' : '0');
    if (iconEl) iconEl.innerHTML = state ? '&#9829;' : '&#9825;';
    if (labelEl) labelEl.textContent = state ? 'Favorito' : 'Marcar favorito';
  }

  btn.addEventListener('click', function () {
    if (btn.disabled) return;

    var fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('venueId', String(venueId));

    btn.disabled = true;

    fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: fd
    })
      .then(function (res) {
        return res.json().catch(function () {
          return { ok: false, message: 'El servidor no devolvió una respuesta válida.' };
        });
      })
      .then(function (payload) {
        if (!payload.ok) {
          throw new Error(payload.message || 'No se pudo actualizar el favorito.');
        }
        setFavorite(!!payload.favorite);
        App.toast(
          payload.favorite ? 'Local agregado a favoritos.' : 'Local eliminado de favoritos.',
          'success'
        );
      })
      .catch(function (err) {
        App.toast(err.message || 'Ocurrió un error al actualizar el favorito.', 'error');
      })
      .then(function () {
        btn.disabled = false;
      });
  });
})(window, document);