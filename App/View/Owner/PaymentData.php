<?php $pageJs = ['owner/paymentData']; ?>
<?php require_once __DIR__ . '/../_header.php';

$ownerPayments = $ownerPayments ?? [];
$paymentMethods = $paymentMethods ?? [];
?>

<div class="page-head">
  <div>
    <h1>Datos de cobro de mi local</h1>
  </div>
</div>

<p class="muted" style="margin-bottom:16px;">
  Al reservar, el cliente verá los datos que correspondan al método que elijas: SINPE Móvil,
  transferencia bancaria o pago en el lugar.
  Solo se muestran los métodos que tengas <strong>activos</strong>.
</p>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div style="margin-bottom:18px;">
  <button class="btn btn-primary" type="button" data-toggle-payment-form>
    + Agregar método de pago nuevo
  </button>
</div>

<div class="card form-card form-page" style="margin-bottom:18px;" data-payment-form-wrap hidden>
  <h3 class="card-title">Agregar / actualizar método</h3>
  <form method="post" action="<?= e(base_url('owner', 'savePayment')) ?>"
        data-ajax-payment-save data-payment-form>
    <?= csrf_field() ?>
    <input type="hidden" name="idOwnerPayment" data-payment-id value="0">

    <div class="form-grid-2">
      <div class="form-group">
        <label for="paymentMethodId">Método de pago *</label>
        <select class="form-control" id="paymentMethodId" name="paymentMethodId" required
                data-validate="required">
          <option value="">— Selecciona —</option>
          <?php foreach ($paymentMethods as $pm): ?>
            <option value="<?= (int) $pm->getIdPaymentMethod() ?>"><?= e($pm->getPaymentMethod()) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">SINPE Móvil solicita un teléfono; transferencia, un IBAN costarricense; pago en el lugar no solicita datos bancarios.</p>
      </div>

      <div class="form-group">
        <label for="active">Disponibilidad</label>
        <select class="form-control" id="active" name="active">
          <option value="1" selected>Activo</option>
          <option value="0">Desactivado</option>
        </select>
        <p class="form-hint">Activo: visible a tus clientes. Desactivado: se oculta.</p>
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group" data-payment-holder-group>
        <label for="holder">Titular / nombre *</label>
        <input class="form-control" type="text" id="holder" name="holder"
               placeholder="Ej: Salón La Quinta S.A.">
      </div>

      <div class="form-group" data-payment-account-group>
        <label for="account">Cuenta bancaria / teléfono SINPE *</label>
        <div style="display:flex;align-items:stretch;gap:8px;">
          <span class="form-control" data-sinpe-prefix hidden style="width:auto;align-items:center;">+506</span>
          <input class="form-control" type="text" id="account" name="account"
                 autocomplete="off" inputmode="numeric" placeholder="">
        </div>
      </div>
    </div>

    <div class="form-group" data-payment-instructions-group>
      <label for="instructions">Instrucciones de pago</label>
      <textarea class="form-control" id="instructions" name="instructions" rows="2"
                placeholder="Ej: Transferencia SINPE; enviar el comprobante al subirlo."></textarea>
    </div>

    <button class="btn btn-primary" type="submit">Guardar método</button>
  </form>
</div>

<div class="card">
  <h3 class="card-title">Mis métodos configurados</h3>
  <?php if (empty($ownerPayments)): ?>
    <p class="muted" style="margin:0;">Aún no has configurado ningún método de cobro.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Método</th>
            <th>Titular</th>
            <th>Cuenta / Teléfono</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ownerPayments as $op): ?>
            <?php
              $paymentName = mb_strtolower($op->getPaymentMethod(), 'UTF-8');
              $paymentAccount = (string) $op->getAccount();
              if (str_contains($paymentName, 'sinpe')) {
                $paymentAccount = preg_replace('/\D/', '', $paymentAccount);
                if (strlen($paymentAccount) === 8) $paymentAccount = '+506 ' . substr($paymentAccount, 0, 4) . '-' . substr($paymentAccount, 4);
              } elseif (str_contains($paymentName, 'transfer') || str_contains($paymentName, 'iban')) {
                $paymentAccount = trim(chunk_split(strtoupper(preg_replace('/\s+/', '', $paymentAccount)), 4, ' '));
              }
            ?>
            <tr>
              <td><?= e($op->getPaymentMethod()) ?></td>
              <td><?= e($op->getHolder()) ?></td>
              <td><?= e($paymentAccount !== '' ? $paymentAccount : '—') ?></td>
              <td>
                <span class="badge <?= $op->getIsActive() ? 'success' : 'neutral' ?>">
                  <?= $op->getIsActive() ? 'Activo' : 'Inactivo' ?>
                </span>
              </td>
              <td style="text-align:right;">
                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                  <button class="btn btn-sm btn-ghost" type="button" data-edit-payment
                          data-id="<?= (int) $op->getIdOwnerPayment() ?>"
                          data-method-id="<?= (int) $op->getIdPaymentMethod() ?>"
                          data-method="<?= e($op->getPaymentMethod()) ?>"
                          data-holder="<?= e($op->getHolder()) ?>"
                          data-account="<?= e($op->getAccount()) ?>"
                          data-instructions="<?= e($op->getInstructions()) ?>"
                          data-active="<?= $op->getIsActive() ? '1' : '0' ?>">
                    Editar
                  </button>
                  <form method="post" action="<?= e(base_url('owner', 'removePayment')) ?>"
                        data-ajax-payment-remove data-id="<?= (int) $op->getIdOwnerPayment() ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="idOwnerPayment" value="<?= (int) $op->getIdOwnerPayment() ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
