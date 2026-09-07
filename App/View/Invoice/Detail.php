<?php require_once __DIR__ . '/../_header.php';
if ($invoice === null) {
  echo '<div class="alert alert-error">Factura no encontrada.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1>Factura #<?= (int) $invoice->getIdInvoice() ?></h1>
    <a href="<?= e(base_url('invoice', 'list')) ?>">&larr; Volver a mis facturas</a>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="detail-item"><div class="k">Reserva</div><div class="v">#<?= (int) $invoice->getIdClientBooking() ?></div></div>
    <div class="detail-item"><div class="k">Fecha de emisión</div><div class="v"><?= e(date('d/m/Y', strtotime($invoice->getDateInvoice()))) ?></div></div>
    <div class="detail-item"><div class="k">Método de pago</div><div class="v"><?= $paymentMethod !== null ? e($paymentMethod->getPaymentMethod()) : ('#' . (int) $invoice->getIdPaymentMethod()) ?></div></div>
    <div class="detail-item"><div class="k">Estado</div>
      <div class="v" style="margin-top:6px;">
        <?php
          $badge = [
            'pagada' => 'success',
            'pendiente' => 'warning',
            'anulada' => 'neutral',
          ][$invoice->getStatusInvoice()] ?? 'neutral';
        ?>
        <span class="badge <?= $badge ?>"><?= e($invoice->getStatusInvoice()) ?></span>
      </div>
    </div>
  </div>

  <h3 style="margin:18px 0 12px;">Detalle</h3>
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
                Renta del local — <?= $venue !== null ? e($venue->getNameVenue()) : ('Local #' . $d->getIdVenue()) ?>
              <?php else: ?>
                Servicio — <?= isset($serviceMap[$d->getIdLocalService()]) ? e($serviceMap[$d->getIdLocalService()]->getNameService()) : ('Servicio #' . $d->getIdLocalService()) ?>
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
</div>

<div class="grid grid-2" style="margin-top:20px;">
  <div class="card">
    <h3 class="card-title">Cliente</h3>
    <?php if ($client === null): ?>
      <p class="muted">Datos del cliente no encontrados.</p>
    <?php else: ?>
      <div class="detail-item"><div class="k">Nombre</div><div class="v"><strong><?= e($client->getName()) ?></strong></div></div>
      <div class="detail-item"><div class="k">Correo</div><div class="v"><?= e($client->getEmail()) ?></div></div>
      <div class="detail-item"><div class="k">Teléfono</div><div class="v"><?= $client->getPhoneNumber() !== null && $client->getPhoneNumber() !== '' ? e($client->getPhoneNumber()) : '—' ?></div></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Local</h3>
    <?php if ($venue === null): ?>
      <p class="muted">Datos del local no encontrados.</p>
    <?php else: ?>
      <div class="detail-item"><div class="k">Nombre</div><div class="v"><strong><?= e($venue->getNameVenue()) ?></strong></div></div>
      <div class="detail-item"><div class="k">Tipo</div><div class="v"><?= $venue->getTypeVenue() !== '' ? e($venue->getTypeVenue()) : '—' ?></div></div>
      <div class="detail-item"><div class="k">Capacidad</div><div class="v"><?= (int) $venue->getCapacityVenue() ?> personas</div></div>
      <div class="detail-item"><div class="k">Precio de renta</div><div class="v">&#8353; <?= number_format($venue->getPriceVenue(), 2) ?></div></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>