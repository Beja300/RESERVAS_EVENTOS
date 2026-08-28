<?php require_once __DIR__ . '/../_header.php'; ?>

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
    <div class="detail-item"><div class="k">Método de pago</div><div class="v">#<?= (int) $invoice->getIdPaymentMethod() ?></div></div>
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
          <th colspan="3" style="text-align:right;">Total</th>
          <th>&#8353; <?= number_format($total, 2) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
