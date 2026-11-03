<?php
$pageCss = 'venue/form';
$pageJs = ['venue/form', 'venue/location', 'venue/location-map'];
?>
<?php require_once __DIR__ . '/../_header.php';

$isEdit = $venue !== null;
$location = $location ?? null;
$action = $isEdit ? base_url('venue', 'update') : base_url('venue', 'create');

$posted = static function (string $key, ?string $fallback = null): string {
  return array_key_exists($key, $_POST) ? (string) $_POST[$key] : (string) ($fallback ?? '');
};

$curType     = $posted('locationType', $location?->getTypeLocation() ?? 'exacta') === 'aproximada' ? 'aproximada' : 'exacta';
$curProvince = $posted('province', $location?->getProvinceLocation());
$curCanton   = $posted('canton', $location?->getCantonLocation());
$curDistrict = $posted('district', $location?->getDistrictLocation());
$curTown     = $posted('town', $location?->getTownLocation());
$curApprox   = $posted('approximate', $location?->getApproximateLocation());
$curDesc     = $posted('description', $location?->getDescriptionLocation());
$curLat      = $posted('latitude', $location?->getLatitudeLocation() !== null ? (string) $location->getLatitudeLocation() : '');
$curLng      = $posted('longitude', $location?->getLongitudeLocation() !== null ? (string) $location->getLongitudeLocation() : '');
?>

<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/'); ?>
<link rel="stylesheet" href="<?= e($basePath . '/vendor/leaflet/leaflet.css') ?>">
<script src="<?= e($basePath . '/vendor/leaflet/leaflet.js') ?>"></script>

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
        data-validate="required"
        value="<?= e($isEdit ? $venue->getNameVenue() : ($_POST['name'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="type">Tipo de local *</label>
      <input class="form-control" type="text" id="type" name="type" required
        data-validate="required"
        placeholder="Ej: Salón de eventos, Restaurante, Jardín..."
        value="<?= e($isEdit && $venue->getTypeVenue() !== '' ? $venue->getTypeVenue() : ($_POST['type'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="capacity">Capacidad *</label>
      <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*" id="capacity" name="capacity" required
        data-validate="required positive"
        value="<?= e($isEdit ? (string) $venue->getCapacityVenue() : ($_POST['capacity'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="price">Precio de renta por evento *</label>
      <input class="form-control no-spinners" type="text" inputmode="decimal" id="price" name="price" required
        data-currency-mask data-validate="required positive"
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

    <div class="form-group location-mode">
      <span class="form-label">Tipo de ubicación *</span>
      <div class="radio-list">
        <label class="radio-option">
          <input type="radio" name="locationType" value="aproximada" data-location-mode
            <?= $curType === 'aproximada' ? 'checked' : '' ?>>
          <span>Asignar ubicación aproximada</span>
        </label>
        <label class="radio-option">
          <input type="radio" name="locationType" value="exacta" data-location-mode
            <?= $curType === 'exacta' ? 'checked' : '' ?>>
          <span>Asignar ubicación exacta</span>
        </label>
      </div>
      <p class="form-hint">La ubicación aproximada solo pide barrio y una referencia; la exacta permite elegir provincia, cantón, distrito y marcar el punto en el mapa.</p>
    </div>

    <div data-location-section="exacta" <?= $curType === 'aproximada' ? 'hidden' : '' ?>>
      <div class="grid grid-3">
        <div class="form-group">
          <label for="province">Provincia *</label>
          <select class="form-control" id="province" name="province" data-level="province"
                  data-validate="required" data-value="<?= e($curProvince) ?>">
            <option value="">— Selecciona —</option>
          </select>
        </div>
        <div class="form-group">
          <label for="canton">Cantón *</label>
          <select class="form-control" id="canton" name="canton" data-level="canton" data-validate="required" disabled
                  data-value="<?= e($curCanton) ?>">
            <option value="">— Selecciona —</option>
          </select>
        </div>
        <div class="form-group">
          <label for="district">Distrito *</label>
          <select class="form-control" id="district" name="district" data-level="district" data-validate="required" disabled
                  data-value="<?= e($curDistrict) ?>">
            <option value="">— Selecciona —</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="venue-map">Ubicación exacta en el mapa</label>
        <div class="geo-search-box">
          <input class="form-control" type="search" id="venue-map-search"
            placeholder="Buscar dirección o cantón (ej. San José)...">
          <ul class="geo-search-results" id="venue-map-results"></ul>
        </div>
        <div id="venue-map"></div>
        <span id="geo-display" class="geo-coords <?= $curLat !== '' && $curLng !== '' ? '' : 'muted' ?>">
          <?php if ($curLat !== '' && $curLng !== ''): ?>
            Lat <?= e($curLat) ?> · Lon <?= e($curLng) ?>
          <?php else: ?>
            Marca el punto en el mapa
          <?php endif; ?>
        </span>
        <input type="hidden" name="latitude" id="latitude" value="<?= e($curLat) ?>">
        <input type="hidden" name="longitude" id="longitude" value="<?= e($curLng) ?>">
        <p class="form-hint">Opcional: haz clic en el mapa o arrastra el marcador para marcar la ubicación del local.</p>
      </div>
    </div>

    <div data-location-section="aproximada" <?= $curType === 'exacta' ? 'hidden' : '' ?>>
      <div class="form-group">
        <label for="town">Barrio *</label>
        <input class="form-control" type="text" id="town" name="town"
          data-validate="required" maxlength="100"
          <?= $curType === 'aproximada' ? 'required' : '' ?>
          value="<?= e($curTown) ?>">
      </div>
      <div class="form-group">
        <label for="approximate">Ubicación aproximada *</label>
        <input class="form-control" type="text" id="approximate" name="approximate"
          data-validate="required" maxlength="300"
          <?= $curType === 'aproximada' ? 'required' : '' ?>
          placeholder="Ej: Barrio El Carmen, 200 m norte del parque central"
          value="<?= e($curApprox) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="description">Otras señas de la ubicación</label>
      <textarea class="form-control" id="description" name="description" rows="3" maxlength="300"
        placeholder="Ej: frente al parque, portón azul, 200 m norte de..."><?= e($curDesc) ?></textarea>
    </div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear local' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
