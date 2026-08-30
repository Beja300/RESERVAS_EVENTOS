/**
 * owner-payment.js — Script específico de App/View/Owner/PaymentData.php
 *
 *  1. Plegar/desplegar el formulario de métodos de cobro con un botón.
 *  2. Editar un método existente: carga sus datos en el formulario.
 */
(function (window, document) {
  'use strict';

  function init() {
    var wrap = document.querySelector('[data-payment-form-wrap]');
    var toggle = document.querySelector('[data-toggle-payment-form]');
    var form = document.querySelector('[data-payment-form]');

    if (!wrap || !toggle) return;

    var openLabel = '+ Agregar método de pago nuevo';
    var closeLabel = 'Cerrar formulario';

    function setOpen(open) {
      wrap.hidden = !open;
      toggle.textContent = open ? closeLabel : openLabel;
      if (open && form) {
        var first = form.querySelector('select, input[type="text"], textarea');
        if (first) first.focus();
      }
    }

    function resetForm() {
      if (!form) return;
      form.reset();
      var idField = form.querySelector('[data-payment-id]');
      if (idField) idField.value = '0';
      var active = form.querySelector('[name="active"]');
      if (active) active.checked = true;
    }

    toggle.addEventListener('click', function () {
      setOpen(wrap.hidden);
      if (!wrap.hidden) resetForm();
    });

    var editButtons = document.querySelectorAll('[data-edit-payment]');
    for (var i = 0; i < editButtons.length; i++) {
      (function (btn) {
        btn.addEventListener('click', function () {
          if (!form) return;

          var select = form.querySelector('[name="paymentMethodId"]');
          var methodId = btn.getAttribute('data-method-id');
          if (select && select.querySelector('option[value="' + methodId + '"]')) {
            select.value = methodId;
          }

          form.querySelector('#holder').value = btn.getAttribute('data-holder') || '';
          form.querySelector('#account').value = btn.getAttribute('data-account') || '';
          form.querySelector('#instructions').value = btn.getAttribute('data-instructions') || '';

          var active = form.querySelector('[name="active"]');
          if (active) active.checked = btn.getAttribute('data-active') === '1';

          var idField = form.querySelector('[data-payment-id]');
          if (idField) idField.value = btn.getAttribute('data-id');

          setOpen(true);
          form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      })(editButtons[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);