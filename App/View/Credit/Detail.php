<?php
require_once __DIR__ . '/../_header.php';
$stateLabels = [
  'pendiente' => ['Pendiente', 'warning'],
  'parcial'   => ['Abono parcial', 'info'],
  'pagado'    => ['Pagado', 'success'],
  'vencido'   => ['Vencido', 'danger'],
];
[$label, $tone] = $stateLabels[$effectiveState] ?? [$effectiveState, 'info'];
$isClientOwner = current_user_type() === 'client'
  && (int) $_SESSION['user']->getIdClient() === $credit->getIdClient();
$canPay = !empty($clientCanPay) && $isClientOwner;
?>

<div class="page-head">
  <div>
    <h1>Crédito #<?= (int) $credit->getIdCredit() ?></h1>
    <p class="muted">Reserva #<?= (int) $credit->getIdBooking() ?></p>
  </div>
  <span class="badge <?= e($tone) ?>"><?= e($label) ?></span>
</div>

<?php if (!empty($_GET['created'])): ?>
  <div class="alert alert-success">Crédito otorgado. La reserva quedó confirmada.</div>
<?php endif; ?>
<?php if (!empty($_GET['submitted'])): ?>
  <div class="alert alert-success">Abono enviado, pendiente de verificación.</div>
<?php endif; ?>
<?php if (!empty($_GET['approved'])): ?>
  <div class="alert alert-success">Comprobante aprobado y saldo actualizado.</div>
<?php endif; ?>
<?php if (!empty($_GET['rejected'])): ?>
  <div class="alert alert-success">Comprobante rechazado. El cliente puede enviar otro.</div>
<?php endif; ?>

<?php if (!empty($isOwner) && !empty($hasPendingPayment)): ?>
  <div class="card" style="margin-bottom:18px;">
    <h3 style="margin-top:0;">Comprobantes por verificar</h3>
    <?php foreach ($payments as $pending): if (($pending['tbcreditpaymentstate'] ?? '') !== 'pendiente') continue; ?>
      <div class="detail-grid" style="margin-bottom:14px;">
        <div class="detail-item"><div class="k">Monto</div><div class="v">₡ <?= number_format((float) $pending['tbcreditpaymentamount'], 2) ?></div></div>
        <div class="detail-item"><div class="k">Fecha</div><div class="v"><?= e($pending['tbcreditpaymentdate']) ?></div></div>
        <div class="detail-item"><div class="k">Nota</div><div class="v"><?= e($pending['tbcreditpaymentnote'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="k">Comprobante</div><div class="v"><?php if (!empty($pending['tbcreditpaymentproof'])): ?><a href="<?= e(image_url($pending['tbcreditpaymentproof'])) ?>" target="_blank" rel="noopener">Abrir comprobante</a><?php else: ?>—<?php endif; ?></div></div>
      </div>
      <div style="display:flex;gap:8px;">
        <form method="post" action="<?= e(base_url('credit', 'approvePayment')) ?>"><?= csrf_field() ?><input type="hidden" name="creditId" value="<?= (int) $credit->getIdCredit() ?>"><input type="hidden" name="paymentId" value="<?= (int) $pending['tbcreditpaymentid'] ?>"><button class="btn btn-primary" type="submit">Aprobar</button></form>
        <form method="post" action="<?= e(base_url('credit', 'rejectPayment')) ?>"><?= csrf_field() ?><input type="hidden" name="creditId" value="<?= (int) $credit->getIdCredit() ?>"><input type="hidden" name="paymentId" value="<?= (int) $pending['tbcreditpaymentid'] ?>"><button class="btn btn-outline" type="submit">Rechazar</button></form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php
$minimumPayment = (float) ($minimumPayment ?? 0);
$balance = (float) $credit->getBalance();
$minimumAllowed = min($minimumPayment, $balance);
?>

<div class="grid grid-2" style="align-items:start;">

  <?php if ($canPay): ?>
    <div class="card form-card">
      <h3 style="margin-top:0;">Registrar abono</h3>

      <form
        method="post"
        action="<?= e(base_url('credit', 'pay')) ?>"
        enctype="multipart/form-data"
        data-validate-form>
        <?= csrf_field() ?>
        <input type="hidden" name="creditId" value="<?= (int) $credit->getIdCredit() ?>">

        <div class="form-group">
          <label for="amount">
            Monto del abono (₡) <span class="required-mark">*</span>
          </label>
          <input
            class="form-control"
            type="text"
            id="amount"
            name="amount"
            inputmode="decimal"
            data-currency-mask="credit"
            data-min="<?= e(number_format($minimumAllowed, 2, '.', '')) ?>"
            data-validate="required positive minamount"
            placeholder="₡ 0.00"
            required>
          <div class="form-hint">Abono mínimo: ₡ <?= number_format($minimumAllowed, 2) ?></div>
        </div>

        <div class="form-group">
          <label for="paymentProof">
            Comprobante de pago <span class="required-mark">*</span>
          </label>
          <input
            class="form-control"
            type="file"
            id="paymentProof"
            name="paymentProof"
            accept=".jpg,.jpeg,.png,.pdf"
            required>
          <div class="form-hint">Adjunta una imagen o un PDF del comprobante.</div>
        </div>

        <div class="form-group">
          <label for="note">Nota (opcional)</label>
          <input
            class="form-control"
            type="text"
            id="note"
            name="note"
            maxlength="255">
        </div>

        <button class="btn btn-primary" type="submit">Registrar abono</button>
      </form>
    </div>
  <?php elseif ($isClientOwner && $hasPendingPayment): ?>
    <div class="card"><h3 style="margin-top:0;">Abono pendiente de verificación</h3><p class="muted">Podrás enviar otro comprobante después de que el propietario revise este.</p></div>
  <?php endif; ?>

  <div class="card">
    <h3 style="margin-top:0;">Detalle del crédito</h3>

    <div class="detail-grid">
      <div class="detail-item">
        <div class="k">Monto total</div>
        <div class="v">₡ <?= number_format($credit->getTotal(), 2) ?></div>
      </div>

      <div class="detail-item">
        <div class="k">Saldo pendiente</div>
        <div class="v">₡ <?= number_format($balance, 2) ?></div>
      </div>

      <div class="detail-item">
        <div class="k">Abono mínimo</div>
        <div class="v">₡ <?= number_format($minimumAllowed, 2) ?></div>
      </div>

      <div class="detail-item">
        <div class="k">Fecha límite</div>
        <div class="v"><?= e($credit->getDueDate()) ?></div>
      </div>

      <div class="detail-item">
        <div class="k">Estado</div>
        <div class="v"><?= e($label) ?></div>
      </div>
    </div>
  </div>

</div>

<div class="card" style="margin-top:18px;">
  <h3 style="margin-top:0;">Calendario de cuotas</h3>
  <?php if (empty($installments)): ?>
    <p class="muted">Este crédito no tiene cuotas registradas.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Cuota</th><th>Monto</th><th>Vence</th><th>Estado</th><th>Pagada el</th></tr>
        </thead>
        <tbody>
          <?php foreach ($installments as $installment): ?>
            <tr>
              <td>#<?= (int) $installment->getNumber() ?></td>
              <td>&#8353; <?= number_format($installment->getAmount(), 2) ?></td>
              <td><?= e($installment->getDueDate()) ?></td>
              <td>
                <?php $isLate = $installment->getState() !== 'pagado' && $installment->getDueDate() < date('Y-m-d'); ?>
                <span class="badge <?= $installment->getState() === 'pagado' ? 'success' : ($isLate ? 'danger' : 'warning') ?>">
                  <?= e($installment->getState() === 'pagado' ? 'Pagada' : ($isLate ? 'Vencida' : 'Pendiente')) ?>
                </span>
              </td>
              <td><?= e($installment->getPaidDate() ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>



<div class="card" style="margin-top:18px;">
  <h3 style="margin-top:0;">Historial de abonos</h3>
  <?php if (empty($payments)): ?>
    <p class="muted">Aún no hay abonos registrados.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Fecha</th><th>Monto</th><th>Estado</th><th>Comprobante</th><th>Nota</th><th>Registrado por</th></tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><?= e($p['tbcreditpaymentdate']) ?></td>
              <td>&#8353; <?= number_format((float) $p['tbcreditpaymentamount'], 2) ?></td>
              <td><span class="badge <?= ($p['tbcreditpaymentstate'] ?? '') === 'aprobado' ? 'success' : (($p['tbcreditpaymentstate'] ?? '') === 'rechazado' ? 'danger' : 'warning') ?>"><?= e(ucfirst($p['tbcreditpaymentstate'] ?? 'pendiente')) ?></span></td>
              <td>
                <?php if (!empty($p['tbcreditpaymentproof'])): ?>
                  <a href="<?= e(image_url($p['tbcreditpaymentproof'])) ?>" target="_blank" rel="noopener">Ver comprobante</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?= e($p['tbcreditpaymentnote'] ?? '') ?></td>
              <td><?= e($p['tbrolename'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
