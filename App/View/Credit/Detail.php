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
$canPay = $isClientOwner && $effectiveState !== 'pagado';
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
<?php if (!empty($_GET['paid'])): ?>
  <div class="alert alert-success">Abono registrado correctamente.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px;">
  <div class="detail-grid">
    <div class="detail-item"><div class="k">Monto total</div><div class="v">&#8353; <?= number_format($credit->getTotal(), 2) ?></div></div>
    <div class="detail-item"><div class="k">Saldo pendiente</div><div class="v">&#8353; <?= number_format($credit->getBalance(), 2) ?></div></div>
    <div class="detail-item"><div class="k">Fecha límite</div><div class="v"><?= e($credit->getDueDate()) ?></div></div>
    <div class="detail-item"><div class="k">Estado</div><div class="v"><?= e($label) ?></div></div>
  </div>
</div>

<?php if ($canPay): ?>
<div class="card form-card" style="max-width:560px;margin-top:18px;">
  <h3 style="margin-top:0;">Registrar abono</h3>
  <form method="post" action="<?= e(base_url('credit', 'pay')) ?>" data-validate-form>
    <?= csrf_field() ?>
    <input type="hidden" name="creditId" value="<?= (int) $credit->getIdCredit() ?>">
    <div class="form-group">
      <label for="amount">Monto del abono (₡) <span class="required-mark">*</span></label>
      <input class="form-control" type="number" id="amount" name="amount" step="0.01" min="0.01"
             max="<?= number_format($credit->getBalance(), 2, '.', '') ?>" required data-validate="positive"
             value="">
      <div class="form-hint">Máximo: &#8353; <?= number_format($credit->getBalance(), 2) ?></div>
    </div>
    <div class="form-group">
      <label for="note">Nota (opcional)</label>
      <input class="form-control" type="text" id="note" name="note" maxlength="255">
    </div>
    <button class="btn btn-primary" type="submit">Registrar abono</button>
  </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:18px;">
  <h3 style="margin-top:0;">Historial de abonos</h3>
  <?php if (empty($payments)): ?>
    <p class="muted">Aún no hay abonos registrados.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Fecha</th><th>Monto</th><th>Nota</th><th>Registrado por</th></tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><?= e($p['tbcreditpaymentdate']) ?></td>
              <td>&#8353; <?= number_format((float) $p['tbcreditpaymentamount'], 2) ?></td>
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
