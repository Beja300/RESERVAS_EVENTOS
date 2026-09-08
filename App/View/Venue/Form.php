<?php require_once __DIR__ . '/../_header.php';

$isEdit = $venue !== null;
$action = $isEdit ? base_url('venue', 'update') : base_url('venue', 'create');
$pageJs = ['venue/form', 'venue/location'];

$curProvince = $isEdit && $location !== null ? $location->getProvinceLocation() : ($_POST['province'] ?? '');
$curCanton   = $isEdit && $location !== null ? $location->getCantonLocation()   : ($_POST['canton'] ?? '');
$curDistrict = $isEdit && $location !== null ? $location->getDistrictLocation() : ($_POST['district'] ?? '');
$curTown     = $isEdit && $location !== null ? $location->getTownLocation()     : ($_POST['town'] ?? '');
$curDesc     = $isEdit && $location !== null ? $location->getDescriptionLocation() : ($_POST['description'] ?? '');
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar local' : 'Nuevo local' ?></h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Volver a mis locales</a>
  </div>
</div>

<div class="card form-card" style="max-width:640px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e($action) ?>" data-ajax-venue-form>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="idVenue" value="<?= (int) $venue->getIdVenue() ?>">
    <?php endif; ?>

    <div class="form-group">
      <label for="name">Nombre del local *</label>
      <input class="form-control" type="text" id="name" name="name" required
        value="<?= e($isEdit ? $venue->getNameVenue() : ($_POST['name'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="type">Tipo de local</label>
      <input class="form-control" type="text" id="type" name="type"
        placeholder="Ej: Salón de eventos, Restaurante, Jardín..."
        value="<?= e($isEdit && $venue->getTypeVenue() !== '' ? $venue->getTypeVenue() : ($_POST['type'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="capacity">Capacidad</label>
      <input class="form-control" type="number" id="capacity" name="capacity" min="1"
        value="<?= e($isEdit ? (string) $venue->getCapacityVenue() : ($_POST['capacity'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="price">Precio de renta por evento *</label>
      <input class="form-control" type="number" id="price" name="price" min="0.01" step="0.01" required
        value="<?= e($isEdit ? number_format($venue->getPriceVenue(), 2, '.', '') : ($_POST['price'] ?? '')) ?>">
      <p class="form-hint">Este precio se incluye siempre en la factura de cada reserva de este local.</p>
    </div>

    <div class="form-group">
      <label for="image">Foto del local <?= $isEdit ? '' : '*' ?></label>
      <input class="form-control" type="file" id="image" name="image" accept="image/*"
        <?= $isEdit ? '' : 'required' ?> onchange="var f=this.files[0];var p=document.getElementById('imagePreview');if(f){var r=new FileReader();r.onload=function(e){p.src=e.target.result;p.style.display='block';};r.readAsDataURL(f);}">
      <?php $currentImg = $isEdit ? $venue->getImageVenue() : ($_POST['image'] ?? ''); ?>
      <img id="imagePreview" src="<?= e(image_url($currentImg !== '' ? $currentImg : null)) ?>"
        alt="Vista previa del local" style="<?= $currentImg !== '' ? 'display:block;' : 'display:none;' ?>width:160px;height:110px;object-fit:cover;border-radius:8px;margin-top:10px;border:1px solid var(--neutral-200);">
      <p class="form-hint">Sube una foto desde tu equipo (jpg, png, webp o gif, máx. 2 MB).</p>
    </div>

    <?php if ($isEdit): ?>
      <div class="checkbox-row" style="margin:18px 0;">
        <input type="checkbox" id="active" name="active" <?= $venue->getIsActive() ? 'checked' : '' ?>>
        <label for="active">Local activo (visible en el catálogo)</label>
      </div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid var(--neutral-200);margin:18px 0;">
    <p style="font-weight:700;color:var(--neutral-800);margin-bottom:14px;">Ubicación</p>

    <div class="grid grid-3">
      <div class="form-group">
        <label for="province">Provincia *</label>
        <select class="form-control" id="province" name="province" data-level="province" required
                data-value="<?= e($curProvince) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="canton">Cantón *</label>
        <select class="form-control" id="canton" name="canton" data-level="canton" disabled required
                data-value="<?= e($curCanton) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="district">Distrito *</label>
        <select class="form-control" id="district" name="district" data-level="district" disabled required
                data-value="<?= e($curDistrict) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label for="town">Pueblo</label>
      <input class="form-control" type="text" id="town" name="town"
        value="<?= e($curTown ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="description">Descripción</label>
      <input class="form-control" type="text" id="description" name="description"
        value="<?= e($curDesc ?? '') ?>">
    </div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear local' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
