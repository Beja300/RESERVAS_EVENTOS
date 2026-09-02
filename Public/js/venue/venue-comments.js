/**
 * venue-comments.js — Comentarios de un local (App/View/Venue/Detail.php)
 *
 * - Publica comentarios con AJAX: solo se refresca el contenedor de
 *   comentarios, no la página completa.
 * - Cada usuario puede publicar varios comentarios: tras guardar, el
 *   formulario queda listo y el botón "Nuevo comentario" vuelve a dejarlo
 *   en modo de alta.
 * - "Editar" carga SOLO el comentario elegido (por su id) en el formulario
 *   y lo actualiza por AJAX.
 */
(function (window, document) {
  'use strict';

  var form = document.getElementById('commentForm');
  if (!form || !window.App) return;

  var box = document.getElementById('commentCard');
  var list = document.getElementById('venueCommentsList');
  var widget = document.getElementById('venueStarWidget');
  var textarea = document.getElementById('comment');
  var submitBtn = document.getElementById('submitComment');
  var cancelBtn = document.getElementById('cancelEdit');
  var newBtn = document.getElementById('newComment');
  var commentIdInput = form.querySelector('input[name="commentId"]');

  function currentStars() {
    if (!widget) return 0;
    var input = widget.querySelector('input[type="hidden"]');
    return input ? parseInt(input.value || '0', 10) || 0 : 0;
  }

  function setStars(value) {
    if (!widget) return;
    var input = widget.querySelector('input[type="hidden"]');
    if (input) input.value = value > 0 ? String(value) : '';
    widget.querySelectorAll('.star').forEach(function (starEl) {
      var idx = parseInt(starEl.getAttribute('data-star'), 10);
      starEl.classList.toggle('is-active', idx <= value);
    });
  }

  function resetForm() {
    commentIdInput.value = '';
    setStars(0);
    if (textarea) textarea.value = '';
    if (submitBtn) submitBtn.textContent = 'Publicar comentario';
    if (cancelBtn) cancelBtn.style.display = 'none';
    if (newBtn) newBtn.style.display = 'none';
  }

  function enterEditMode(commentId, starValue, text) {
    commentIdInput.value = String(commentId);
    setStars(starValue);
    if (textarea) textarea.value = text || '';
    if (submitBtn) submitBtn.textContent = 'Actualizar comentario';
    if (cancelBtn) cancelBtn.style.display = '';
    if (newBtn) newBtn.style.display = '';
    if (box) {
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (textarea) textarea.focus();
    }
  }

  function renderAverage(avg) {
    var el = document.getElementById('venueAvgRating');
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

  function refreshComments() {
    var url = form.getAttribute('data-refresh-url');
    if (!url || !list) return Promise.resolve();
    return fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && typeof data.html === 'string') {
          list.innerHTML = data.html;
          var empty = list.querySelector('.muted');
          if (empty) {
            list.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        }
      });
  }

  if (list) {
    list.addEventListener('click', function (e) {
      var btn = e.target.closest('.btn-edit-comment');
      if (!btn) return;
      enterEditMode(
        parseInt(btn.getAttribute('data-comment-id') || '0', 10) || 0,
        parseInt(btn.getAttribute('data-stars') || '0', 10) || 0,
        btn.getAttribute('data-text') || ''
      );
    });
  }

  if (newBtn) {
    newBtn.addEventListener('click', resetForm);
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', resetForm);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var editing = commentIdInput.value !== '';
    var stars = currentStars();

    if (!stars) {
      App.toast('Selecciona una calificación de 1 a 5 estrellas.', 'error');
      return;
    }

    var url = editing
      ? form.getAttribute('data-update-url')
      : form.getAttribute('action');

    var original = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = editing ? 'Guardando...' : 'Publicando...';

    fetch(url, {
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
          throw new Error(payload.message || 'No se pudo guardar el comentario.');
        }
        renderAverage(payload.avg);
        return refreshComments().then(function () {
          App.toast(payload.message || 'Comentario guardado.', 'success');
        });
      })
      .then(resetForm)
      .catch(function (err) {
        App.toast(err.message || 'Ocurrió un error al guardar el comentario.', 'error');
      })
      .then(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = original;
      });
  });
})(window, document);