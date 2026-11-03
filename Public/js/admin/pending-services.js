(function () {
  function base() { var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/'); }

  var filterGlobal = document.getElementById('filter-global');
  var filterState = document.getElementById('filter-history-state');
  var tablePending = document.getElementById('table-pending');
  var tableHistory = document.getElementById('table-history');

  function applyFilters() {
    var term = (filterGlobal ? filterGlobal.value : '').toLowerCase();
    var stateTerm = filterState ? filterState.value : '';

    if (tablePending) {
      tablePending.querySelectorAll('tbody tr').forEach(function (row) {
        var text = (row.textContent || '').toLowerCase();
        row.style.display = term === '' ? '' : (text.indexOf(term) !== -1 ? '' : 'none');
      });
    }

    if (tableHistory) {
      tableHistory.querySelectorAll('tbody tr').forEach(function (row) {
        var stateCell = row.querySelector('td:nth-child(5)');
        var state = stateCell ? (stateCell.textContent || '').trim().toLowerCase() : '';
        var matchesState = stateTerm === '' ? true : state === stateTerm;
        var text = (row.textContent || '').toLowerCase();
        var matchesText = term === '' ? true : text.indexOf(term) !== -1;
        row.style.display = (matchesState && matchesText) ? '' : 'none';
      });
    }
  }

  if (filterGlobal) filterGlobal.addEventListener('input', applyFilters);
  if (filterState) filterState.addEventListener('change', applyFilters);

  // ---- Acciones AJAX (aprobar / rechazar) ----
  document.querySelectorAll('form[data-ajax-service-action]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form)
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (r) {
          window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
          if (r.ok) {
            setTimeout(function () { window.location.reload(); }, 500);
          }
        });
    });
  });
})();