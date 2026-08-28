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
    if (cancelForm) {
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

    var rejectForm = document.querySelector('form[data-confirm]');
    if (rejectForm) {
      rejectForm.addEventListener('submit', function (e) {
        e.preventDefault();
        App.confirmModal(
          '¿Rechazar el comprobante de esta reserva?',
          'Rechazar comprobante'
        ).then(function (ok) {
          if (ok) rejectForm.submit();
        });
      });
    }

    // Validación del comprobante (tipo y tamaño) antes de subirlo.
    var uploadForm = document.querySelector('input[name="ticket"]') &&
      document.querySelector('input[name="ticket"]').closest('form');
    if (uploadForm) {
      uploadForm.addEventListener('submit', function (e) {
        var input = uploadForm.querySelector('input[name="ticket"]');
        if (!input.files || !input.files.length) return;
        var file = input.files[0];
        var allowed = ['image/png', 'image/jpeg', 'application/pdf'];
        if (allowed.indexOf(file.type) === -1) {
          e.preventDefault();
          App.toast && App.toast('Solo se permiten archivos PNG, JPG o PDF.', 'error');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          e.preventDefault();
          App.toast && App.toast('El comprobante no puede superar los 2 MB.', 'error');
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
