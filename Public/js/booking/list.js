(function () {
  var table = document.querySelector('.booking-table');
  if (!table) return;

  var stateCol = parseInt(table.getAttribute('data-state-col'), 10);
  var ticketCol = parseInt(table.getAttribute('data-ticket-col'), 10);

  var search = document.getElementById('booking-search');
  var state = document.getElementById('booking-state');
  var ticket = document.getElementById('booking-ticket');
  var clear = document.getElementById('booking-clear');
  var rows = table.querySelectorAll('tbody tr');

  function applyFilters() {
    var term = (search ? search.value : '').toLowerCase().trim();
    var stateTerm = state ? state.value : '';
    var ticketTerm = ticket ? ticket.value : '';

    rows.forEach(function (row) {
      var text = (row.textContent || '').toLowerCase();
      var stateCell = row.querySelector('td:nth-child(' + stateCol + ')');
      var rowState = stateCell ? (stateCell.textContent || '').trim().toLowerCase() : '';
      var ticketCell = row.querySelector('td:nth-child(' + ticketCol + ')');
      var rowTicket = ticketCell ? (ticketCell.textContent || '').trim().toLowerCase() : '';

      var matchesText = term === '' || text.indexOf(term) !== -1;
      var matchesState = stateTerm === '' || rowState === stateTerm;
      var matchesTicket = ticketTerm === '' || rowTicket === ticketTerm;
      row.style.display = (matchesText && matchesState && matchesTicket) ? '' : 'none';
    });
  }

  if (search) search.addEventListener('input', applyFilters);
  if (state) state.addEventListener('change', applyFilters);
  if (ticket) ticket.addEventListener('change', applyFilters);
  if (clear) clear.addEventListener('click', function () {
    if (search) search.value = '';
    if (state) state.value = '';
    if (ticket) ticket.value = '';
    applyFilters();
  });
})();