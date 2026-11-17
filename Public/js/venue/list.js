(function () {
  var tables = document.querySelectorAll('.venue-table');
  if (!tables.length) return;

  var search = document.getElementById('venue-search');
  var clear = document.getElementById('venue-clear');
  var rows = [];
  tables.forEach(function (table) {
    table.querySelectorAll('tbody tr').forEach(function (row) { rows.push(row); });
  });

  function applyFilters() {
    var term = (search ? search.value : '').toLowerCase().trim();

    rows.forEach(function (row) {
      if (row.classList.contains('section-row')) return;
      var text = (row.textContent || '').toLowerCase();
      row.style.display = (term === '' || text.indexOf(term) !== -1) ? '' : 'none';
    });
  }

  if (search) search.addEventListener('input', applyFilters);
  if (clear) clear.addEventListener('click', function () {
    if (search) search.value = '';
    applyFilters();
    if (search) search.focus();
  });
})();
