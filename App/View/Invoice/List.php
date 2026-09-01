<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Mis facturas</h1>
    <p class="muted">Historial de pagos realizados</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">Nueva reserva</a>
</div>

<?php if (empty($invoices)): ?>
  <div class="card empty">
    <span class="emoji">&#128201;</span>
    Aún no tienes facturas generadas.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Reserva</th>
          <th>Fecha</th>
          <th>Método de pago</th>
          <th>Estado</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td>#<?= (int) $inv->getIdInvoice() ?></td>
            <td>#<?= (int) $inv->getIdClientBooking() ?></td>
            <td><?= e(date('d/m/Y', strtotime($inv->getDateInvoice()))) ?></td>
            <td>#<?= (int) $inv->getIdPaymentMethod() ?></td>
            <td>
              <?php
                $badge = ['pagada' => 'success', 'pendiente' => 'warning', 'anulada' => 'neutral'][$inv->getStatusInvoice()] ?? 'neutral';
              ?>
              <span class="badge <?= $badge ?>"><?= e($inv->getStatusInvoice()) ?></span>
            </td>
            <td>
              <a class="btn btn-sm btn-primary" href="<?= e(base_url('invoice', 'detail', ['bookingId' => $inv->getIdClientBooking()])) ?>">Ver factura</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
