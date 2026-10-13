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
      <label for="name">Nombre del servicio <span class="required-mark">*</span></label>
      <input class="form-control" type="text" id="name" name="name" required
             value="<?= e($_POST['name'] ?? ($isEdit ? $service->getNameService() : '')) ?>">
    </div>

    <div class="form-group">
      <label for="type">Tipo / categoría</label>
      <input class="form-control" type="text" id="type" name="type" list="serviceTypeOptions"
             placeholder="Elige o escribe una categoría"
             value="<?= e($isEdit && $service->getTypeService() !== '' ? $service->getTypeService() : ($_POST['type'] ?? '')) ?>">
      <datalist id="serviceTypeOptions">
        <?php foreach (['Catering', 'Decoración', 'Producción', 'Mobiliario', 'DJ', 'Fotografía', 'Animación', 'Seguridad', 'Transporte', 'Iluminación', 'Sonido'] as $opt): ?>
          <option value="<?= e($opt) ?>"></option>
        <?php endforeach; ?>
      </datalist>
      <div class="form-hint">Categorías sugeridas; el catálogo se filtra por este valor.</div>
    </div>

    <div class="form-group">
      <label for="price">Precio <span class="required-mark">*</span></label>
      <input class="form-control" type="number" id="price" name="price" step="0.01" min="0.01" required
             value="<?= e($isEdit ? number_format($service->getPriceService(), 2, '.', '') : ($_POST['price'] ?? '')) ?>">
    </div>

    <?php if ($isEdit): ?>
      <div class="checkbox-row">
        <input type="checkbox" id="active" name="active" <?= $service->getIsActive() ? 'checked' : '' ?>>
        <label for="active">Servicio activo</label>
      </div>
    <?php endif; ?>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear servicio' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
