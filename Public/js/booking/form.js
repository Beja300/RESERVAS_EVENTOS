(function () {
  'use strict';

  var nativeInput = document.getElementById('date');
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
  nativeInput.classList.add('datepicker-native');
  formGroup.appendChild(wrap);

  // El input nativo queda como respaldo SOLO (progessive enhancement).
  // Sin JS el formulario sigue funcionando con el picker nativo.
  nativeInput.removeAttribute('required');
  nativeInput.removeAttribute('min');

  // ---------- Estado ----------
  var selected = '';
  var anchor = new Date(today.getFullYear(), today.getMonth(), 1);

  if (nativeInput.value) {
    selected = nativeInput.value;
    var sel = parseIso(selected);
    anchor = new Date(sel.getFullYear(), sel.getMonth(), 1);
  } else {
    // Fecha sugerida: hoy y siguientes 7 días como ayuda rápida? No — solo mostramos calendario.
  }

  function formatDisplay(iso) {
    var d = parseIso(iso);
    return d.getDate() + ' de ' + monthLabels[d.getMonth()] + ' de ' + d.getFullYear();
  }

  function isUnavailable(iso) {
    return iso < todayIso || bookedMap[iso] === true;
  }

  function refreshDisplay() {
    display.textContent = selected ? 'Fecha elegida: ' + formatDisplay(selected) : 'Selecciona la fecha del evento';
  }

  function showError(message) {
    errorEl.textContent = message;
    errorEl.hidden = false;
  }

  function clearError() {
    errorEl.hidden = true;
  }

  // ---------- Render de días ----------
  function renderDays() {
    monthLabel.textContent = monthLabels[anchor.getMonth()] + ' ' + anchor.getFullYear();
    grid.textContent = '';

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

      if (isUnavailable(iso)) {
        btn.classList.add('is-unavailable');
        btn.disabled = true;
        btn.title = bookedMap[iso] === true
          ? 'Fecha no disponible: ya está reservada'
          : 'Fecha no disponible';
      } else {
        btn.classList.add('is-available');
      }

      if (iso === selected) { btn.classList.add('is-selected'); }
      if (iso === todayIso) { btn.classList.add('is-today'); }

      btn.addEventListener('click', (function (i) {
        return function () { choose(i); };
      })(iso));

      grid.appendChild(btn);
    }
  }

  // ---------- Acciones ----------
  function choose(iso) {
    if (isUnavailable(iso)) { return; }
    selected = iso;
    nativeInput.value = iso;
    refreshDisplay();
    clearError();
    close();
  }

  function open() {
    calendar.hidden = false;
    display.setAttribute('aria-expanded', 'true');
    renderDays();
  }

  function close() {
    calendar.hidden = true;
    display.setAttribute('aria-expanded', 'false');
  }

  function toggle() {
    if (calendar.hidden) { open(); } else { close(); }
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

  // Validación al enviar: fecha obligatoria y disponible.
  var form = nativeInput.closest('form');
  if (form) {
    form.addEventListener('submit', function (ev) {
      if (!selected) {
        ev.preventDefault();
        showError('Debes elegir una fecha para continuar.');
        open();
        return;
      }
      if (bookedMap[selected] === true) {
        ev.preventDefault();
        showError('Esa fecha ya está reservada para este local. Elige otra fecha.');
        selected = '';
        nativeInput.value = '';
        refreshDisplay();
        open();
        return;
      }
    });
  }

  // ---------- Inicialización ----------
  refreshDisplay();

  // Si el servidor re-renderiza con una fecha ocupada (validación server-side),
  // se limpia y se avisa al cliente.
  if (selected && bookedMap[selected] === true) {
    selected = '';
    nativeInput.value = '';
    refreshDisplay();
  }
})();