<?php $pageJs = ['booking/list']; ?>
<?php require_once __DIR__ . '/../_header.php';
$isOwnerView = current_user_type() === 'owner';
$isPendingBookings = $isPendingBookings ?? false;
?>

<div class="page-head">
  <div>
    <h1><?= $isOwnerView ? ($isPendingBookings ? 'Reservas pendientes' : 'Reservas del local') : 'Mis reservas' ?></h1>
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
    <?= $isPendingBookings ? 'No tienes reservas pendientes por aprobar.'
       : ($isOwnerView ? 'Todavía no hay reservas para este local.' : 'Aún no has hecho reservas.') ?>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table booking-table" data-state-col="<?= $isOwnerView ? 4 : 3 ?>" data-ticket-col="<?= $isOwnerView ? 5 : 4 ?>">
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

<?php require_once __DIR__ . '/../_footer.php'; ?>
