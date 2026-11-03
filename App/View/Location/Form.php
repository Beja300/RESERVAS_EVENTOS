<?php
require_once __DIR__ . '/../_header.php';

$pageJs = ['venue/location'];

$isEdit = isset($location) && $location !== null;
$error = $error ?? null;

$pick = static function (string $key, $fallback = '') {
    return $_POST[$key] ?? $fallback;
};

if ($isEdit) {
    $provinceValue = $pick('province', $location->getProvinceLocation());
    $cantonValue   = $pick('canton', $location->getCantonLocation());
    $districtValue = $pick('district', $location->getDistrictLocation());
    $townValue     = $pick('town', (string) $location->getTownLocation());
    $descValue     = $pick('description', (string) $location->getDescriptionLocation());
} else {
    $provinceValue = $pick('province');
    $cantonValue   = $pick('canton');
    $districtValue = $pick('district');
    $townValue     = $pick('town');
    $descValue     = $pick('description');
}
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar ubicación' : 'Nueva ubicación' ?></h1>
    <a href="<?= e(base_url('location', 'list')) ?>">&larr; Volver a ubicaciones</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if ($error !== null): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('location', $isEdit ? 'update' : 'create')) ?>" data-validate-form>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int) $location->getIdLocation() ?>">
    <?php endif; ?>
    <div class="grid grid-3">
      <div class="form-group">
        <label for="province">Provincia <span class="required-mark">*</span></label>
        <select class="form-control" id="province" name="province" data-level="province" required
                data-validate="required" data-value="<?= e($provinceValue) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="canton">Cantón <span class="required-mark">*</span></label>
        <select class="form-control" id="canton" name="canton" data-level="canton" disabled required
                data-validate="required" data-value="<?= e($cantonValue) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="district">Distrito <span class="required-mark">*</span></label>
        <select class="form-control" id="district" name="district" data-level="district" disabled required
                data-validate="required" data-value="<?= e($districtValue) ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="form-group">
        <label for="town">Pueblo</label>
        <input class="form-control" type="text" id="town" name="town" maxlength="100"
               placeholder="Pueblo o comunidad"
               value="<?= e($townValue) ?>">
      </div>
      <div class="form-group">
        <label for="description">Descripción</label>
        <input class="form-control" type="text" id="description" name="description" maxlength="300"
               placeholder="Otras señas de la ubicación"
               value="<?= e($descValue) ?>">
      </div>
    </div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear ubicación' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
