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
    var warningBox = flow.querySelector('[data-pm-warning]');
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
        if (warningBox) { warningBox.style.display = 'block'; }
        return;
      }
      var parts = [];
      var methodName = String(op.paymentMethod || '');
      var normalizedMethod = methodName.toLowerCase();
      parts.push('<strong>Datos para pagar a ' + App.escape(methodName) + ':</strong>');
      if (op.holder) parts.push('<div>Titular: <strong>' + App.escape(op.holder) + '</strong></div>');
      if (op.account) {
        var account = String(op.account);
        var label = 'Cuenta';
        if (normalizedMethod.indexOf('sinpe') >= 0) {
          account = account.replace(/\D/g, '');
          if (account.length === 8) account = '+506 ' + account.slice(0, 4) + '-' + account.slice(4);
          label = 'SINPE Móvil';
        } else if (normalizedMethod.indexOf('transfer') >= 0 || normalizedMethod.indexOf('iban') >= 0) {
          account = account.replace(/\s/g, '').toUpperCase().replace(/(.{4})/g, '$1 ').trim();
          label = 'IBAN';
        }
        parts.push('<div>' + label + ': <strong>' + App.escape(account) + '</strong></div>');
      }
      if (!op.account && /efectivo|lugar|local|cash/.test(normalizedMethod)) parts.push('<div>El pago se realiza en el lugar.</div>');
      if (op.instructions) parts.push('<div>' + App.escape(op.instructions) + '</div>');
      infoBox.innerHTML = parts.join('');
      infoBox.style.display = 'block';
      hidden.value = String(op.idPaymentMethod);
      uploadBtn.disabled = false;
      if (warningBox) { warningBox.style.display = 'none'; }
    }

    if (select) {
      select.addEventListener('change', function () {
        var selectedPayment = currentPayment();
        if (!selectedPayment) {
          showInfo(null);
          return;
        }

        var requestedValue = select.value;
        var previousValue = select.getAttribute('data-confirmed-value') || '';
        select.disabled = true;
        App.confirmModal(
          'Al pagar ya no podrá editar su reserva.',
          'Aviso antes de seleccionar el método de pago'
        ).then(function (confirmed) {
          select.disabled = false;
          if (!confirmed) {
            select.value = previousValue;
            showInfo(currentPayment());
            return;
          }
          select.value = requestedValue;
          select.setAttribute('data-confirmed-value', requestedValue);
          showInfo(selectedPayment);
        });
      });
      showInfo(currentPayment());
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!parseInt(hidden.value, 10)) {
          if (warningBox) { warningBox.style.display = 'block'; } App.toast('Debe seleccionar un método de pago', 'error');
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

// 1) Seleccionar, quitar y cambiar cantidad de servicios.
var serviceForms = document.querySelectorAll('form[data-service-selection]');
serviceForms.forEach(function (serviceForm) {
  var checkbox = serviceForm.querySelector('[data-service-toggle]');
  var quantityInput = serviceForm.querySelector('[data-service-quantity]');
  var saveButton = serviceForm.querySelector('[data-save-service-quantity]');
  var serviceLabel = serviceForm.querySelector('[data-service-label]');
  var includedPreview = serviceForm.querySelector('[data-included-preview]');
  if (!checkbox || !quantityInput) return;

  function refreshServiceControls() {
    quantityInput.disabled = !checkbox.checked;
    if (saveButton) saveButton.style.display = checkbox.checked ? '' : 'none';
    if (serviceLabel) serviceLabel.textContent = checkbox.checked ? 'Servicio agregado' : 'Agregar servicio';
  }

  function refreshIncludedPreview() {
    if (!includedPreview) return;
    var included = parseInt(includedPreview.getAttribute('data-included') || '0', 10);
    var quantity = parseInt(quantityInput.value || '0', 10);
    includedPreview.textContent = checkbox.checked && quantity > 0
      ? ' ' + Math.min(included, quantity) + ' de las ' + quantity + ' solicitadas están cubiertas por el precio base.'
      : '';
  }

  function saveSelection(selected, previousSelection) {
    var data = new FormData(serviceForm);
    data.set('selected', selected ? '1' : '0');
    data.set('quantity', quantityInput.value || '0');
    checkbox.disabled = true;
    quantityInput.disabled = true;
    if (saveButton) saveButton.disabled = true;

    postJson(
      serviceForm.getAttribute('action'),
      data,
      function (r) {
        App.toast(r.message || 'Servicio actualizado.', 'success');
        setTimeout(function () { window.location.reload(); }, 500);
      },
      function (r) {
        checkbox.checked = previousSelection;
        checkbox.disabled = false;
        refreshServiceControls();
        if (saveButton) saveButton.disabled = false;
        refreshIncludedPreview();
        App.toast(r.message || 'No se pudo actualizar el servicio.', 'error');
      }
    );
  }

  refreshServiceControls();
  refreshIncludedPreview();

  checkbox.addEventListener('change', function () {
    var selected = checkbox.checked;
    var previousSelection = !selected;
    refreshServiceControls();
    if (selected && (!quantityInput.value || parseInt(quantityInput.value, 10) < parseInt(quantityInput.min, 10))) {
      quantityInput.value = quantityInput.min;
    }
    refreshIncludedPreview();
    saveSelection(selected, previousSelection);
  });

  quantityInput.addEventListener('input', refreshIncludedPreview);
  serviceForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!checkbox.checked) return;
    refreshIncludedPreview();
    saveSelection(true, true);
  });
});

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
