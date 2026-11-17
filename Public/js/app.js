/**
 * app.js — Núcleo genérico (común a todas las vistas).
 *
 * Expone el objeto global `App` con utilidades reutilizables. No contiene
 * lógica específica de ninguna vista; cada vista carga su propio script en
 * Public/js/ {nombre}.js (ver helper js_url()).
 */
(function (window, document) {
  'use strict';

  var App = {};

  // ---------- Utilidades ----------
  App.escape = function (value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  App.parseMoney = function (value) {
    return parseFloat(String(value).replace(/[^\d.-]/g, '')) || 0;
  };

  App.formatPhoneInput = function (field) {
    var raw = field.value || '';
    var before = raw.slice(0, field.selectionStart || 0);
    var beforeDigits = before.replace(/\D/g, '').length;
    var digits = raw.replace(/\D/g, '');
    if ((/^\s*\+?506(?:\D|$)/.test(raw) || digits.length > 8) && digits.slice(0, 3) === '506') {
      digits = digits.slice(3);
      if (/^\s*\+?506/.test(before)) beforeDigits = Math.max(0, beforeDigits - 3);
    }
    digits = digits.slice(0, 8);

    if (digits === '') {
      field.value = '';
      return;
    }

    var formatted = digits.slice(0, 4);
    if (digits.length > 4) formatted += '-' + digits.slice(4);
    field.value = formatted;

    var caret = Math.min(beforeDigits, 4);
    if (beforeDigits > 4) caret = beforeDigits + 1;
    if (caret > formatted.length) caret = formatted.length;
    try { field.setSelectionRange(caret, caret); } catch (ignore) {}
  };

  App.initPhoneMasks = function () {
    var fields = document.querySelectorAll('[data-mask="phone"], input[type="tel"][name="phoneNumber"]');
    for (var i = 0; i < fields.length; i++) {
      (function (field) {
        var group = field.closest('.phone-input-group');
        if (!group) {
          group = document.createElement('div');
          group.className = 'phone-input-group';
          field.parentNode.insertBefore(group, field);
          var prefix = document.createElement('span');
          prefix.className = 'phone-prefix';
          prefix.textContent = field.getAttribute('data-phone-prefix') || '+506';
          prefix.setAttribute('aria-hidden', 'true');
          group.appendChild(prefix);
          group.appendChild(field);
        }
        field.setAttribute('maxlength', '15');
        field.setAttribute('inputmode', 'numeric');
        field.setAttribute('placeholder', '8888-8888');
        if ((field.value || '').trim() !== '') App.formatPhoneInput(field);
        field.addEventListener('input', function () { App.formatPhoneInput(field); });
        field.addEventListener('paste', function () {
          setTimeout(function () { App.formatPhoneInput(field); }, 0);
        });
      })(fields[i]);
    }
  };

  App.formatMoney = function (amount) {
    return '\u20A1 ' + Number(amount).toLocaleString('es-CR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  App.formatCurrencyInput = function (field) {
    var raw = field.value || '';
    var before = raw.slice(0, field.selectionStart || 0);
    var tokensBefore = before.replace(/[^\d.]/g, '').length;
    var beforeNumber = before.replace(/[^\d,.]/g, '');
    if (beforeNumber.indexOf('.') === -1 && /,\d{0,2}$/.test(beforeNumber)) tokensBefore++;
    var numericRaw = raw.replace(/[^\d.,]/g, '');
    if (numericRaw.indexOf('.') === -1 && /,$/.test(numericRaw)) {
      numericRaw = numericRaw.slice(0, -1) + '.';
    } else if (numericRaw.indexOf('.') === -1 && /,\d{1,2}$/.test(numericRaw)) {
      numericRaw = numericRaw.replace(/,(\d{1,2})$/, '.$1');
    }
    var cleaned = numericRaw.replace(/,/g, '');
    var decimalAt = cleaned.indexOf('.');
    if (decimalAt !== -1) {
      cleaned = cleaned.slice(0, decimalAt + 1) + cleaned.slice(decimalAt + 1).replace(/\./g, '');
    }
    var parts = cleaned.split('.');
    var integer = parts[0].replace(/^0+(?=\d)/, '');
    if (integer === '' && decimalAt === 0) integer = '0';
    var decimals = (parts[1] || '').slice(0, 2);
    var formatted = integer ? Number(integer).toLocaleString('en-US') : '';
    if (decimalAt !== -1) formatted += '.' + decimals;
    field.value = formatted ? '\u20A1 ' + formatted : '';

    var seen = 0;
    var caret = field.value.length;
    for (var i = 2; i < field.value.length; i++) {
      if (/[\d.]/.test(field.value.charAt(i))) seen++;
      if (seen >= tokensBefore) {
        caret = i + 1;
        break;
      }
    }
    if (tokensBefore === 0 && field.value !== '') caret = 2;
    try { field.setSelectionRange(caret, caret); } catch (ignore) {}
  };

  App.initCurrencyMasks = function () {
    var fields = document.querySelectorAll('[data-currency-mask="credit"]');
    for (var i = 0; i < fields.length; i++) {
      (function (field) {
        field.setAttribute('inputmode', 'decimal');
        field.setAttribute('placeholder', '\u20A1 0.00');
        if ((field.value || '').trim() !== '') App.formatCurrencyInput(field);
        field.addEventListener('input', function () { App.formatCurrencyInput(field); });
        field.addEventListener('paste', function () {
          setTimeout(function () { App.formatCurrencyInput(field); }, 0);
        });
      })(fields[i]);
    }
  };

  // ---------- Toast (notificación no intrusiva) ----------
  App.toast = function (message, type) {
    var types = ['success', 'error', 'info', 'warning'];
    if (types.indexOf(type) === -1) type = 'info';
    var existing = document.querySelector('.app-toast-wrap');
    var wrap = existing || document.createElement('div');
    if (!existing) {
      wrap.className = 'app-toast-wrap';
      document.body.appendChild(wrap);
    }
    var toast = document.createElement('div');
    toast.className = 'app-toast app-toast--' + type;
    toast.textContent = message;
    wrap.appendChild(toast);
    setTimeout(function () {
      toast.classList.add('app-toast--out');
      setTimeout(function () { toast.remove(); }, 300);
    }, 2600);
  };

  // ---------- Modal de confirmación ----------
  // Sustituye a confirm() nativo. Devuelve una promesa true/false.
  App.confirmModal = function (message, title) {
    return new Promise(function (resolve) {
      var overlay = document.createElement('div');
      overlay.className = 'modal-overlay';

      var box = document.createElement('div');
      box.className = 'modal';

      var head = document.createElement('div');
      head.className = 'modal__head';
      head.textContent = title || '¿Confirmar acción?';

      var body = document.createElement('div');
      body.className = 'modal__body';
      body.textContent = message || '¿Deseas continuar?';

      var foot = document.createElement('div');
      foot.className = 'modal__foot';

      var btnCancel = document.createElement('button');
      btnCancel.type = 'button';
      btnCancel.className = 'btn btn-sm btn-ghost';
      btnCancel.textContent = 'Cancelar';

      var btnOk = document.createElement('button');
      btnOk.type = 'button';
      btnOk.className = 'btn btn-sm btn-danger';
      btnOk.textContent = 'Confirmar';

      foot.appendChild(btnCancel);
      foot.appendChild(btnOk);
      box.appendChild(head);
      box.appendChild(body);
      box.appendChild(foot);
      overlay.appendChild(box);
      document.body.appendChild(overlay);

      var close = function (result) {
        overlay.remove();
        resolve(result);
      };

      btnCancel.addEventListener('click', function () { close(false); });
      btnOk.addEventListener('click', function () { close(true); });
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close(false);
      });

      function onKey(e) {
        if (e.key === 'Escape') {
          document.removeEventListener('keydown', onKey);
          close(false);
        }
      }
      document.addEventListener('keydown', onKey);
      btnOk.focus();
    });
  };

  // ---------- Confirmaciones con data-confirm (genérico) ----------
  App.initConfirm = function () {
    var forms = document.querySelectorAll('form[data-confirm]');
    for (var i = 0; i < forms.length; i++) {
      (function (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          var message = form.getAttribute('data-confirm');
          var title = form.getAttribute('data-confirm-title') || undefined;
          App.confirmModal(message, title).then(function (ok) {
            if (ok) form.submit();
          });
        });
      })(forms[i]);
    }
  };

  // ---------- Filtro en vivo: tablas (data-table-filter="selector") ----------
  App.initTableFilter = function () {
    var inputs = document.querySelectorAll('[data-table-filter]');
    for (var i = 0; i < inputs.length; i++) {
      (function (input) {
        var selector = input.getAttribute('data-table-filter');
        input.addEventListener('input', function () {
          var tables = document.querySelectorAll(selector);
          var q = App.escape(input.value.toLowerCase().trim());
          for (var t = 0; t < tables.length; t++) {
            var rows = tables[t].querySelectorAll('tbody tr');
            for (var r = 0; r < rows.length; r++) {
              var matches = rows[r].textContent.toLowerCase().indexOf(q) !== -1;
              rows[r].style.display = matches ? '' : 'none';
            }
          }
        });
      })(inputs[i]);
    }
  };

  // ---------- Filtro en vivo: tarjetas (data-card-filter="selector") ----------
  App.initCardFilter = function () {
    var inputs = document.querySelectorAll('[data-card-filter]');
    for (var j = 0; j < inputs.length; j++) {
      (function (input) {
        var items = document.querySelectorAll(input.getAttribute('data-card-filter'));
        input.addEventListener('input', function () {
          var q = App.escape(input.value.toLowerCase().trim());
          for (var k = 0; k < items.length; k++) {
            var match = items[k].textContent.toLowerCase().indexOf(q) !== -1;
            items[k].style.display = match ? '' : 'none';
          }
        });
      })(inputs[j]);
    }
  };

  // ---------- Validación en vivo (genérica, data-validate) ----------
  App.validateField = function (field) {
    var value = (field.value || '').trim();
    var rules = (field.getAttribute('data-validate') || '').split(' ');
    var min = field.getAttribute('data-min');
    var max = field.getAttribute('data-max');
    var rangeMessage = field.getAttribute('data-range-message');
    var numericValue = field.hasAttribute('data-currency-mask') ? App.parseMoney(value) : parseFloat(value);
    var container = field.closest('.form-group') || field.parentNode;
    var err = container.querySelector('.field-error');
    var message = '';

    if (rules.indexOf('required') !== -1 && value === '') {
      message = 'Este campo es obligatorio.';
    } else if (value !== '') {
      if (rules.indexOf('phone') !== -1 && !/^\d{8}$/.test(value.replace(/^\+?506\s*/, '').replace(/\D/g, ''))) {
        message = 'El teléfono debe tener 8 dígitos.';
      } else if (rules.indexOf('email') !== -1 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        message = 'Correo electrónico no válido.';
      } else if (rules.indexOf('password') !== -1 && !/^(?=.*\d).{8,}$/.test(value)) {
        message = 'La contraseña debe tener al menos 8 caracteres y un número.';
      } else if (rules.indexOf('match') !== -1) {
        var matchId = field.getAttribute('data-match') || '';
        var matchEl = matchId ? document.getElementById(matchId) : null;
        if (matchEl && value !== (matchEl.value || '').trim()) {
          message = field.getAttribute('data-match-message') || 'Los valores no coinciden.';
        }
      } else if (rules.indexOf('url') !== -1 && !/^https?:\/\/[^\s]+$/i.test(value)) {
        message = 'La URL no es válida (debe iniciar con http:// o https://).';
      } else if (rules.indexOf('identification') !== -1
        && !/^\d{1}-\d{4}-\d{4}$/.test(value)
        && !/^\d{1}-\d{3}-\d{6}$/.test(value)
        && !/^\d{9,12}$/.test(value.replace(/[\s-]/g, ''))) {
        message = 'Formato de cédula no válido (ej. 1-2345-0678).';
      } else if (rules.indexOf('account') !== -1
        && !/^\d{8}$/.test(value.replace(/[\s-]/g, ''))
        && !/^[A-Za-z]{2}[\dA-Za-z]{18,22}$/.test(value.replace(/[\s-]/g, ''))) {
        message = 'Cuenta no válida (8 dígitos SINPE o IBAN).';
      } else if (rules.indexOf('minprice') !== -1 && (isNaN(numericValue) || numericValue < 0)) {
        message = 'El precio no puede ser negativo.';
      } else if (rules.indexOf('positive') !== -1 && (isNaN(numericValue) || numericValue <= 0)) {
        message = 'Debe ser un número mayor que 0.';
      } else if (rules.indexOf('minamount') !== -1 && min !== null && numericValue + 0.001 < Number(min)) {
        message = 'El abono mínimo es ' + App.formatMoney(Number(min)) + '.';
      } else if (rules.indexOf('intrange') !== -1) {
        if (!/^\d+$/.test(value)) {
          message = 'Debe ser un número entero.';
        } else if (min !== null && Number(value) < Number(min)) {
          message = rangeMessage || 'El valor mínimo permitido es ' + min + '.';
        } else if (max !== null && Number(value) > Number(max)) {
          message = rangeMessage || 'El valor máximo permitido es ' + max + '.';
        }
      }
    }

    if (!err) {
      err = document.createElement('div');
      err.className = 'field-error';
      container.appendChild(err);
    }
    err.textContent = message;
    field.classList.toggle('is-invalid', message !== '');
    return message === '';
  };

  App.initValidation = function () {
    var fields = document.querySelectorAll('[data-validate]');
    for (var i = 0; i < fields.length; i++) {
      (function (field) {
        field.addEventListener('blur', function () { App.validateField(field); });
        field.addEventListener('input', function () {
          if (field.classList.contains('is-invalid')) App.validateField(field);
        });
      })(fields[i]);
    }

    var forms = document.querySelectorAll('form[data-validate-form]');
    for (var j = 0; j < forms.length; j++) {
      (function (form) {
        form.addEventListener('submit', function (e) {
          var valid = true;
          var fs = form.querySelectorAll('[data-validate]');
          for (var k = 0; k < fs.length; k++) {
            if (!App.validateField(fs[k])) valid = false;
          }
          if (!valid) {
            e.preventDefault();
            var firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.focus();
            App.toast('Revisa los campos marcados.', 'error');
          }
        });
      })(forms[j]);
    }
  };

  // ---------- Inicialización global (común a cualquier vista) ----------
  App.init = function () {
    App.initPhoneMasks();
    App.initCurrencyMasks();
    App.initConfirm();
    App.initTableFilter();
    App.initCardFilter();
    App.initValidation();
  };

  window.App = App;
})(window, document);
