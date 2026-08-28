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

<?php if (empty($bookings)): ?>
  <div class="card empty">
    <span class="emoji">&#128197;</span>
    <?= $isOwnerView ? 'Todavía no hay reservas para este local.' : 'Aún no has hecho reservas.' ?>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <?php if ($isOwnerView): ?><th>Cliente</th><?php endif; ?>
          <th>Local</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <?php if ($isOwnerView): ?><td>#<?= (int) $b->getIdClient() ?></td><?php endif; ?>
            <td><strong>Local #<?= (int) $b->getIdLocal() ?></strong></td>
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
