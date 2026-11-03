/**
 * stars.js — Script específico de App/View/Venue/Detail.php
 *
 * - Convierte cada .star-widget en una entrada de 5 estrellas clicables.
 * - Preselecciona la calificación previa del usuario (data-value).
 * - Escribe el valor final en un input[type=hidden] que envía el formulario.
 */
(function (window, document) {
  'use strict';

  function init() {
    document.querySelectorAll('.star-widget').forEach(function (widget) {
      var input = widget.querySelector('input[type="hidden"]');
      var stars = widget.querySelectorAll('.star');
      if (!input || stars.length === 0) {
        return;
      }

      var value = parseInt(widget.getAttribute('data-value') || '0', 10) || 0;

      function paint(upTo) {
        stars.forEach(function (starEl) {
          var idx = parseInt(starEl.getAttribute('data-star'), 10);
          starEl.classList.toggle('is-active', idx <= upTo);
        });
      }

      function clearHover() {
        stars.forEach(function (starEl) {
          starEl.classList.remove('is-hover');
        });
      }

      stars.forEach(function (starEl) {
        starEl.addEventListener('click', function () {
          value = parseInt(starEl.getAttribute('data-star'), 10);
          input.value = String(value);
          paint(value);
        });

        starEl.addEventListener('mouseenter', function () {
          var idx = parseInt(starEl.getAttribute('data-star'), 10);
          stars.forEach(function (s) {
            var sIdx = parseInt(s.getAttribute('data-star'), 10);
            s.classList.toggle('is-hover', sIdx <= idx);
          });
        });

        starEl.addEventListener('mouseleave', clearHover);
      });

      if (value > 0) {
        input.value = String(value);
        paint(value);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);