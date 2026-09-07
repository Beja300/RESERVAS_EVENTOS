<?php $pageCss = 'admin/commission-config';
      $pageJs = ['admin/commission-config']; ?>
<?php require_once __DIR__ . '/../_header.php';

$config = $config ?? null;
if ($config === null) {
  echo '<div class="alert alert-error">No se pudo cargar la configuración.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1>Comisión e IVA</h1>
    <a href="<?= e(base_url('admin', 'dashboard')) ?>">&larr; Volver al panel</a>
  </div>
</div>

<?php if (isset($_GET['saved'])): ?>
  <div class="alert alert-success">Comisión e IVA actualizados correctamente.</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
  <div class="alert alert-error"><?= e($_GET['error']) ?></div>
<?php endif; ?>

<div class="card form-card commission-config-card">
  <p class="muted" style="margin-bottom:14px;">
    Las tarifas se aplican a las nuevas reservas:
    el cliente paga <strong>subtotal + IVA</strong> y al propietario se le
    descuenta la <strong>comisión</strong> y el <strong>IVA</strong> del monto pagado.
  </p>

  <form method="post" action="<?= e(base_url('admin', 'saveCommissionConfig')) ?>"
        data-ajax-commission-config>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="percentage">Comisión de la plataforma (%)</label>
      <input class="form-control" type="number" id="percentage" name="percentage"
             min="0" max="100" step="0.01" required
             data-validate-number
             value="<?= e(number_format($config->getPercentage(), 2)) ?>">
      <p class="form-hint">Porcentaje descontado al propietario sobre el subtotal.</p>
    </div>

    <div class="form-group">
      <label for="tax">IVA (%)</label>
      <input class="form-control" type="number" id="tax" name="tax"
             min="0" max="100" step="0.01" required
             data-validate-number
             value="<?= e(number_format($config->getTax(), 2)) ?>">
      <p class="form-hint">Impuesto que paga el cliente sobre el subtotal.</p>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-outline" href="<?= e(base_url('admin', 'dashboard')) ?>">Cancelar</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>