/**
 * booking-detail.js — Script específico de App/View/Booking/Detail.php
 *
 * Interacciones AJAX (sin recarga de página):
 *  1. Agregar servicio a la reserva (cliente).
 *  2. Seleccionar método de pago -> mostrar datos de cobro del owner.
 *  3. Subir comprobante de pago (requiere haber seleccionado método).
 *  4. Cancelar reserva (solo pendiente).
 *  5. Aprobar / rechazar comprobante (owner).
 */
(function (window, document) {
  'use strict';

  function csrfToken() {
    var f = document.querySelector('input[name="csrf_token"]');
    return f ? f.value : '';
  }

  function postJson(url, formData, onOk, onError) {
    fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: formData
    }).then(function (res) {
      return res.json().then(function (data) { return { ok: res.ok, data: data }; });
    }).then(function (r) {
      if (r.ok) onOk(r.data);
      else onError(r.data);
    }).catch(function () {
      onError({ message: 'Ocurrió un error de red. Intenta de nuevo.' });
    });
  }

  function initPaymentFlow(flow) {
    var select = flow.querySelector('[data-pm-select]');
    var infoBox = flow.querySelector('[data-pm-info]');
    var form = flow.querySelector('[data-upload-ticket]');
    var hidden = flow.querySelector('[data-pm-hidden]');
    var uploadBtn = flow.querySelector('[data-upload-btn]');
    var ticketInput = flow.querySelector('[data-ticket-input]');
    var payments = [];
    try { payments = JSON.parse(flow.getAttribute('data-owner-payments') || '[]'); } catch (e) {}

    function currentPayment() {
      var id = parseInt(select.value, 10);
      for (var i = 0; i < payments.length; i++) {
        if (payments[i].idPaymentMethod === id) return payments[i];
      }
      return null;
    }

    function showInfo(op) {
      if (!op) {
        infoBox.style.display = 'none';
        infoBox.innerHTML = '';
        hidden.value = '';
        uploadBtn.disabled = true;
        return;
      }
      var parts = [];
      parts.push('<strong>Datos para pagar a ' + op.paymentMethod + ':</strong>');
      if (op.holder) parts.push('<div>Titular: <strong>' + App.escape(op.holder) + '</strong></div>');
      if (op.account) parts.push('<div>Cuenta / Teléfono: <strong>' + App.escape(op.account) + '</strong></div>');
      if (op.instructions) parts.push('<div>' + App.escape(op.instructions) + '</div>');
      infoBox.innerHTML = parts.join('');
      infoBox.style.display = 'block';
      hidden.value = String(op.idPaymentMethod);
      uploadBtn.disabled = false;
    }

    if (select) {
      select.addEventListener('change', function () { showInfo(currentPayment()); });
      showInfo(currentPayment());
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!parseInt(hidden.value, 10)) {
          App.toast('Selecciona primero un método de pago.', 'error');
          return;
        }

        var file = ticketInput && ticketInput.files && ticketInput.files[0];
        if (!file) {
          App.toast('Selecciona un archivo de comprobante.', 'error');
          return;
        }

        var allowed = ['image/png', 'image/jpeg', 'application/pdf'];
        if (allowed.indexOf(file.type) === -1) {
          App.toast('Solo se permiten archivos PNG, JPG o PDF.', 'error');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          App.toast('El comprobante no puede superar los 2 MB.', 'error');
          return;
        }

        var data = new FormData(form);

        postJson(form.getAttribute('action') || (window.location.pathname + '?controller=booking&action=uploadTicket'),
          data,
          function (r) {
            App.toast(r.message || 'Comprobante subido.', 'success');
            setTimeout(function () { window.location.reload(); }, 900);
          },
          function (r) { App.toast(r.message || 'No se pudo subir el comprobante.', 'error'); }
        );
      });
    }
  }

  function getBasePath() {
    var script = (window.location.pathname || '').split('/');
    script.pop();
    return script.join('/');
  }

  function init() {
    if (!window.App) return;

    // 1) Agregar servicio (AJAX)
    var addForm = document.querySelector('form[data-add-service]');
    if (addForm) {
      addForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(addForm);
        data.set('bookingId', addForm.getAttribute('data-venue-booking'));
        postJson(getBasePath() + '/index.php?controller=booking&action=addLine',
          data,
          function (r) { App.toast(r.message || 'Servicio agregado.', 'success'); setTimeout(function () { window.location.reload(); }, 600); },
          function (r) { App.toast(r.message || 'No se pudo agregar el servicio.', 'error'); }
        );
      });
    }

    // 2 & 3) Flujo de pago
    var flow = document.querySelector('[data-payment-flow]');
    if (flow) initPaymentFlow(flow);

    // 4) Cancelar reserva (solo pendiente) — confirmación + AJAX
    var cancelForm = document.querySelector('form[data-ajax-cancel]');
    if (cancelForm) {
      cancelForm.addEventListener('submit', function (e) {
        e.preventDefault();
        App.confirmModal(
          '¿Cancelar esta reserva? Esta acción no se puede deshacer.',
          'Cancelar reserva'
        ).then(function (ok) {
          if (!ok) return;
          var data = new FormData(cancelForm);
          postJson(cancelForm.getAttribute('action'),
            data,
            function (r) { App.toast(r.message || 'Reserva cancelada.', 'success'); setTimeout(function () { window.location.href = getBasePath() + '/index.php?controller=booking&action=myBookings'; }, 600); },
            function (r) { App.toast(r.message || 'No se pudo cancelar.', 'error'); }
          );
        });
      });
    }

    // 5a) Aprobar comprobante (AJAX)
    var approveForm = document.querySelector('form[data-ajax-approve]');
    if (approveForm) {
      approveForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(approveForm);
        postJson(approveForm.getAttribute('action'),
          data,
          function (r) { App.toast(r.message || 'Comprobante aprobado.', 'success'); setTimeout(function () { window.location.reload(); }, 600); },
          function (r) { App.toast(r.message || 'No se pudo aprobar.', 'error'); }
        );
      });
    }

    // 5b) Rechazar comprobante (AJAX + confirmación)
    var rejectForm = document.querySelector('form[data-ajax-reject]');
    if (rejectForm) {
      rejectForm.addEventListener('submit', function (e) {
        e.preventDefault();
        App.confirmModal(
          '¿Rechazar el comprobante de esta reserva?',
          'Rechazar comprobante'
        ).then(function (ok) {
          if (!ok) return;
          var data = new FormData(rejectForm);
          postJson(rejectForm.getAttribute('action'),
            data,
            function (r) { App.toast(r.message || 'Comprobante rechazado.', 'success'); setTimeout(function () { window.location.reload(); }, 600); },
            function (r) { App.toast(r.message || 'No se pudo rechazar.', 'error'); }
          );
        });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
