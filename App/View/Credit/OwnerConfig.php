<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Crédito a clientes frecuentes</h1>
    <p class="muted">Define el plazo y desde cuántas reservas un cliente puede financiar sus pagos.</p>
  </div>
  <a class="btn btn-outline" href="<?= e(base_url('credit', 'ownerList')) ?>">Cuentas por cobrar</a>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($_GET['saved'])): ?>
    <div class="alert alert-success">Política de crédito guardada correctamente.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('credit', 'saveConfig')) ?>" data-validate-form>
    <?= csrf_field() ?>

    <div class="checkbox-row">
      <input type="checkbox" id="enabled" name="enabled" <?= $config->isEnabled() ? 'checked' : '' ?>>
      <label for="enabled">Aceptar crédito en mis locales</label>
    </div>

    <div class="form-group">
      <label for="days">Plazo de pago (días) <span class="required-mark">*</span></label>
      <input class="form-control" type="number" id="days" name="days" min="1" max="365" required
             data-validate="positiveint"
             value="<?= (int) $config->getDays() ?>">
      <div class="form-hint">Entre 1 y 365 días contados desde la fecha de la reserva.</div>
    </div>

    <div class="form-group">
      <label for="frequentMin">Reservas mínimas para ser cliente frecuente</label>
      <input class="form-control" type="number" id="frequentMin" name="frequentMin" min="0" max="1000"
             value="<?= (int) $config->getFrequentMin() ?>">
      <div class="form-hint">0 = cualquier cliente puede financiar. Ej: 3 exige al menos 3 reservas previas.</div>
    </div>

    <button class="btn btn-primary" type="submit">Guardar política</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
