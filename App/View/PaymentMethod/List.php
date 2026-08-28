<?php require_once __DIR__ . '/../_header.php';
$isAdmin = current_user_type() === 'admin';
?>

<div class="page-head">
  <div>
    <h1>Métodos de pago</h1>
    <p class="muted">Medios disponibles para pagar tus reservas</p>
  </div>
  <?php if ($isAdmin): ?>
    <a class="btn btn-primary" href="<?= e(base_url('paymentMethod', 'showForm')) ?>">+ Nuevo método</a>
  <?php endif; ?>
</div>

<?php if (empty($paymentMethods)): ?>
  <div class="card empty">
    <span class="emoji">&#128176;</span>
    No hay métodos de pago disponibles.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($paymentMethods as $pm): ?>
      <div class="card list-item" style="margin-bottom:0;">
        <div>
          <div class="title"><?= e($pm->getPaymentMethod()) ?></div>
          <div class="desc">Método #<?= (int) $pm->getIdPaymentMethod() ?></div>
        </div>
        <?= $pm->getIsActive() ? '<span class="badge success">Activo</span>' : '<span class="badge neutral">Inactivo</span>' ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
