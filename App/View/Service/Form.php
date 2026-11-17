<?php $pageJs = ['service/form']; ?>
<?php require_once __DIR__ . '/../_header.php';
$isEdit = $service !== null;
$isSingleVenue = !$isEdit && $idVenue > 0;
$action = $isEdit ? base_url('service', 'update') : base_url('service', 'create');
$title = $isEdit
  ? 'Editar servicio — ' . ($venue !== null ? $venue->getNameVenue() : '')
  : ($isSingleVenue && $venue !== null ? 'Nuevo servicio — ' . $venue->getNameVenue() : 'Nuevo servicio');
$types = $types ?? [];
$images = $images ?? [];
$venues = $venues ?? [];

$posted = static function (string $key, mixed $fallback = ''): string {
  return array_key_exists($key, $_POST) ? (string) $_POST[$key] : (string) $fallback;
};

$curName = $isEdit ? $service->getNameService() : $posted('name');
$curType = $isEdit ? $service->getTypeService() : $posted('type');
$curPrice = $isEdit ? number_format($service->getPriceService(), 2, '.', '') : $posted('price');
$curDescription = $isEdit ? (string) $service->getDescriptionService() : $posted('description');
$curUnit = $isEdit ? $service->getUnitService() : $posted('unit', 'unidad');
$curMin = $isEdit ? (string) $service->getMinQuantity() : $posted('minQuantity', '1');
$curMax = $isEdit && $service->getMaxQuantity() !== null ? (string) $service->getMaxQuantity() : $posted('maxQuantity');
$curIncluded = $isEdit ? (string) $service->getIncludedQuantity() : $posted('includedQuantity', '0');
$curActive = $isEdit ? ($service->getIsActive() ? '1' : '0') : '1';
?>

<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <a href="<?= e($isEdit || $isSingleVenue ? base_url('service', 'list', ['venueId' => $idVenue]) : base_url('service', 'groups')) ?>">&larr; Volver</a>
  </div>
</div>

<div class="card form-card form-page">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-ajax-service-form>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="idService" value="<?= (int) $service->getIdService() ?>">
      <input type="hidden" name="venueId" value="<?= (int) $idVenue ?>">
    <?php elseif ($isSingleVenue): ?>
      <input type="hidden" name="venueId" value="<?= (int) $idVenue ?>">
    <?php endif; ?>

    <?php if (!$isEdit && !$isSingleVenue): ?>
      <div class="form-group">
        <label>Locales donde se ofrece *</label>
        <?php if (empty($venues)): ?>
          <div class="alert alert-error">No tienes locales activos. Activa o crea un local primero.</div>
        <?php else: ?>
          <div style="padding:12px 14px;margin-bottom:14px;border:1px solid var(--neutral-200);border-radius:10px;background:#f8fafc;">
            <div class="checkbox-row">
              <input type="checkbox" id="allVenues" name="allVenues" value="1">
              <label for="allVenues"><strong>Agregar servicio a todos mis locales</strong></label>
            </div>
            <p class="form-hint" style="margin:6px 0 0;">El servicio se activará en cada uno de tus locales activos.</p>
          </div>
          <p class="muted" style="margin:0 0 10px;">O selecciona locales específicos</p>
          <div id="venue-checks" class="form-grid-3">
            <?php foreach ($venues as $v): ?>
              <label class="radio-option" style="justify-content:flex-start;">
                <input type="checkbox" name="venueIds[]" value="<?= (int) $v->getIdVenue() ?>"
                  <?= in_array((string) $v->getIdVenue(), array_map('strval', $_POST['venueIds'] ?? []), true) ? 'checked' : '' ?>>
                <span><?= e($v->getNameVenue()) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="name">Nombre del servicio *</label>
        <input class="form-control" type="text" id="name" name="name" required
               data-validate="required" value="<?= e($curName) ?>">
      </div>

      <div class="form-group">
        <label for="type">Tipo *</label>
        <input class="form-control" type="text" id="type" name="type" required list="service-types"
               data-validate="required" placeholder="Ej: Catering, DJ, Decoración..."
               value="<?= e($curType) ?>">
        <datalist id="service-types">
          <?php foreach ($types as $type): ?>
            <option value="<?= e($type) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="price">Precio *</label>
        <input class="form-control no-spinners" type="text" inputmode="decimal" id="price" name="price" required
               data-currency-mask data-validate="required positive"
               value="<?= e($curPrice) ?>">
      </div>
      <div class="form-group">
        <label for="unit">Unidad *</label>
        <select class="form-control" id="unit" name="unit">
          <?php foreach (['unidad', 'persona', 'hora', 'evento'] as $unit): ?>
            <option value="<?= e($unit) ?>" <?= $curUnit === $unit ? 'selected' : '' ?>><?= e(ucfirst($unit)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-grid-3">
      <div class="form-group">
        <label for="minQuantity">Cantidad mínima *</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
               id="minQuantity" name="minQuantity" required data-validate="required positive" value="<?= e($curMin) ?>">
      </div>
      <div class="form-group">
        <label for="maxQuantity">Cantidad máxima</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
               id="maxQuantity" name="maxQuantity" value="<?= e($curMax) ?>">
      </div>
      <div class="form-group">
        <label for="includedQuantity">Cantidad incluida</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
               id="includedQuantity" name="includedQuantity" value="<?= e($curIncluded) ?>">
        <p class="form-hint">Unidades que ya cubre el precio; las adicionales se cobran.</p>
      </div>
    </div>

    <div class="form-group">
      <label for="description">Descripción</label>
      <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?= e($curDescription) ?></textarea>
    </div>

    <?php if ($isEdit): ?>
      <div class="form-group">
        <label for="active">Estado *</label>
        <select class="form-control" id="active" name="active">
          <option value="1" <?= $curActive === '1' ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= $curActive === '0' ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <?php $imageRequired = !$isEdit || empty($images); ?>
      <label for="gallery">Imágenes del servicio <?php if ($imageRequired): ?><span class="required-mark">*</span><?php endif; ?></label>
      <input class="form-control" type="file" id="gallery" name="gallery[]" accept="image/*" multiple
             <?= $imageRequired ? 'required' : '' ?> data-gallery-input data-images-existing="<?= count($images) ?>">
      <p class="form-hint">Puedes subir varias imágenes (jpg, png, webp o gif, máx. 2 MB cada una).<?= $imageRequired ? ' Se requiere al menos una imagen.' : '' ?></p>
      <?php if (!empty($images)): ?>
        <div class="actions" style="flex-wrap:wrap;margin-top:10px;">
          <?php foreach ($images as $image): ?>
            <label style="display:flex;flex-direction:column;gap:4px;align-items:center;font-size:.8rem;">
              <img src="<?= e(image_url($image->getPath())) ?>" alt="Imagen del servicio"
                   style="width:110px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--neutral-200);">
              <span><input type="checkbox" name="removeGallery[]" value="<?= (int) $image->getIdImage() ?>"> Quitar</span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Guardar servicio' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
