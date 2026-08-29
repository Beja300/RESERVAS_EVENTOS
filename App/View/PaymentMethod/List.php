<?php require_once __DIR__ . '/../_header.php';
$isAdmin = current_user_type() === 'admin';
?>

<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Método de pago actualizado correctamente.</div>
<?php endif; ?>

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
  <div style="display:flex;flex-wrap:wrap;gap:14px;">
    <?php foreach ($paymentMethods as $pm): ?>
      <div class="card list-item" style="margin-bottom:0;width:fit-content;">
        <div>
          <div class="title"><?= e($pm->getPaymentMethod()) ?></div>
          <div class="desc">Método #<?= (int) $pm->getIdPaymentMethod() ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <?= $pm->getIsActive() ? '<span class="badge success">Activo</span>' : '<span class="badge neutral">Inactivo</span>' ?>
          <?php if ($isAdmin): ?>
            <a class="btn btn-sm btn-secondary"
               href="<?= e(base_url('paymentMethod', 'edit', ['id' => $pm->getIdPaymentMethod()])) ?>">Editar</a>
            <form method="post" action="<?= e(base_url('paymentMethod', 'delete')) ?>"
                  onsubmit="return confirm('¿Eliminar este método de pago? Ya no estará disponible en nuevas reservas.');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $pm->getIdPaymentMethod() ?>">
              <button class="btn btn-sm btn-warning" type="submit">Eliminar</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
