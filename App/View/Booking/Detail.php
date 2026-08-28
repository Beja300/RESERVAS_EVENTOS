<?php require_once __DIR__ . '/../_header.php';
if ($booking === null) {
  echo '<div class="alert alert-error">Reserva no encontrada.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
$isClient = current_user_type() === 'client';
$isPending = $booking->getBookingState() === 'pendiente';
?>

<div class="page-head">
  <div>
    <h1>Detalle de la reserva</h1>
    <a href="<?= e(base_url($isClient ? 'booking' : 'venue', $isClient ? 'myBookings' : 'list')) ?>">&larr; Volver</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="detail-item"><div class="k">Local</div><div class="v">#<?= (int) $booking->getIdLocal() ?></div></div>
    <div class="detail-item"><div class="k">Fecha</div><div class="v"><?= e(date('d/m/Y', strtotime($booking->getBookingDate()))) ?></div></div>
    <div class="detail-item"><div class="k">Estado</div>
      <div class="v" style="margin-top:6px;">
        <?php
          $badge = [
            'pendiente' => 'warning',
            'confirmado' => 'success',
            'cancelado' => 'neutral',
            'rechazado' => 'danger',
          ][$booking->getBookingState()] ?? 'neutral';
        ?>
        <span class="badge <?= $badge ?>"><?= e($booking->getBookingState()) ?></span>
      </div>
    </div>
  </div>

  <h3 style="margin:18px 0 12px;">Servicios incluidos</h3>
  <?php if (empty($details)): ?>
    <p class="muted">Aún no se han agregado servicios a esta reserva.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $d): ?>
            <tr>
              <td>Servicio #<?= (int) $d->getIdLocalService() ?></td>
              <td><?= (int) $d->getQuantityDetail() ?></td>
              <td>&#8353; <?= number_format($d->getUnitPrice(), 2) ?></td>
              <td>&#8353; <?= number_format($d->getQuantityDetail() * $d->getUnitPrice() - $d->getDiscount(), 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" style="text-align:right;">Subtotal</th>
            <th>&#8353; <?= number_format($totals['subtotal'], 2) ?></th>
          </tr>
          <tr>
            <th colspan="3" style="text-align:right;">Comisión (5%)</th>
            <th>&#8353; <?= number_format($totals['commission'], 2) ?></th>
          </tr>
          <tr>
            <th colspan="3" style="text-align:right;">IVA (13%)</th>
            <th>&#8353; <?= number_format($totals['tax'], 2) ?></th>
          </tr>
          <tr>
            <th colspan="3" style="text-align:right;">Total a pagar</th>
            <th>&#8353; <?= number_format($total, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($isClient && $isPending): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px;">
      <form method="post" action="<?= e(base_url('booking', 'confirm')) ?>">
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <button class="btn btn-success" type="submit">Confirmar reserva</button>
      </form>

      <form method="post" action="<?= e(base_url('booking', 'cancel')) ?>"
            data-booking-cancel>
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <button class="btn btn-danger" type="submit">Cancelar reserva</button>
      </form>

      <a class="btn btn-primary" href="<?= e(base_url('invoice', 'showForm', ['bookingId' => $booking->getIdBooking()])) ?>">
        Generar pago
      </a>
    </div>
  <?php endif; ?>
</div>

<script src="<?= e(js_url('booking-detail')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>
