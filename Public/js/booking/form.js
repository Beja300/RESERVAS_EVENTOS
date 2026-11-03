(function () {
  'use strict';

  var nativeInput = document.getElementById('date');
  var endDateInput = document.getElementById('endDate');
  if (!nativeInput) { return; }

  var raw = (nativeInput.getAttribute('data-booked-dates') || '').trim();
  var bookedMap = {};
  (raw ? JSON.parse(raw) : []).forEach(function (d) { bookedMap[d] = true; });

  var monthLabels = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  var weekLabels  = ['L','M','X','J','V','S','D'];

  var today = new Date();
  today.setHours(0, 0, 0, 0);
  var todayIso = toIso(today);

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }

  function toIso(date) {
    return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
  }

  function parseIso(str) {
    var p = str.split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]);
  }

  function formatDisplay(iso) {
    var d = parseIso(iso);
    return d.getDate() + ' de ' + monthLabels[d.getMonth()] + ' de ' + d.getFullYear();
  }

  function isUnavailable(iso) {
    return iso < todayIso || bookedMap[iso] === true;
  }

  function daysBetween(a, b) {
    if (!a || !b) { return 0; }
    return Math.round((parseIso(b) - parseIso(a)) / 86400000) + 1;
  }

  // ¿Hay alguna fecha reservada dentro del rango [a, b]?
  function availRange(a, b) {
    if (!a || !b) { return true; }
    var lo = a < b ? a : b;
    var hi = a < b ? b : a;
    var cur = parseIso(lo);
    var limit = parseIso(hi).getTime();
    while (cur.getTime() <= limit) {
      if (bookedMap[toIso(cur)] === true) { return false; }
      cur.setDate(cur.getDate() + 1);
    }
    return true;
  }

  // ---------- Construcción del calendario ----------
  var wrap = document.createElement('div');
  wrap.className = 'datepicker';

  var display = document.createElement('button');
  display.type = 'button';
  display.className = 'datepicker-display';
  display.setAttribute('aria-haspopup', 'true');
  display.setAttribute('aria-expanded', 'false');

  var calendar = document.createElement('div');
  calendar.className = 'datepicker-calendar';
  calendar.hidden = true;

  var head = document.createElement('div');
  head.className = 'datepicker-head';

  var prev = document.createElement('button');
  prev.type = 'button';
  prev.className = 'datepicker-nav';
  prev.setAttribute('aria-label', 'Mes anterior');
  prev.textContent = '\u2039';

  var monthLabel = document.createElement('span');
  monthLabel.className = 'datepicker-month';

  var next = document.createElement('button');
  next.type = 'button';
  next.className = 'datepicker-nav';
  next.setAttribute('aria-label', 'Mes siguiente');
  next.textContent = '\u203A';

  head.appendChild(prev);
  head.appendChild(monthLabel);
  head.appendChild(next);

  var weekRow = document.createElement('div');
  weekRow.className = 'datepicker-weekdays';
  weekLabels.forEach(function (w) {
    var el = document.createElement('span');
    el.textContent = w;
    weekRow.appendChild(el);
  });

  var grid = document.createElement('div');
  grid.className = 'datepicker-days';

  calendar.appendChild(head);
  calendar.appendChild(weekRow);
  calendar.appendChild(grid);

  var errorEl = document.createElement('p');
  errorEl.className = 'datepicker-error';
  errorEl.hidden = true;

  wrap.appendChild(display);
  wrap.appendChild(calendar);
  wrap.appendChild(errorEl);

  var formGroup = nativeInput.closest('.form-group') || nativeInput.parentNode;
  // El calendario reemplaza a ambos inputs nativos (quedan como respaldo sin JS).
  nativeInput.classList.add('datepicker-native');
  if (endDateInput) { endDateInput.classList.add('datepicker-native'); }
  formGroup.appendChild(wrap);

  nativeInput.removeAttribute('required');
  nativeInput.removeAttribute('min');
  if (endDateInput) {
    endDateInput.removeAttribute('required');
    endDateInput.removeAttribute('min');
  }

  // ---------- Estado (rango [inicio, final]) ----------
  var rangeStart = '';
  var rangeEnd = '';
  var lastHovered = '';
  var anchor = new Date(today.getFullYear(), today.getMonth(), 1);

  if (nativeInput.value) {
    rangeStart = nativeInput.value;
    var sel = parseIso(rangeStart);
    anchor = new Date(sel.getFullYear(), sel.getMonth(), 1);
  }
  if (endDateInput && endDateInput.value) {
    rangeEnd = endDateInput.value;
  }

  var pickingEnd = function () { return rangeStart !== '' && rangeEnd === ''; };

  function refreshDisplay() {
    if (rangeStart === '') {
      display.textContent = 'Selecciona el rango de fechas del evento';
      display.classList.remove('has-value');
      return;
    }
    if (rangeEnd === '') {
      display.textContent = 'Fecha de inicio: ' + formatDisplay(rangeStart) + ' — elige la fecha final';
      display.classList.remove('has-value');
      return;
    }
    var days = daysBetween(rangeStart, rangeEnd);
    display.textContent = 'Del ' + formatDisplay(rangeStart) + ' al ' + formatDisplay(rangeEnd)
      + ' (' + days + ' día' + (days > 1 ? 's' : '') + ')';
    display.classList.add('has-value');
  }

  function showError(message) {
    errorEl.textContent = message;
    errorEl.hidden = false;
  }

  function clearError() {
    errorEl.hidden = true;
  }

  // ---------- Resaltado del rango ----------
  function isBetween(iso, a, b) {
    if (!a || !b) { return false; }
    var lo = a < b ? a : b;
    var hi = a < b ? b : a;
    return iso > lo && iso < hi;
  }

  function markRange(a, b, cls) {
    var days = grid.querySelectorAll('.datepicker-day[data-iso]');
    days.forEach(function (btn) {
      var d = btn.getAttribute('data-iso');
      btn.classList.toggle(
        cls,
        isBetween(d, a, b) && !bookedMap[d] && d >= todayIso
      );
    });
  }

  function clearHover() {
    grid.querySelectorAll('.is-hover-range').forEach(function (el) {
      el.classList.remove('is-hover-range');
    });
  }

  // ---------- Render de días ----------
  function renderDays() {
    monthLabel.textContent = monthLabels[anchor.getMonth()] + ' ' + anchor.getFullYear();
    grid.textContent = '';
    lastHovered = '';

    var first = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
    var offset = (first.getDay() + 6) % 7; // semana comenzando en lunes
    var daysInMonth = new Date(anchor.getFullYear(), anchor.getMonth() + 1, 0).getDate();

    for (var i = 0; i < offset; i++) {
      var blank = document.createElement('span');
      blank.className = 'datepicker-day is-blank';
      grid.appendChild(blank);
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var date = new Date(anchor.getFullYear(), anchor.getMonth(), d);
      var iso = toIso(date);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'datepicker-day';
      btn.textContent = d;
      btn.setAttribute('data-iso', iso);

      if (isUnavailable(iso)) {
        btn.classList.add('is-unavailable');
        btn.disabled = true;
        btn.title = bookedMap[iso] === true
          ? 'Fecha no disponible: ya está reservada'
          : 'Fecha no disponible';
      } else {
        btn.classList.add('is-available');
      }

      if (iso === rangeStart || iso === rangeEnd) {
        btn.classList.add('is-selected');
      } else if (isBetween(iso, rangeStart, rangeEnd)) {
        btn.classList.add('is-in-range');
      }
      if (iso === todayIso) { btn.classList.add('is-today'); }

      btn.addEventListener('click', (function (i) {
        return function () { choose(i); };
      })(iso));

      btn.addEventListener('mouseenter', (function (i) {
        return function () { hover(i); };
      })(iso));

      grid.appendChild(btn);
    }
  }

  // ---------- Selección (1er clic = inicio, 2º clic = final) ----------
  function choose(iso) {
    if (isUnavailable(iso)) { return; }
    clearError();

    if (rangeStart === '') {
      rangeStart = iso;
      rangeEnd = '';
      anchor = new Date(parseIso(iso).getFullYear(), parseIso(iso).getMonth(), 1);
      syncInputs();
      refreshDisplay();
      renderDays();
      open();
      return;
    }

    if (rangeEnd === '') {
      if (iso < rangeStart) {
        // Clic en una fecha anterior: reinicia desde esa fecha (nuevo inicio)
        rangeStart = iso;
        anchor = new Date(parseIso(iso).getFullYear(), parseIso(iso).getMonth(), 1);
        syncInputs();
        refreshDisplay();
        renderDays();
        open();
        return;
      }
      if (!availRange(rangeStart, iso)) {
        showError('El rango elegido incluye una fecha ya reservada. Elige otra fecha final.');
        open();
        return;
      }
      rangeEnd = iso;
      syncInputs();
      refreshDisplay();
      close();
      return;
    }

    // Rango completo: cualquier clic reinicia desde la fecha elegida
    rangeStart = iso;
    rangeEnd = '';
    anchor = new Date(parseIso(iso).getFullYear(), parseIso(iso).getMonth(), 1);
    syncInputs();
    refreshDisplay();
    renderDays();
    open();
  }

  // ---------- Hover: previsualizar rango mientras se elige la fecha final ----------
  function hover(iso) {
    if (!pickingEnd()) { return; }
    clearHover();
    if (iso <= rangeStart) { return; }
    lastHovered = iso;
    markRange(rangeStart, iso, 'is-hover-range');
  }

  function open() {
    calendar.hidden = false;
    display.setAttribute('aria-expanded', 'true');
    renderDays();
  }

  function close() {
    calendar.hidden = true;
    display.setAttribute('aria-expanded', 'false');
    clearHover();
  }

  function toggle() {
    if (calendar.hidden) { open(); } else { close(); }
  }

  // ---------- Preview de precio (acumulación por día) ----------
  var priceEl = document.querySelector('[data-venue-price]');
  var priceSummary = document.getElementById('bookingPriceSummary');

  function syncInputs() {
    nativeInput.value = rangeStart;
    if (endDateInput) { endDateInput.value = rangeEnd; }
    updatePricePreview();
  }

  function updatePricePreview() {
    if (!priceEl || !priceSummary) { return; }
    var days = daysBetween(rangeStart, rangeEnd);
    if (days >= 1) {
      var price = parseFloat(priceEl.getAttribute('data-venue-price')) || 0;
      var fmt = window.App ? window.App.formatMoney(price * days) : ('\u20A1 ' + (price * days).toFixed(2));
      var fmtDay = window.App ? window.App.formatMoney(price) : ('\u20A1 ' + price.toFixed(2));
      priceSummary.textContent = 'Duración: ' + days + ' día' + (days > 1 ? 's' : '')
        + ' -> ' + fmt + ' (precio por día ' + fmtDay + '). El total mostrará los servicios que agregues.';
    } else if (rangeStart !== '' && rangeEnd !== '') {
      priceSummary.textContent = 'Revisa las fechas: la fecha final debe ser igual o posterior a la de inicio.';
    }
  }

  // ---------- Eventos ----------
  display.addEventListener('click', toggle);

  prev.addEventListener('click', function () {
    anchor = new Date(anchor.getFullYear(), anchor.getMonth() - 1, 1);
    renderDays();
  });

  next.addEventListener('click', function () {
    anchor = new Date(anchor.getFullYear(), anchor.getMonth() + 1, 1);
    renderDays();
  });

  document.addEventListener('click', function (ev) {
    if (!wrap.contains(ev.target)) { close(); }
  });

  // ---------- Validación al enviar ----------
  var form = nativeInput.closest('form');
  if (form) {
    form.addEventListener('submit', function (ev) {
      if (rangeStart === '') {
        ev.preventDefault();
        showError('Debes elegir la fecha de inicio para continuar.');
        open();
        return;
      }
      if (rangeEnd === '') {
        ev.preventDefault();
        showError('Elige también la fecha final en el mismo calendario.');
        open();
        return;
      }
      if (!availRange(rangeStart, rangeEnd)) {
        ev.preventDefault();
        showError('El rango elegido incluye una fecha ya reservada. Elige otro rango.');
        open();
        return;
      }
    });
  }

  // ---------- Campo "Otro": descripción del evento ----------
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

  // ---------- Inicialización ----------
  // Si el servidor re-renderiza con un rango inválido (fecha ocupada o fin
  // anterior al inicio), se limpia y se avisa al cliente.
  var invalidRange = false;
  if (!availRange(rangeStart || todayIso, rangeEnd || todayIso)) { invalidRange = true; }
  if (rangeStart !== '' && rangeEnd !== '' && rangeEnd < rangeStart) { invalidRange = true; }

  if (invalidRange || (rangeStart !== '' && bookedMap[rangeStart] === true)) {
    rangeStart = '';
    rangeEnd = '';
    nativeInput.value = '';
    if (endDateInput) { endDateInput.value = ''; }
  }

  refreshDisplay();
  updatePricePreview();
})();