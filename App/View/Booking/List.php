<?php require_once __DIR__ . '/../_header.php';
$isOwnerView = current_user_type() === 'owner';
?>

<div class="page-head">
  <div>
    <h1><?= $isOwnerView ? 'Reservas del local' : 'Mis reservas' ?></h1>
    <?php if (!$isOwnerView): ?>
      <p class="muted">Consulta el estado de tus reservas</p>
    <?php endif; ?>
  </div>
  <?php if (!$isOwnerView): ?>
    <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">+ Nueva reserva</a>
  <?php endif; ?>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:18px;padding:16px;">
  <div class="actions" style="flex-wrap:wrap">
    <input class="form-control" type="search" id="booking-search"
           placeholder="Buscar por <?= $isOwnerView ? 'cliente, ' : '' ?>local o fecha..."
           style="max-width:340px;flex:1 1 240px;">
    <select class="form-control" id="booking-state" style="max-width:150px;">
      <option value="">Estado: todos</option>
      <option value="pendiente">Pendiente</option>
      <option value="confirmado">Confirmado</option>
      <option value="cancelado">Cancelado</option>
      <option value="rechazado">Rechazado</option>
    </select>
    <select class="form-control" id="booking-ticket" style="max-width:190px;">
      <option value="">Comprobante: todos</option>
      <option value="sí">Con comprobante</option>
      <option value="no">Sin comprobante</option>
    </select>
    <button class="btn btn-ghost" type="button" id="booking-clear">Limpiar filtros</button>
  </div>
</div>

<?php if (empty($bookings)): ?>
  <div class="card empty">
    <span class="emoji">&#128197;</span>
    <?= $isOwnerView ? 'Todavía no hay reservas para este local.' : 'Aún no has hecho reservas.' ?>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table booking-table">
      <thead>
        <tr>
          <?php if ($isOwnerView): ?><th>Cliente</th><?php endif; ?>
          <th>Local</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Comprobante</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <?php if ($isOwnerView): ?><td><?= e($clientNames[$b->getIdBooking()] ?? '#' . (int) $b->getIdClient()) ?></td><?php endif; ?>
            <td><strong><?= e($venueNames[$b->getIdBooking()] ?? 'Local #' . (int) $b->getIdLocal()) ?></strong></td>
            <td><?= e(date('d/m/Y', strtotime($b->getBookingDate()))) ?></td>
            <td>
              <?php
                $badge = [
                  'pendiente' => 'warning',
                  'confirmado' => 'success',
                  'cancelado' => 'neutral',
                  'rechazado' => 'danger',
                ][$b->getBookingState()] ?? 'neutral';
              ?>
              <span class="badge <?= $badge ?>"><?= e($b->getBookingState()) ?></span>
            </td>
            <td><?= ($hasTicket[$b->getIdBooking()] ?? false) ? 'Sí' : 'No' ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="<?= e(base_url('booking', 'detail', ['id' => $b->getIdBooking()])) ?>">Ver detalle</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
  (function () {
    var table = document.querySelector('.booking-table');
    if (!table) return;

    // Índices de columna (1-based) según el tipo de vista.
    // Owner: [Cliente][Local][Fecha][Estado][Comprobante]
    // Client:        [Local][Fecha][Estado][Comprobante]
    var stateCol = <?= $isOwnerView ? 4 : 3 ?>;
    var ticketCol = <?= $isOwnerView ? 5 : 4 ?>;

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
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
