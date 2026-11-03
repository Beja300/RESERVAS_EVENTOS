<?php require_once __DIR__ . '/../_header.php';
$isActive = !empty($_POST) ? isset($_POST['isActive']) : $paymentMethod->getIsActive();
$catalogTypes = $catalogTypes ?? ['Efectivo', 'Transferencia Bancaria', 'SINPE Móvil'];
$selectedType = $_POST['type'] ?? $paymentMethod->getPaymentMethod();
?>

<div class="page-head">
  <div>
    <h1>Editar método de pago</h1>
  </div>
</div>

<div class="card form-card" style="max-width:520px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('paymentMethod', 'update')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $paymentMethod->getIdPaymentMethod() ?>">
    <div class="form-group">
      <label for="type">Tipo de método de pago <span class="required-mark">*</span></label>
      <select class="form-control" id="type" name="type" required>
        <?php foreach ($catalogTypes as $typeOption): ?>
          <option value="<?= e($typeOption) ?>" <?= $selectedType === $typeOption ? 'selected' : '' ?>>
            <?= e($typeOption) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="form-hint">Catálogo base: Efectivo, Transferencia Bancaria y SINPE Móvil.</p>
    </div>

    <div class="form-group">
      <label class="checkbox">
        <input type="checkbox" name="isActive" value="1" <?= $isActive ? 'checked' : '' ?>>
        Activo (disponible en reservas)
      </label>
    </div>

    <div class="form-group" style="display:flex;gap:10px;margin-bottom:0;">
      <a class="btn btn-secondary" href="<?= e(base_url('paymentMethod', 'list')) ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>