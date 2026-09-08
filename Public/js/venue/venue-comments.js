/**
 * venue-comments.js — Reserva (opinión) de un local (App/View/Venue/Detail.php)
 *
 * - Publica/actualiza la reserva con AJAX: solo se refresca el contenedor de
 *   comentarios y el promedio, no la página completa.
 * - Cada cliente solo puede tener UNA reserva por local. Si ya existe, el
 *   formulario llega prellenado con la misma (modo edición); al guardar, se
 *   conserva el mismo texto para que el cliente pueda editarlo después.
 * - "Cancelar edición" restaura la reserva guardada.
 * - "Editar" dentro de la lista carga la reserva elegida en el formulario.
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
  var commentIdInput = form.querySelector('input[name="commentId"]');

  // Snapshot de la reserva guardada (para "Cancelar edición").
  var savedCommentId = '';
  var savedStars = 0;
  var savedText = '';

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

  function labelFor(hasRating) {
    return hasRating ? 'Actualizar reserva' : 'Publicar reserva';
  }

  function captureSaved() {
    savedCommentId = commentIdInput.value || '';
    savedStars = currentStars();
    savedText = textarea ? textarea.value : '';
  }

  function initSaved() {
    savedCommentId = commentIdInput.value || '';
    // Al cargar, stars.js aún no ha pintado el widget; usamos data-value como respaldo.
    var widgetValue = widget ? (parseInt(widget.getAttribute('data-value') || '0', 10) || 0) : 0;
    savedStars = currentStars() || widgetValue;
    savedText = textarea ? textarea.value : '';
  }

  function restoreSaved() {
    commentIdInput.value = savedCommentId;
    setStars(savedStars);
    if (textarea) textarea.value = savedText;
    if (submitBtn) submitBtn.textContent = labelFor(savedCommentId !== '');
    if (cancelBtn) cancelBtn.style.display = 'none';
  }

  function enterEditMode(commentId, starValue, text) {
    commentIdInput.value = String(commentId);
    setStars(starValue);
    if (textarea) textarea.value = text || '';
    if (submitBtn) submitBtn.textContent = labelFor(true);
    if (cancelBtn) cancelBtn.style.display = '';
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
    return App.ajax(url, { method: 'GET' }).then(function (r) {
      var data = r.data;
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

  if (cancelBtn) {
    cancelBtn.addEventListener('click', restoreSaved);
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

    App.ajax(url, { body: new FormData(form) })
      .then(function (r) {
        var payload = r.data;
        if (!payload || payload.ok !== true) {
          throw new Error((payload && payload.message) || 'No se pudo guardar la reserva.');
        }

        // Si fue una creación, ahora el cliente YA tiene una reserva para el
        // local: se queda con el mismo texto para que pueda editarlo.
        if (!editing && payload.commentId) {
          commentIdInput.value = String(payload.commentId);
          if (submitBtn) submitBtn.textContent = labelFor(true);
        }

        renderAverage(payload.avg);
        return refreshComments().then(function () {
          App.toast(payload.message || 'Reserva guardada.', 'success');
        });
      })
      .then(function () {
        captureSaved();
      })
      .catch(function (err) {
        App.toast(err.message || 'Ocurrió un error al guardar la reserva.', 'error');
      })
      .then(function () {
        submitBtn.disabled = false;
        if (submitBtn.textContent === 'Guardando...' || submitBtn.textContent === 'Publicando...') {
          submitBtn.textContent = original;
        }
      });
  });

  // Inicialización: snapshot de la reserva guardada (si viene prellenada).
  initSaved();
})(window, document);