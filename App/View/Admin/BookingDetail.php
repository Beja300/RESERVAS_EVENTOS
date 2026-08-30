<?php require_once __DIR__ . '/../_header.php'; ?>

<?php if (isset($_GET['msg'])): ?>
  <?php $msgMap = [
    'cancelled' => 'La reserva fue cancelada.',
    'rescheduled' => 'La reserva fue reprogramada.',
    'venue_changed' => 'El local fue cambiado.',
    'refunded' => 'Reembolso aprobado y procesado.',
    'refund_rejected' => 'La solicitud de reembolso fue rechazada.',
  ]; ?>
  <?php if (isset($msgMap[$_GET['msg']])): ?>
    <div class="alert alert-success"><?= e($msgMap[$_GET['msg']]) ?></div>
  <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>

<div class="page-head">
  <div>
    <h1>Reserva #<?= (int) $booking->getIdBooking() ?></h1>
  </div>
  <div>
    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('admin', 'bookings')) ?>">&larr; Volver</a>
  </div>
</div>

<div class="card">
  <div class="grid-2">
    <div class="detail-item"><div class="k">Cliente</div>
      <div class="v"><?= e($client ? $client->getName() : '—') ?>
        <?= $client ? '(' . e($client->getEmail()) . ')' : '' ?></div>
    </div>
    <div class="detail-item"><div class="k">Local</div>
      <div class="v">#<?= (int) $booking->getIdLocal() ?>
        <?= $venue !== null ? '— ' . e($venue->getNameVenue()) : '' ?></div>
    </div>
    <div class="detail-item"><div class="k">Fecha</div>
      <div class="v"><?= e(date('d/m/Y', strtotime($booking->getBookingDate()))) ?></div>
    </div>
    <div class="detail-item"><div class="k">Estado</div>
      <div class="v">
        <?php $badge = ['pendiente' => 'warning', 'confirmado' => 'success', 'cancelado' => 'neutral', 'rechazado' => 'danger'][$booking->getBookingState()] ?? 'neutral'; ?>
        <span class="badge <?= $badge ?>"><?= e($booking->getBookingState()) ?></span>
      </div>
    </div>
  </div>
</div>

<?php if ($invoice !== null): ?>
  <div class="card">
    <h2 style="font-size:1.05rem;margin-bottom:10px;">Pago / Factura #<?= (int) $invoice->getIdInvoice() ?></h2>
    <div class="grid-2">
      <div class="detail-item"><div class="k">Estado factura</div>
        <div class="v"><?= e($invoice->getStatusInvoice()) ?></div>
      </div>
      <div class="detail-item"><div class="k">Fecha</div>
        <div class="v"><?= e(date('d/m/Y', strtotime($invoice->getDateInvoice()))) ?></div>
      </div>
    </div>
    <?php if ($ticket !== null): ?>
      <p class="muted" style="margin-top:8px;">Comprobante: <?= e($ticket->getType()) ?> (<?= e($ticket->getState()) ?>)</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <h2 style="font-size:1.05rem;margin-bottom:10px;">Detalle de la reserva</h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Ítem</th>
          <th>Cant.</th>
          <th>Precio</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lines as $d): ?>
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
        <tr><th colspan="3" style="text-align:right;">Subtotal</th>
          <th>&#8353; <?= number_format($totals['subtotal'] ?? 0, 2) ?></th></tr>
        <tr><th colspan="3" style="text-align:right;">Comisión</th>
          <th>&#8353; <?= number_format($totals['commission'] ?? 0, 2) ?></th></tr>
        <tr><th colspan="3" style="text-align:right;">Impuesto</th>
          <th>&#8353; <?= number_format($totals['tax'] ?? 0, 2) ?></th></tr>
        <tr><th colspan="3" style="text-align:right;">Total</th>
          <th>&#8353; <?= number_format($totals['total'] ?? 0, 2) ?></th></tr>
      </tfoot>
    </table>
  </div>
  <?php if ($earning !== null): ?>
    <p class="muted" style="margin-top:8px;">Ganancia registrada: &#8353; <?= number_format($earning->getTotal(), 2) ?></p>
  <?php endif; ?>
</div>

<?php if ($refundRequest !== null): ?>
  <div class="card" style="border-color:var(--warning);">
    <h2 style="font-size:1.05rem;color:var(--warning);margin-bottom:10px;">Solicitud de reembolso (<?= e($refundRequest->getState()) ?>)</h2>
    <p><strong>Motivo del cliente:</strong> <?= e($refundRequest->getDetail()) ?></p>
    <?php if ($refundRequest->getState() === 'pendiente'): ?>
      <div class="actions" style="margin-top:12px;">
        <form method="post" action="<?= e(base_url('admin', 'refundBooking')) ?>"
              data-confirm="¿Aprobar el reembolso? Se cancelará la reserva y se revertirá la ganancia.">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
          <input type="hidden" name="refundId" value="<?= (int) $refundRequest->getId() ?>">
          <input class="form-control" style="max-width:300px;margin-bottom:8px;"
                 type="text" name="note" placeholder="Nota (opcional)">
          <button class="btn btn-sm btn-success" type="submit">Aprobar reembolso</button>
        </form>
        <form method="post" action="<?= e(base_url('admin', 'rejectRefundBooking')) ?>"
              data-confirm="¿Rechazar la solicitud de reembolso?">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
          <input type="hidden" name="refundId" value="<?= (int) $refundRequest->getId() ?>">
          <button class="btn btn-sm btn-danger" type="submit">Rechazar</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!in_array($booking->getBookingState(), ['cancelado', 'rechazado'], true)): ?>
  <div class="card">
    <h2 style="font-size:1.05rem;margin-bottom:10px;">Acciones del administrador</h2>

    <details style="margin-bottom:14px;">
      <summary class="muted" style="cursor:pointer;">Reprogramar fecha</summary>
      <form method="post" action="<?= e(base_url('admin', 'rescheduleBooking')) ?>" style="margin-top:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <div class="form-group" style="max-width:260px;">
          <input class="form-control" type="date" name="date"
                 value="<?= e($booking->getBookingDate()) ?>"
                 min="<?= e(date('Y-m-d')) ?>"
                 required
                 data-booked-dates='<?= e(json_encode($bookedDates)) ?>'>
          <p class="form-hint">No se puede elegir una fecha ya ocupada por otra reserva.</p>
        </div>
        <div class="form-group" style="max-width:300px;">
          <input class="form-control" type="text" name="note" placeholder="Nota (opcional)">
        </div>
        <button class="btn btn-sm btn-primary" type="submit">Reprogramar</button>
      </form>
    </details>

    <details style="margin-bottom:14px;">
      <summary class="muted" style="cursor:pointer;">Cambiar local</summary>
      <form method="post" action="<?= e(base_url('admin', 'changeBookingVenue')) ?>" style="margin-top:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <div class="form-group" style="max-width:300px;">
          <select class="form-control" name="venueId" required>
            <option value="">Seleccionar local...</option>
            <?php foreach ($venues as $v): ?>
              <option value="<?= (int) $v->getIdVenue() ?>" <?= $v->getIdVenue() === $booking->getIdLocal() ? 'disabled' : '' ?>>
                <?= e($v->getNameVenue()) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="max-width:300px;">
          <input class="form-control" type="text" name="note" placeholder="Nota (opcional)">
        </div>
        <button class="btn btn-sm btn-primary" type="submit">Cambiar local</button>
      </form>
    </details>

    <form method="post" action="<?= e(base_url('admin', 'cancelBooking')) ?>"
          data-confirm="¿Cancelar esta reserva?">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
      <div class="form-group" style="max-width:300px;">
        <input class="form-control" type="text" name="note" placeholder="Motivo (opcional)">
      </div>
      <button class="btn btn-sm btn-danger" type="submit">Cancelar reserva</button>
    </form>
  </div>
<?php endif; ?>

<?php if (!empty($history)): ?>
  <div class="card">
    <h2 style="font-size:1.05rem;margin-bottom:10px;">Historial de esta reserva</h2>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Responsable</th>
            <th>Acción</th>
            <th>Detalle</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= e($h['responsibleName'] ?? '—') ?></td>
              <td><span class="badge neutral"><?= e($h['tbbookinghistoryaction']) ?></span></td>
              <td><?= e($h['tbbookinghistorydetail'] ?? '—') ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($h['tbbookinghistorydate']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>

<script>
(function () {
  var input = document.querySelector('input[type="date"][data-booked-dates]');
  if (!input) { return; }
  var current = <?= json_encode($booking->getBookingDate()) ?>;
  var booked = (input.getAttribute('data-booked-dates') || '').trim();
  var dates = booked ? JSON.parse(booked) : [];
  var blocked = dates.filter(function (d) { return d !== current; });

  input.addEventListener('input', function () {
    var val = input.value;
    if (blocked.indexOf(val) !== -1) {
      input.setCustomValidity('Esa fecha ya está ocupada por otra reserva. Elige otra fecha.');
    } else {
      input.setCustomValidity('');
    }
  });
})();
</script>

