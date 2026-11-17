(function () {
  'use strict';

  var startInput = document.getElementById('date');
  var endInput = document.getElementById('endDate');
  if (!startInput || !endInput) { return; }

  var dateError = document.getElementById('bookingDateError');

  var bookedMap = {};
  try {
    var raw = (startInput.getAttribute('data-booked-dates') || '').trim();
    (raw ? JSON.parse(raw) : []).forEach(function (d) { bookedMap[d] = true; });
  } catch (ignore) {
    bookedMap = {};
  }

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }

  function toIso(date) {
    return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
  }

  function parseIso(str) {
    var p = str.split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]);
  }

  function daysBetween(a, b) {
    if (!a || !b) { return 0; }
    return Math.round((parseIso(b) - parseIso(a)) / 86400000) + 1;
  }

  function rangeHasBooking(a, b) {
    if (!a || !b) { return false; }
    var lo = a < b ? a : b;
    var hi = a < b ? b : a;
    var cur = parseIso(lo);
    var limit = parseIso(hi).getTime();
    while (cur.getTime() <= limit) {
      if (bookedMap[toIso(cur)] === true) { return true; }
      cur.setDate(cur.getDate() + 1);
    }
    return false;
  }

  function showDateError(message) {
    if (!dateError) { return; }
    dateError.textContent = message;
    dateError.hidden = false;
  }

  function clearDateError() {
    if (!dateError) { return; }
    dateError.textContent = '';
    dateError.hidden = true;
  }

  var today = new Date();
  today.setHours(0, 0, 0, 0);
  var todayIso = toIso(today);

  function syncDateLimits() {
    var minEnd = startInput.value || todayIso;
    endInput.min = minEnd;
    if (endInput.value !== '' && endInput.value < minEnd) {
      endInput.value = '';
    }
  }

  var priceEl = document.querySelector('[data-venue-price]');
  var priceSummary = document.getElementById('bookingPriceSummary');
  var defaultPriceSummary = priceSummary ? priceSummary.textContent : '';
  var blockSelect = document.querySelector('[data-rental-block]');

  function updatePricePreview() {
    if (!priceEl || !priceSummary) { return; }
    var days = daysBetween(startInput.value, endInput.value);
    if (days >= 1) {
      var price = parseFloat(priceEl.getAttribute('data-venue-price')) || 0;
      if (blockSelect && blockSelect.selectedOptions.length > 0) {
        price = parseFloat(blockSelect.selectedOptions[0].getAttribute('data-price')) || price;
      }
      var fmt = window.App ? window.App.formatMoney(price * days) : ('\u20A1 ' + (price * days).toFixed(2));
      var fmtDay = window.App ? window.App.formatMoney(price) : ('\u20A1 ' + price.toFixed(2));
      var servicesTotal = servicesSubtotal();
      var extra = '';
      if (servicesTotal > 0) {
        var fmtServices = window.App ? window.App.formatMoney(servicesTotal) : ('\u20A1 ' + servicesTotal.toFixed(2));
        extra = ' + servicios ' + fmtServices;
      }
      priceSummary.textContent = 'Duración: ' + days + ' día' + (days > 1 ? 's' : '')
        + ' -> ' + fmt + ' (precio por día ' + fmtDay + ')' + extra + '. El total mostrará el IVA.';
    } else if (startInput.value !== '' && endInput.value !== '') {
      priceSummary.textContent = 'Revisa las fechas: la fecha final debe ser igual o posterior a la de inicio.';
    } else {
      priceSummary.textContent = defaultPriceSummary;
    }
  }

  function onDatesChanged() {
    clearDateError();
    syncDateLimits();
    updatePricePreview();
  }

  startInput.addEventListener('change', onDatesChanged);
  startInput.addEventListener('input', onDatesChanged);
  endInput.addEventListener('change', onDatesChanged);
  endInput.addEventListener('input', onDatesChanged);

  var serviceRows = Array.prototype.slice.call(document.querySelectorAll('[data-service-row]'));

  function serviceRowData(row) {
    var price = parseFloat(row.getAttribute('data-price')) || 0;
    var min = parseInt(row.getAttribute('data-min'), 10) || 1;
    var maxRaw = row.getAttribute('data-max');
    var max = maxRaw === '' ? null : (parseInt(maxRaw, 10) || 0);
    var included = parseInt(row.getAttribute('data-included'), 10) || 0;
    var input = row.querySelector('[data-service-qty]');
    var qty = input ? (parseInt(input.value, 10) || 0) : 0;
    if (qty < 0) { qty = 0; }
    if (max !== null && qty > max) {
      qty = max;
      if (input) { input.value = qty; }
    }
    var free = Math.min(qty, included);
    var subtotal = Math.max(0, (qty - free) * price);
    return { row: row, input: input, qty: qty, min: min, max: max, included: included, price: price, subtotal: subtotal };
  }

  function servicesSubtotal() {
    var total = 0;
    serviceRows.forEach(function (row) { total += serviceRowData(row).subtotal; });
    return total;
  }

  function refreshServiceRows() {
    serviceRows.forEach(function (row) {
      var data = serviceRowData(row);
      var out = row.querySelector('[data-service-subtotal]');
      if (out) {
        out.textContent = window.App ? window.App.formatMoney(data.subtotal) : ('\u20A1 ' + data.subtotal.toFixed(2));
      }
      if (data.input) {
        data.input.classList.toggle('is-invalid', data.qty > 0 && data.qty < data.min);
      }
    });
  }

  serviceRows.forEach(function (row) {
    var input = row.querySelector('[data-service-qty]');
    if (!input) { return; }
    input.addEventListener('input', function () { refreshServiceRows(); updatePricePreview(); });
    input.addEventListener('change', function () { refreshServiceRows(); updatePricePreview(); });
  });

  var form = startInput.closest('form');
  if (form) {
    form.addEventListener('submit', function (ev) {
      clearDateError();

      if (startInput.value === '') {
        ev.preventDefault();
        showDateError('Debes elegir la fecha de inicio para continuar.');
        startInput.focus();
        return;
      }
      if (endInput.value === '') {
        ev.preventDefault();
        showDateError('Debes elegir la fecha final para continuar.');
        endInput.focus();
        return;
      }
      if (endInput.value < startInput.value) {
        ev.preventDefault();
        showDateError('La fecha final debe ser igual o posterior a la fecha de inicio.');
        endInput.focus();
        return;
      }
      if (rangeHasBooking(startInput.value, endInput.value)) {
        ev.preventDefault();
        showDateError('El rango elegido incluye una fecha ya reservada. Elige otras fechas.');
        startInput.focus();
        return;
      }

      var invalid = null;
      serviceRows.forEach(function (row) {
        var data = serviceRowData(row);
        if (data.qty > 0 && data.qty < data.min) { invalid = data; }
      });
      if (!invalid) { return; }

      ev.preventDefault();
      var err = document.getElementById('serviceQtyError');
      if (!err) {
        err = document.createElement('p');
        err.className = 'datepicker-error';
        err.id = 'serviceQtyError';
        invalid.row.parentNode.appendChild(err);
      }
      err.textContent = 'La cantidad mínima por servicio es ' + invalid.min + '. Corrige o deja 0.';
      err.hidden = false;
      invalid.input.focus();
    });
  }

  if (blockSelect) {
    blockSelect.addEventListener('change', updatePricePreview);
  }

  var eventTypeSelect = document.getElementById('eventType');
  var eventDetailGroup = document.getElementById('eventDetailGroup');
  var eventDetailInput = document.getElementById('eventDetail');

  if (eventTypeSelect && eventDetailGroup) {
    function syncEventDetail() {
      var show = eventTypeSelect.value === 'otro';
      eventDetailGroup.style.display = show ? '' : 'none';
    }
    eventTypeSelect.addEventListener('change', syncEventDetail);

    if (eventDetailInput) {
      var bookForm = eventDetailInput.closest('form');
      if (bookForm) {
        bookForm.addEventListener('submit', function (ev) {
          if (eventTypeSelect.value !== 'otro') { return; }
          if (eventDetailInput.value.trim() !== '') { return; }
          ev.preventDefault();
          eventDetailGroup.style.display = '';
          eventDetailInput.focus();
          var err = document.getElementById('eventDetailError');
          if (!err) {
            err = document.createElement('p');
            err.className = 'datepicker-error';
            err.id = 'eventDetailError';
            err.textContent = 'Escribe de qué trata tu evento antes de continuar.';
            eventDetailGroup.appendChild(err);
          }
          err.hidden = false;
        });
      }
    }
  }

  syncDateLimits();
  refreshServiceRows();
  updatePricePreview();
})();
