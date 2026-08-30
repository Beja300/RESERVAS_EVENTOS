<?php require_once __DIR__ . '/../_header.php';

$ownerPayments = $ownerPayments ?? [];
$paymentMethods = $paymentMethods ?? [];
?>

<div class="page-head">
  <div>
    <h1>Datos de cobro de mi local</h1>
  </div>
</div>

<p class="muted" style="margin-bottom:16px;max-width:640px;">
  Configura cómo deseas que te paguen tus clientes. Cuando un cliente reserva tu local,
  elegirá uno de estos métodos y verá los datos que indiques (titular, cuenta/teléfono,
  instrucciones) para pagarte y subir su comprobante.
  Solo se muestran los métodos que tengas <strong>activos</strong>.
</p>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div style="margin-bottom:18px;max-width:640px;">
  <button class="btn btn-primary" type="button" data-toggle-payment-form>
    + Agregar método de pago nuevo
  </button>
</div>

<div class="card form-card" style="max-width:640px;margin-bottom:18px;" data-payment-form-wrap hidden>
  <h3 class="card-title">Agregar / actualizar método</h3>
  <form method="post" action="<?= e(base_url('owner', 'savePayment')) ?>"
        data-ajax-payment-save data-payment-form>
    <?= csrf_field() ?>
    <input type="hidden" name="idOwnerPayment" data-payment-id value="0">

    <div class="form-group">
      <label for="paymentMethodId">Método de pago *</label>
      <select class="form-control" id="paymentMethodId" name="paymentMethodId" required>
        <option value="">— Selecciona —</option>
        <?php foreach ($paymentMethods as $pm): ?>
          <option value="<?= (int) $pm->getIdPaymentMethod() ?>"><?= e($pm->getPaymentMethod()) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="holder">Titular / nombre</label>
      <input class="form-control" type="text" id="holder" name="holder"
             placeholder="Ej: Salón La Quinta S.A.">
    </div>

    <div class="form-group">
      <label for="account">Cuenta bancaria / teléfono SINPE</label>
      <input class="form-control" type="text" id="account" name="account"
             placeholder="Ej: CR12 1234 5678 9012 3456 7">
    </div>

    <div class="form-group">
      <label for="instructions">Instrucciones de pago</label>
      <textarea class="form-control" id="instructions" name="instructions" rows="2"
                placeholder="Ej: Transferencia SINPE; enviar el comprobante al subirlo."></textarea>
    </div>

    <div class="checkbox-row">
      <input type="checkbox" id="active" name="active" checked>
      <label for="active">Activo (ofrecer a mis clientes)</label>
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
            <tr>
              <td><?= e($op->getPaymentMethod()) ?></td>
              <td><?= e($op->getHolder()) ?></td>
              <td><?= e($op->getAccount()) ?></td>
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

<script src="<?= e(js_url('owner-payment')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>
