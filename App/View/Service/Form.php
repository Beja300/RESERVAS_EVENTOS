<?php $pageJs = ['service/form']; ?>
<?php require_once __DIR__ . '/../_header.php';
$isEdit = $service !== null;
$action = $isEdit ? base_url('service', 'update') : base_url('service', 'create');
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar servicio' : 'Nuevo servicio' ?></h1>
    <a href="<?= e(base_url('service', 'list', ['venueId' => $idVenue])) ?>">&larr; Volver a servicios</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e($action) ?>" data-ajax-service-form>
    <?= csrf_field() ?>
    <input type="hidden" name="venueId" value="<?= (int) $idVenue ?>">
    <?php if ($isEdit): ?>
      <input type="hidden" name="idService" value="<?= (int) $service->getIdService() ?>">
    <?php endif; ?>

    <div class="form-group">
      <label for="name">Nombre del servicio *</label>
      <input class="form-control" type="text" id="name" name="name" required
             value="<?= e($_POST['name'] ?? ($isEdit ? $service->getNameService() : '')) ?>">
    </div>

    <div class="form-group">
      <label for="type">Tipo</label>
      <input class="form-control" type="text" id="type" name="type"
             placeholder="Ej: Catering, DJ, Decoración..."
             value="<?= e(trim($_POST['type'] ?? '') !== '' ? $_POST['type'] : ($isEdit ? $service->getTypeService() : '')) ?>">
    </div>

    <div class="form-group">
      <label for="price">Precio *</label>
      <input class="form-control" type="number" id="price" name="price" step="0.01" min="0.01" required
             value="<?= e($_POST['price'] ?? ($isEdit ? number_format($service->getPriceService(), 2, '.', '') : '')) ?>">
    </div>

    <?php if ($isEdit): ?>
      <div class="checkbox-row">
        <input type="checkbox" id="active" name="active"
          <?= !empty($_POST) ? (isset($_POST['active']) ? 'checked' : '') : ($service->getIsActive() ? 'checked' : '') ?>>
        <label for="active">Servicio activo</label>
      </div>
    <?php endif; ?>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear servicio' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
