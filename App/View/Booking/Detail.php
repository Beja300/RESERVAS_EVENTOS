<?php require_once __DIR__ . '/../_header.php';
if ($booking === null) {
  echo '<div class="alert alert-error">Reserva no encontrada.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
$isClient = current_user_type() === 'client';
$isOwner  = current_user_type() === 'owner';
$isPending = $booking->getBookingState() === 'pendiente';
$isModifiable = $isPending && $ticket === null;
$hasTicket = $ticket !== null;
?>

<div class="page-head">
  <div>
    <h1>Detalle de la reserva</h1>
    <a href="<?= e(base_url($isClient ? 'booking' : 'venue', $isClient ? 'myBookings' : 'list')) ?>">&larr; Volver</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="detail-item"><div class="k">Local</div><div class="v">#<?= (int) $booking->getIdLocal() ?> <?= $venue !== null ? '— ' . e($venue->getNameVenue()) : '' ?></div></div>
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

  <h3 style="margin:18px 0 12px;">Detalle de la reserva</h3>
  <?php if (empty($details)): ?>
    <p class="muted">Esta reserva no tiene líneas de detalle.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Concepto</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $d): ?>
            <tr>
              <td>
                <?php if ($d->getIdVenue() > 0): ?>
                  Renta del local — <?= e($venue !== null ? $venue->getNameVenue() : ('Local #' . $d->getIdVenue())) ?>
                <?php else: ?>
                  Servicio #<?= (int) $d->getIdLocalService() ?>
                <?php endif; ?>
              </td>
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

  <?php if ($isClient && $isModifiable): ?>
    <!-- AGREGAR SERVICIOS (AJAX): solo mientras la reserva está pendiente
         y aún no se ha subido el comprobante. -->
    <?php if (!empty($availableServices)): ?>
      <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
      <h3 style="margin:0 0 10px;">Agregar servicios</h3>
      <form class="form-inline" data-add-service
            data-venue-booking="<?= (int) $booking->getIdBooking() ?>"
            style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <?= csrf_field() ?>
        <div class="form-group" style="min-width:220px;">
          <label for="serviceId">Servicio</label>
          <select class="form-control" id="serviceId" name="serviceId" required>
            <?php foreach ($availableServices as $s): ?>
              <option value="<?= (int) $s->getIdService() ?>">
                <?= e($s->getNameService()) ?> (&#8353; <?= number_format($s->getPriceService(), 2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="qty">Cantidad</label>
          <input class="form-control" type="number" id="qty" name="quantity" min="1" value="1" style="width:90px;">
        </div>
        <button class="btn btn-primary" type="submit">Agregar</button>
      </form>
    <?php else: ?>
      <p class="muted" style="margin-top:16px;">No hay servicios disponibles adicionales para agregar.</p>
    <?php endif; ?>

    <!-- PAGO: seleccionar método -> ver datos de cobro -> subir comprobante (AJAX). -->
    <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
    <h3 style="margin:0 0 10px;">Pagar esta reserva</h3>
    <div data-payment-flow
         data-booking-id="<?= (int) $booking->getIdBooking() ?>"
         data-owner-payments='<?= e(json_encode($ownerPaymentMethods, JSON_UNESCAPED_UNICODE)) ?>'>
      <div class="form-group">
        <label for="pmSelect">Método de pago</label>
        <select class="form-control" id="pmSelect" data-pm-select>
          <option value="">— Selecciona un método —</option>
          <?php foreach ($ownerPaymentMethods as $op): ?>
            <option value="<?= (int) $op['idPaymentMethod'] ?>"><?= e($op['paymentMethod']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div data-pm-info class="alert alert-info" style="display:none;text-align:left;margin-top:10px;"></div>

      <form data-upload-ticket enctype="multipart/form-data"
            style="<?= empty($ownerPaymentMethods) ? 'opacity:.5;pointer-events:none;' : '' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">
        <input type="hidden" name="paymentMethodId" data-pm-hidden value="">
        <div class="form-group">
          <label for="ticket">Comprobante (PNG / JPG / PDF)</label>
          <input class="form-control" type="file" id="ticket" name="ticket"
                 accept=".png,.jpg,.jpeg,.pdf" data-ticket-input>
        </div>
        <p class="form-hint" style="margin:8px 0 12px;">
          Selecciona primero el método de pago; al subir el comprobante se genera tu factura
          y ya no podrás agregar más servicios.
        </p>
        <button class="btn btn-outline" type="submit" data-upload-btn disabled>Subir comprobante</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isClient && $isPending && !$hasTicket): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px;">
      <form method="post" action="<?= e(base_url('booking', 'cancel')) ?>"
            data-booking-cancel data-ajax-cancel>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <button class="btn btn-danger" type="submit">Cancelar reserva</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($hasTicket): ?>
    <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
    <h3 style="margin:0 0 10px;">Comprobante de pago</h3>
    <?php
      $statusBadge = [
        'pendiente' => 'warning',
        'aprobado'  => 'success',
        'rechazado' => 'danger',
      ][$ticket->getState()] ?? 'neutral';
    ?>
    <div class="alert alert-info" style="text-align:left;">
      <p style="margin-bottom:8px;">
        Comprobante <span class="badge <?= $statusBadge ?>"><?= e($ticket->getState()) ?></span>
        &nbsp;(<?= strtoupper(e($ticket->getType())) ?>)
      </p>
      <?php
        $pmName = null;
        foreach ($paymentMethods as $pm) {
          if ($pm->getIdPaymentMethod() === $ticket->getPaymentMethodId()) {
            $pmName = $pm->getPaymentMethod();
            break;
          }
        }
      ?>
      <?php if ($pmName !== null): ?>
        <p class="muted" style="margin:0 0 8px;">
          Método de pago: <strong><?= e($pmName) ?></strong>
        </p>
      <?php endif; ?>
      <a class="btn btn-sm btn-ghost" href="<?= e(image_url($ticket->getFile())) ?>" target="_blank" rel="noopener">
        &#128065; Ver comprobante
      </a>
    </div>
    <p class="form-hint">Factura generada el día del pago.</p>
  <?php endif; ?>

  <?php if ($isOwner && $ticket !== null && $ticket->getState() === 'pendiente'): ?>
    <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">

    <form method="post" action="<?= e(base_url('booking', 'approveTicket')) ?>"
          data-ajax-approve data-booking-id="<?= (int) $booking->getIdBooking() ?>"
          style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <?= csrf_field() ?>
      <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">
      <button class="btn btn-success" type="submit">Aprobar comprobante</button>
    </form>
    <form method="post" action="<?= e(base_url('booking', 'rejectTicket')) ?>"
          data-ajax-reject data-booking-id="<?= (int) $booking->getIdBooking() ?>"
          style="margin-top:10px;" data-confirm="¿Rechazar el comprobante de esta reserva?">
      <?= csrf_field() ?>
      <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">
      <button class="btn btn-danger btn-sm" type="submit">Rechazar comprobante</button>
    </form>
  <?php endif; ?>
</div>

<script src="<?= e(js_url('booking-detail')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>
