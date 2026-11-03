<?php
$catalogTypes = $catalogTypes ?? ['Efectivo', 'Transferencia Bancaria', 'SINPE Móvil'];
$selectedType = $_POST['type'] ?? '';
require_once __DIR__ . '/../_header.php';
?>

<div class="page-head">
  <div>
    <h1>Nuevo método de pago</h1>
  </div>
</div>

<div class="card form-card" style="max-width:520px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('paymentMethod', 'create')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="type">Tipo de método de pago <span class="required-mark">*</span></label>
      <select class="form-control" id="type" name="type" required>
        <option value="">— Selecciona —</option>
        <?php foreach ($catalogTypes as $typeOption): ?>
          <option value="<?= e($typeOption) ?>" <?= $selectedType === $typeOption ? 'selected' : '' ?>>
            <?= e($typeOption) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="form-hint">Catálogo base: Efectivo, Transferencia Bancaria y SINPE Móvil.</p>
    </div>

    <button class="btn btn-primary" type="submit">Crear método</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
