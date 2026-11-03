(function () {
  var input = document.querySelector('input[type="date"][data-booked-dates]');
  if (!input) { return; }
  var current = input.getAttribute('data-current-date') || '';
  var booked = (input.getAttribute('data-booked-dates') || '').trim();
  var dates = booked ? JSON.parse(booked) : [];
  var blocked = dates.filter(function (d) { return d !== current; });

  input.addEventListener('input', function () {
    var val = input.value;
    if (blocked.indexOf(val) !== -1) {
      input.setCustomValidity('Esa fecha ya está ocupada por otra reserva. Elige otra fecha.');
    } else {
      input.setCustomValidity('');
    }
  });
})();