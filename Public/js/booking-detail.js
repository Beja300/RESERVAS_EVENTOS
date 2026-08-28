/**
 * booking-detail.js — Script específico de App/View/Booking/Detail.php
 *
 * - Sustituye el confirm() nativo de "Cancelar reserva" por un modal de
 *   confirmación (usa App.confirmModal del núcleo).
 */
(function (window, document) {
  'use strict';

  function init() {
    if (!window.App) return;

    var cancelForm = document.querySelector('form[data-booking-cancel]');
    if (!cancelForm) return;

    cancelForm.addEventListener('submit', function (e) {
      e.preventDefault();
      App.confirmModal(
        '¿Cancelar esta reserva? Esta acción no se puede deshacer.',
        'Cancelar reserva'
      ).then(function (ok) {
        if (ok) cancelForm.submit();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
