<?php $pageJs = ['booking/detail']; ?>
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
$hasDateConflict = !empty($hasDateConflict);
$hasTicket = $ticket !== null;
$isCreditPayment = ($bookingCredit ?? null) !== null
  || (($invoice ?? null) !== null && $invoice->getStatusInvoice() === 'credito');
$isCashPayment = !$isCreditPayment
  && (($invoice ?? null) !== null && $invoice->getStatusInvoice() === 'pagado');
?>

<div class="page-head">
  <div>
    <h1>Detalle de la reserva</h1>
    <a href="<?= e(base_url($isClient ? 'booking' : 'venue', $isClient ? 'myBookings' : 'list')) ?>">&larr; Volver</a>
  </div>
</div>

<?php if (!empty($_GET['creditError'])): ?>
  <div class="alert alert-error"><?= e($_GET['creditError']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['editError'])): ?>
  <div class="alert alert-error"><?= e($_GET['editError']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Los cambios de la reserva se guardaron correctamente.</div>
<?php endif; ?>

<?php if ($isOwner && !empty($clientIsBlocked)): ?>
  <div class="alert alert-error">Este cliente está bloqueado. La reserva existente se conserva, pero no podrá crear nuevas reservas.</div>
<?php elseif ($isOwner && ($clientOverdueCount ?? 0) > 0): ?>
  <div class="alert alert-error">Atención: este cliente tiene <?= (int) $clientOverdueCount ?> cuota(s) vencida(s) en créditos contigo.</div>
<?php endif; ?>

<div class="card">
  <div class="detail-grid">
<div class="detail-item"><div class="k">Local</div><div class="v">#<?= (int) $booking->getIdLocal() ?> <?= $venue !== null ? '— ' . e($venue->getNameVenue()) : '' ?></div></div>
    <div class="detail-item"><div class="k">Fechas</div>
      <div class="v">
        <?php
          $startLabel = date('d/m/Y', strtotime($booking->getBookingDate()));
          $endLabel = $booking->getBookingEndDate() !== null
            ? ' — ' . date('d/m/Y', strtotime($booking->getBookingEndDate()))
            : '';
        ?>
        <?= e($startLabel . $endLabel) ?>
      </div>
    </div>
    <div class="detail-item"><div class="k">Horario</div><div class="v">
      <?php if (($booking->getRentalBlockId() ?? null) !== null && $rentalBlock !== null): ?>
        <?= e($rentalBlock->getName()) ?>: <?= e($rentalBlock->getStartTime()) ?>–<?= e($rentalBlock->getEndTime()) ?>
      <?php elseif ($venue !== null && $venue->getCheckInTime() !== null && $venue->getCheckOutTime() !== null): ?>
        Entrada <?= e($venue->getCheckInTime()) ?> · salida <?= e($venue->getCheckOutTime()) ?>
      <?php else: ?>
        <span class="muted">Horario general no especificado</span>
      <?php endif; ?>
    </div></div>
    <?php if ($booking->getEventType() !== null): ?>
      <div class="detail-item">
        <div class="k">Tipo de evento</div>
        <div class="v">
          <?php
            $eventTypeLabel = [
              'boda' => 'Boda',
              'cumpleanos' => 'Cumpleaños',
              'empresarial' => 'Empresarial',
              'otro' => 'Otro',
            ][$booking->getEventType()] ?? $booking->getEventType();
          ?>
          <?= e($eventTypeLabel) ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($booking->getAttendees() !== null): ?>
      <div class="detail-item">
        <div class="k">Asistentes</div>
        <div class="v">
          <?= (int) $booking->getAttendees() ?> personas
          <?php if ($venue !== null): ?>
            <span class="muted">
              (mínimo <?= (int) ($venue->getMinCapacity() ?? 1) ?>,
              máximo <?= (int) $venue->getCapacityVenue() ?>)
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($booking->getEventDescription() !== null && $booking->getEventDescription() !== ''): ?>
      <div class="detail-item">
        <div class="k">Detalle del evento</div>
        <div class="v"><?= e($booking->getEventDescription()) ?></div>
      </div>
    <?php endif; ?>
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
    <?php if ($isCreditPayment || $isCashPayment): ?>
      <div class="detail-item">
        <div class="k">Modalidad de pago</div>
        <div class="v">
          <span class="badge <?= $isCreditPayment ? 'info' : 'success' ?>">
            <?= $isCreditPayment ? 'A crédito' : 'De contado' ?>
          </span>
        </div>
      </div>
    <?php endif; ?>

  </div>

<div class="detail-item" style="grid-column:1 / -1;">
  <div class="k">Propietario</div>

  <?php if ($owner === null): ?>
    <div class="v muted">Propietario no encontrado.</div>
  <?php else: ?>
    <div class="v" style="display:flex;align-items:center;flex-wrap:wrap;gap:6px 14px;">
      <span>
        <strong>Propietario:</strong>
        <a href="<?= e(base_url('venue', 'showOwner', [
          'ownerId' => $owner->getIdOwner(),
          'venueId' => $booking->getIdLocal()
        ])) ?>">
          <?= e($owner->getFirstNameOwner()) ?><?= $owner->getLastNameOwner() !== ''
            ? ' ' . e($owner->getLastNameOwner())
            : '' ?>
        </a>
      </span>

      <span><strong>Correo:</strong> <?= e($owner->getEmail()) ?></span>

      <?php if ($owner->getPhoneNumber() !== null && $owner->getPhoneNumber() !== ''): ?>
        <span><strong>Teléfono:</strong> <?= e(format_phone_number($owner->getPhoneNumber())) ?></span>
      <?php endif; ?>

      <a href="<?= e(base_url('venue', 'showOwner', [
        'ownerId' => $owner->getIdOwner(),
        'venueId' => $booking->getIdLocal()
      ])) ?>">
        Ver información del propietario &rarr;
      </a>
    </div>
  <?php endif; ?>
</div>
<div class="detail-item" style="grid-column:1 / -1;">
  <div class="k">Cliente</div>

  <?php if ($client === null): ?>
    <div class="v muted">Cliente no encontrado.</div>
  <?php else: ?>
    <div class="v" style="display:flex;align-items:center;flex-wrap:wrap;gap:6px 14px;">
      <span>
        <strong>Cliente:</strong>
        <?= e($client->getName()) ?>
      </span>

      <span>
        <strong>Correo:</strong>
        <?= e($client->getEmail()) ?>
      </span>

      <?php if ($client->getPhoneNumber() !== null && $client->getPhoneNumber() !== ''): ?>
        <span>
          <strong>Teléfono:</strong>
          <?= e(format_phone_number($client->getPhoneNumber())) ?>
        </span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
  <?php if ($isClient && $isModifiable): ?>
    <hr style="margin:22px 0;border:none;border-top:1px solid var(--neutral-200);">
    <h3 style="margin:0 0 12px;">Editar datos de la reserva</h3>
    <?php if ($hasDateConflict): ?>
      <p role="alert" style="color:#b42318;font-weight:700;margin:0 0 14px;">Fecha no disponible. Cambia las fechas por un rango disponible antes de editar servicios o pagar.</p>
    <?php endif; ?>
    <form method="post" action="<?= e(base_url('booking', 'updateDetails')) ?>"
          style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end;">
      <?= csrf_field() ?>
      <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">

      <div class="form-group" style="margin:0;">
        <label for="editStartDate">Fecha de inicio</label>
        <input class="form-control" type="date" id="editStartDate" name="startDate"
               value="<?= e(date('Y-m-d', strtotime($booking->getBookingDate()))) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label for="editEndDate">Fecha final</label>
        <input class="form-control" type="date" id="editEndDate" name="endDate"
               value="<?= e(date('Y-m-d', strtotime($booking->getBookingEndDate() ?? $booking->getBookingDate()))) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label for="editEventType">Tipo de evento</label>
        <select class="form-control" id="editEventType" name="eventType">
          <option value="">— Selecciona —</option>
          <option value="boda" <?= $booking->getEventType() === 'boda' ? 'selected' : '' ?>>Boda</option>
          <option value="cumpleanos" <?= $booking->getEventType() === 'cumpleanos' ? 'selected' : '' ?>>Cumpleaños</option>
          <option value="empresarial" <?= $booking->getEventType() === 'empresarial' ? 'selected' : '' ?>>Empresarial</option>
          <option value="otro" <?= $booking->getEventType() === 'otro' ? 'selected' : '' ?>>Otro</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label for="editAttendees">Asistentes</label>
        <input class="form-control" type="number" id="editAttendees" name="attendees"
               min="<?= (int) ($venue !== null ? ($venue->getMinCapacity() ?? 1) : 1) ?>"
               max="<?= (int) ($venue !== null ? $venue->getCapacityVenue() : 999999) ?>"
               value="<?= e((string) ($booking->getAttendees() ?? '')) ?>" required>

      </div>
      <?php if ($venue !== null): ?>
          <div class="form-hint">
            Permitido: <?= (int) ($venue->getMinCapacity() ?? 1) ?> a
            <?= (int) $venue->getCapacityVenue() ?> asistentes.
          </div>
        <?php endif; ?>
      <div class="form-group" style="margin:0;grid-column:1/-1;">
        <label for="editEventDetail">Detalle del evento (necesario si eliges «Otro»)</label>
        <textarea class="form-control" id="editEventDetail" name="eventDetail"
                  rows="2" maxlength="255"><?= e($booking->getEventDescription() ?? '') ?></textarea>
      </div>
      <div style="grid-column:1/-1;">
        <button class="btn btn-primary" type="submit">Guardar datos de la reserva</button>
      </div>
    </form>
  <?php endif; ?>

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
                <?php if ($d->getConcept() !== null && $d->getConcept() !== ''): ?>
                  <?= e($d->getConcept()) ?>
                  <?php if ($d->getIdVenue() > 0 && $d->getQuantityDetail() > 1): ?>
                    <span class="muted">(<?= (int) $d->getQuantityDetail() ?> días)</span>
                  <?php endif; ?>
                <?php elseif ($d->getIdVenue() > 0): ?>
                  Renta del local — <?= e($venue !== null ? $venue->getNameVenue() : ('Local #' . $d->getIdVenue())) ?>
                  <?php if ($d->getQuantityDetail() > 1): ?> <span class="muted">(<?= (int) $d->getQuantityDetail() ?> día<?= $d->getQuantityDetail() > 1 ? 's' : '' ?>)</span><?php endif; ?>
                <?php else: ?>
                  Servicio — <?= isset($serviceMap[$d->getIdLocalService()]) ? e($serviceMap[$d->getIdLocalService()]->getNameService()) : ('Servicio #' . $d->getIdLocalService()) ?>
                  <?php if ($d->getDiscount() > 0 && $d->getUnitPrice() > 0): ?>
                    <?php $includedUnits = (int) round($d->getDiscount() / $d->getUnitPrice()); ?>
                    <?php if ($includedUnits > 0): ?>
                      <span class="muted">(<?= $includedUnits ?> incluida<?= $includedUnits === 1 ? '' : 's' ?>)</span>
                    <?php endif; ?>
                  <?php endif; ?>
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
          <?php if ($isOwner): ?>
            <tr>
              <th colspan="3" style="text-align:right;">Comisión (<?= e(number_format($totals['commissionPct'] ?? 0, 2)) ?>%)</th>
              <th>&#8353; <?= number_format($totals['commission'], 2) ?></th>
            </tr>
          <?php endif; ?>
          <tr>
            <th colspan="3" style="text-align:right;">IVA (<?= e(number_format($totals['taxPct'] ?? 0, 2)) ?>%)</th>
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

  <?php if ($isClient && $isModifiable && !$hasDateConflict): ?>
    <!-- Las cantidades se editan desde esta lista. -->
    <?php
      $serviceChoices = [];
      foreach ($availableServices as $availableService) {
        $serviceChoices[$availableService->getIdService()] = $availableService;
      }
      foreach ($serviceMap as $existingService) {
        if (!isset($serviceChoices[$existingService->getIdService()])) {
          $serviceChoices[$existingService->getIdService()] = $existingService;
        }
      }
      $selectedServiceQuantities = [];
      foreach ($details as $serviceLine) {
        if ($serviceLine->getIdLocalService() > 0) {
          $id = $serviceLine->getIdLocalService();
          $selectedServiceQuantities[$id] = ($selectedServiceQuantities[$id] ?? 0)
            + $serviceLine->getQuantityDetail();
        }
      }
    ?>
    <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
    <h3 style="margin:0 0 10px;">Servicios disponibles</h3>
    <p class="muted" style="margin-bottom:14px;">
      Marca los servicios incluidos en tu reserva. Desmarca una casilla para quitarlo;
      usa «Guardar cantidad» para aplicar un cambio de cantidad.
    </p>

    <?php if ($serviceChoices !== []): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:14px;">
        <?php foreach ($serviceChoices as $s): ?>
          <?php
            $serviceId = (int) $s->getIdService();
            $isServiceSelected = isset($selectedServiceQuantities[$serviceId]);
            $quantity = $selectedServiceQuantities[$serviceId] ?? max(1, (int) $s->getMinQuantity());
            $includedQuantity = max(0, (int) $s->getIncludedQuantity());
            $images = $serviceImages[$serviceId] ?? [];
            $serviceImage = $images[0] ?? null;
          ?>
          <form class="card" data-service-selection
                action="<?= e(base_url('booking', 'setServiceSelection')) ?>"
                style="margin:0;padding:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">
            <input type="hidden" name="serviceId" value="<?= $serviceId ?>">

            <div style="display:flex;gap:12px;align-items:flex-start;">
              <?php if ($serviceImage !== null): ?>
                <img src="<?= e(image_url($serviceImage->getPath())) ?>"
                     alt="Foto de <?= e($s->getNameService()) ?>"
                     style="width:88px;height:76px;object-fit:cover;border-radius:8px;flex:none;">
              <?php else: ?>
                <div aria-label="Servicio sin foto"
                     style="width:88px;height:76px;display:grid;place-items:center;background:var(--neutral-100);border-radius:8px;flex:none;">
                  &#128247;
                </div>
              <?php endif; ?>

              <div style="min-width:0;flex:1;">
                <strong><?= e($s->getNameService()) ?></strong>
                <?php if (trim((string) $s->getDescriptionService()) !== ''): ?>
                  <p class="muted" style="margin:4px 0;">
                    <?= nl2br(e($s->getDescriptionService())) ?>
                  </p>
                <?php endif; ?>
                <div class="muted">
                  &#8353; <?= number_format($s->getPriceService(), 2) ?>
                  <?php if ($s->getUnitService() !== ''): ?>
                    por <?= e($s->getUnitService()) ?>
                  <?php endif; ?>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                  <input type="checkbox" name="selected" value="1"
                         data-service-toggle <?= $isServiceSelected ? 'checked' : '' ?>>
                  <span data-service-label><?= $isServiceSelected ? 'Servicio agregado' : 'Agregar servicio' ?></span>
                </label>
              </div>
            </div>

            <div class="form-hint" style="margin-top:10px;">
              <?php if ($includedQuantity > 0): ?>
                Incluye <?= $includedQuantity ?> unidad<?= $includedQuantity === 1 ? '' : 'es' ?>
                en el precio base.
                <span data-included-preview data-included="<?= $includedQuantity ?>"
                      data-quantity="<?= $quantity ?>">
                  <?php if ($isServiceSelected): ?>
                    <?= min($includedQuantity, $quantity) ?> de las <?= $quantity ?> solicitadas
                    están cubiertas por el precio base.
                  <?php endif; ?>
                </span>
              <?php else: ?>
                Este servicio no incluye unidades en el precio base.
              <?php endif; ?>
            </div>

            <div style="display:flex;align-items:flex-end;gap:10px;margin-top:12px;">
              <div class="form-group" style="margin:0;">
                <label for="service-quantity-<?= $serviceId ?>">Cantidad</label>
                <input class="form-control" type="number"
                       id="service-quantity-<?= $serviceId ?>"
                       name="quantity"
                       min="<?= max(1, (int) $s->getMinQuantity()) ?>"
                       <?= $s->getMaxQuantity() !== null
                         ? 'max="' . (int) $s->getMaxQuantity() . '"'
                         : '' ?>
                       value="<?= $quantity ?>"
                       data-service-quantity
                       <?= $isServiceSelected ? '' : 'disabled' ?> required
                       style="width:100px;">
              </div>
              <button class="btn btn-primary" type="submit"
                      data-save-service-quantity
                      style="<?= $isServiceSelected ? '' : 'display:none;' ?>">
                Guardar cantidad
              </button>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted" style="margin-top:16px;">No hay servicios disponibles para agregar.</p>
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
      <div data-pm-warning class="alert alert-error" style="display:none;text-align:left;margin-top:10px;">Debe seleccionar un método de pago</div>

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

  <?php if ($isClient && !$hasDateConflict && isset($creditInfo) && $creditInfo !== null): ?>
    <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
    <h3 style="margin:0 0 10px;">Financiar con crédito</h3>
    <?php if (isset($bookingCredit) && $bookingCredit !== null): ?>
      <div class="alert alert-info" style="text-align:left;">
        <p style="margin:0 0 8px;">
          Esta reserva tiene un crédito por &#8353; <?= number_format($bookingCredit->getTotal(), 2) ?>
          (saldo &#8353; <?= number_format($bookingCredit->getBalance(), 2) ?>,
          vence <?= e($bookingCredit->getDueDate()) ?>).
        </p>
        <a class="btn btn-sm btn-outline" href="<?= e(base_url('credit', 'detail', ['id' => $bookingCredit->getIdCredit()])) ?>">
          Ver crédito y abonar
        </a>
      </div>
    <?php elseif ($isPending && $creditInfo['allowed']): ?>
      <form method="post" action="<?= e(base_url('credit', 'request')) ?>" style="max-width:460px;">
        <?= csrf_field() ?>
        <input type="hidden" name="bookingId" value="<?= (int) $booking->getIdBooking() ?>">
        <p class="form-hint">
          El propietario acepta crédito. Tu reserva quedará confirmada y podrás pagar en abonos
          dentro del plazo acordado.
        </p>
        <button class="btn btn-outline" type="submit">Solicitar crédito</button>
      </form>
    <?php elseif ($isPending): ?>
      <p class="muted" style="margin:0;"><?= e($creditInfo['reason']) ?></p>
    <?php endif; ?>
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

  <?php if ($isClient): ?>
    <?php if ($refundRequest !== null): ?>
      <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
      <h3 style="margin:0 0 10px;">Solicitud de reembolso</h3>
      <?php if ($refundRequest->getState() === 'pendiente'): ?>
        <div class="alert alert-warning" style="text-align:left;">
          Tu solicitud está pendiente de revisión. Motivo: “<?= e($refundRequest->getDetail()) ?>”
        </div>
      <?php elseif ($refundRequest->getState() === 'aprobado'): ?>
        <div class="alert alert-success" style="text-align:left;">Tu solicitud de reembolso fue aprobada.</div>
      <?php else: ?>
        <div class="alert alert-error" style="text-align:left;">Tu solicitud de reembolso fue rechazada.</div>
      <?php endif; ?>
    <?php elseif ($hasTicket && !in_array($booking->getBookingState(), ['cancelado', 'rechazado'], true)): ?>
      <hr style="margin:24px 0;border:none;border-top:1px solid var(--neutral-200);">
      <h3 style="margin:0 0 10px;">Solicitar reembolso</h3>
      <form method="post" action="<?= e(base_url('booking', 'requestRefund')) ?>"
            style="max-width:460px;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $booking->getIdBooking() ?>">
        <div class="form-group">
          <label for="motivoRefund">Motivo (mínimo 10 caracteres) <span class="required-mark">*</span></label>
          <textarea class="form-control" id="motivoRefund" name="motivo" rows="3" minlength="10" required></textarea>
        </div>
        <button class="btn btn-outline" type="submit">Enviar solicitud de reembolso</button>
      </form>
    <?php endif; ?>
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

  <?php if ($isOwner && $ticket !== null && $ticket->getState() === 'pendiente' && $booking->getBookingState() === 'pendiente'): ?>
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

<?php require_once __DIR__ . '/../_footer.php'; ?>
