<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Mis locales</h1>
    <p class="muted">Administra los locales de tu negocio</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
</div>

<div class="card" style="margin-bottom:18px;padding:16px;">
  <div class="actions" style="flex-wrap:wrap">
    <input class="form-control" type="search" id="venue-search"
           placeholder="Buscar por nombre del local..."
           style="max-width:320px;flex:1 1 220px;">
    <select class="form-control" id="venue-state" style="max-width:160px;">
      <option value="">Todos los estados</option>
      <option value="activo">Activo</option>
      <option value="inactivo">Inactivo</option>
    </select>
    <button class="btn btn-ghost" type="button" id="venue-clear">Limpiar filtros</button>
  </div>
</div>

<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    Aún no tienes locales registrados. Crea tu primer local.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table venue-table">
      <thead>
        <tr>
          <th>Local</th>
          <th>Tipo</th>
          <th>Capacidad</th>
          <th>Estado</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($venues as $v): ?>
          <tr>
            <td><strong><?= e($v->getNameVenue()) ?></strong></td>
            <td><?= $v->getTypeVenue() !== '' ? e($v->getTypeVenue()) : 'General' ?></td>
            <td><?= (int) $v->getCapacityVenue() ?></td>
            <td>
              <?= $v->getIsActive() ? '<span class="badge success">Activo</span>' : '<span class="badge neutral">Inactivo</span>' ?>
            </td>
            <td>
              <div class="actions">
                <a class="btn btn-sm btn-outline" href="<?= e(base_url('venue', 'showForm', ['id' => $v->getIdVenue()])) ?>">Editar</a>
                <a class="btn btn-sm btn-accent" href="<?= e(base_url('service', 'list', ['venueId' => $v->getIdVenue()])) ?>">Servicios</a>
                <a class="btn btn-sm btn-primary" href="<?= e(base_url('booking', 'venueBookings', ['venueId' => $v->getIdVenue()])) ?>">Reservas</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
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
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
