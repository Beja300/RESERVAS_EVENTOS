(function () {
  var table = document.querySelector('.venue-table');
  if (!table) return;

  var search = document.getElementById('venue-search');
  var state = document.getElementById('venue-state');
  var clear = document.getElementById('venue-clear');
  var rows = table.querySelectorAll('tbody tr');

  function applyFilters() {
    var term = (search ? search.value : '').toLowerCase().trim();
    var stateTerm = state ? state.value : '';

    rows.forEach(function (row) {
      var text = (row.textContent || '').toLowerCase();
      var stateCell = row.querySelector('td:nth-child(4)');
      var rowState = stateCell ? (stateCell.textContent || '').trim().toLowerCase() : '';

      var matchesText = term === '' || text.indexOf(term) !== -1;
      var matchesState = stateTerm === '' || rowState === stateTerm;
      row.style.display = (matchesText && matchesState) ? '' : 'none';
    });
  }

  if (search) search.addEventListener('input', applyFilters);
  if (state) state.addEventListener('change', applyFilters);
  if (clear) clear.addEventListener('click', function () {
    if (search) search.value = '';
    if (state) state.value = '';
    applyFilters();
  });
})();