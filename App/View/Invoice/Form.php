<?php require_once __DIR__ . '/../_header.php';
if ($booking === null) {
  echo '<div class="alert alert-error">Reserva no encontrada.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1>Generar pago</h1>
    <a href="<?= e(base_url('booking', 'detail', ['id' => $booking->getIdBooking()])) ?>">&larr; Volver a la reserva</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <div class="detail-grid" style="margin-bottom:20px;">
    <div class="detail-item"><div class="k">Reserva</div><div class="v">#<?= (int) $booking->getIdBooking() ?></div></div>
    <div class="detail-item"><div class="k">Fecha</div><div class="v"><?= e(date('d/m/Y', strtotime($booking->getBookingDate()))) ?></div></div>
    <div class="detail-item"><div class="k">Total a pagar</div><div class="v"><strong>&#8353; <?= number_format($total, 2) ?></strong></div></div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('invoice', 'generate')) ?>">
    <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">

    <div class="form-group">
      <label for="paymentMethodId">Método de pago *</label>
      <?php if (empty($paymentMethods)): ?>
        <p class="muted">No tienes métodos de pago registrados.</p>
      <?php else: ?>
        <select class="form-control" id="paymentMethodId" name="paymentMethodId" required>
          <option value="">— Selecciona —</option>
          <?php foreach ($paymentMethods as $pm): ?>
            <option value="<?= (int) $pm->getIdPaymentMethod() ?>"><?= e($pm->getPaymentMethod()) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">¿No encuentras tu método? <a href="<?= e(base_url('paymentMethod', 'showForm')) ?>">Agrega uno nuevo</a>.</p>
      <?php endif; ?>
    </div>

    <button class="btn btn-primary btn-block" type="submit" <?= empty($paymentMethods) ? 'disabled' : '' ?>>Confirmar y generar factura</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
