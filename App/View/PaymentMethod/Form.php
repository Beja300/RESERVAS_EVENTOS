<?php require_once __DIR__ . '/../_header.php'; ?>

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
      <label for="type">Tipo de método de pago *</label>
      <input class="form-control" type="text" id="type" name="type" required
             placeholder="Ej: Tarjeta de crédito, Transferencia, Efectivo..."
             value="<?= e($_POST['type'] ?? '') ?>">
    </div>

    <button class="btn btn-primary" type="submit">Crear método</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
