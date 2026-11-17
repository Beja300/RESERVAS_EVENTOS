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

$curProvince = $posted('province', $location?->getProvinceLocation());
$curCanton   = $posted('canton', $location?->getCantonLocation());
$curDistrict = $posted('district', $location?->getDistrictLocation());
$curDesc     = $posted('description', $location?->getDescriptionLocation());
$curLat      = $posted('latitude', $location?->getLatitudeLocation() !== null ? (string) $location->getLatitudeLocation() : '');
$curLng      = $posted('longitude', $location?->getLongitudeLocation() !== null ? (string) $location->getLongitudeLocation() : '');
$curVenueDescription = $posted('venueDescription', $venue?->getDescriptionVenue());
$curSize     = $posted('size', $venue?->getSizeVenue());
$curBathrooms = $posted('bathrooms', $venue?->getBathroomsVenue() !== null ? (string) $venue->getBathroomsVenue() : '');
$curParking  = array_key_exists('parking', $_POST) ? isset($_POST['parking']) : (bool) ($venue?->getHasParking() ?? false);
$curParkingCapacity = $posted('parkingCapacity', $venue?->getParkingCapacity() !== null ? (string) $venue->getParkingCapacity() : '');
$curMinCapacity = $posted('minCapacity', $venue?->getMinCapacity() !== null ? (string) $venue->getMinCapacity() : '');
$curCleaningFee = $posted('cleaningFee', $venue?->getCleaningFee() !== null ? number_format($venue->getCleaningFee(), 2, '.', '') : '');
$curCheckInTime = $posted('checkInTime', $venue?->getCheckInTime());
$curCheckOutTime = $posted('checkOutTime', $venue?->getCheckOutTime());
$images = $images ?? [];
$blocks = $blocks ?? [];
?>

<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/'); ?>
<link rel="stylesheet" href="<?= e($basePath . '/vendor/leaflet/leaflet.css') ?>">
<script src="<?= e($basePath . '/vendor/leaflet/leaflet.js') ?>"></script>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar local — ' . e($venue->getNameVenue()) : 'Nuevo local' ?></h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Volver a mis locales</a>
  </div>
</div>

<div class="card form-card venue-form form-page">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e($action) ?>" data-ajax-venue-form>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="idVenue" value="<?= (int) $venue->getIdVenue() ?>">
    <?php endif; ?>

    <div class="form-grid-2">
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
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="minCapacity">Capacidad mínima</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*" id="minCapacity" name="minCapacity"
          value="<?= e($curMinCapacity) ?>">
      </div>
      <div class="form-group">
        <label for="capacity">Capacidad máxima *</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*" id="capacity" name="capacity" required
          data-validate="required positive"
          value="<?= e($isEdit ? (string) $venue->getCapacityVenue() : ($_POST['capacity'] ?? '')) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="price">Precio de renta por evento *</label>
      <input class="form-control no-spinners" type="text" inputmode="decimal" id="price" name="price" required
        data-currency-mask data-validate="required positive"
        value="<?= e($isEdit ? number_format($venue->getPriceVenue(), 2, '.', '') : ($_POST['price'] ?? '')) ?>">
      <p class="form-hint">Este precio se incluye siempre en la factura de cada reserva de este local.</p>
    </div>

    <div class="form-grid-3">
      <div class="form-group">
        <label for="size">Tamaño</label>
        <input class="form-control" type="text" id="size" name="size" maxlength="80"
          placeholder="Ej: 250 m²" value="<?= e($curSize) ?>">
      </div>
      <div class="form-group">
        <label for="bathrooms">Baños</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
          id="bathrooms" name="bathrooms" value="<?= e($curBathrooms) ?>">
      </div>
      <div class="form-group">
        <label for="cleaningFee">Costo de limpieza</label>
        <input class="form-control no-spinners" type="text" inputmode="decimal" id="cleaningFee" name="cleaningFee"
          data-currency-mask value="<?= e($curCleaningFee) ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Horario general de uso</label>
      <div class="form-grid-2">
        <div>
          <label for="checkInTime">Hora de entrada</label>
          <input class="form-control" type="time" id="checkInTime" name="checkInTime" value="<?= e($curCheckInTime) ?>">
        </div>
        <div>
          <label for="checkOutTime">Hora de salida</label>
          <input class="form-control" type="time" id="checkOutTime" name="checkOutTime" value="<?= e($curCheckOutTime) ?>">
        </div>
      </div>
      <p class="form-hint">Se mostrará cuando la reserva sea de día completo y no use un bloque horario.</p>
    </div>

    <div class="parking-row">
      <div class="parking-check">
        <div class="checkbox-row">
          <input type="checkbox" id="parking" name="parking" value="1" <?= $curParking ? 'checked' : '' ?>>
          <label for="parking">¿El local cuenta con parqueo?</label>
        </div>
        <p class="form-hint">Marca la casilla si el local tiene parqueo; desmárcala si no.</p>
      </div>
      <div class="form-group" id="parking-capacity-wrap" <?= $curParking ? '' : 'hidden' ?>>
        <label for="parkingCapacity">Capacidad de parqueo</label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
          id="parkingCapacity" name="parkingCapacity" value="<?= e($curParkingCapacity) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="venueDescription">Descripción del local</label>
      <textarea class="form-control" id="venueDescription" name="venueDescription" rows="3" maxlength="500"
        placeholder="Describe el espacio, amenidades, ambiente..."><?= e($curVenueDescription) ?></textarea>
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

    <div class="form-group">
      <label for="gallery">Galería del local</label>
      <input class="form-control" type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
      <p class="form-hint">Puedes subir varias imágenes adicionales.</p>
      <?php if (!empty($images)): ?>
        <div class="actions" style="flex-wrap:wrap;margin-top:10px;">
          <?php foreach ($images as $image): ?>
            <label style="display:flex;flex-direction:column;gap:4px;align-items:center;font-size:.8rem;">
              <img src="<?= e(image_url($image->getPath())) ?>" alt="Imagen del local"
                style="width:110px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--neutral-200);">
              <span><input type="checkbox" name="removeGallery[]" value="<?= (int) $image->getIdImage() ?>"> Quitar</span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <hr style="border:none;border-top:1px solid var(--neutral-200);margin:18px 0;">
    <p style="font-weight:700;color:var(--neutral-800);margin-bottom:14px;">Bloques de alquiler</p>
    <p class="form-hint" style="margin-bottom:12px;">Opcional: define bloques horarios con su propio precio. Si no hay bloques, se usa el precio de renta general.</p>

    <div id="rental-blocks" style="display:grid;gap:10px;margin-bottom:12px;">
      <?php foreach ($blocks as $block): ?>
        <div class="grid" style="grid-template-columns:1.4fr .8fr .8fr 1fr auto;gap:8px;align-items:end;" data-block-row>
          <input type="hidden" name="blockId[]" value="<?= (int) $block->getIdBlock() ?>">
          <div class="form-group" style="margin:0;">
            <label>Nombre</label>
            <input class="form-control" type="text" name="blockName[]" value="<?= e($block->getName()) ?>" placeholder="Ej: Mañana">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Inicio</label>
            <input class="form-control" type="time" name="blockStart[]" value="<?= e(substr($block->getStartTime(), 0, 5)) ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Fin</label>
            <input class="form-control" type="time" name="blockEnd[]" value="<?= e(substr($block->getEndTime(), 0, 5)) ?>">
          </div>
          <div class="form-group" style="margin:0;">
            <label>Precio</label>
            <input class="form-control no-spinners" type="text" inputmode="decimal" name="blockPrice[]" data-currency-mask value="<?= e(number_format($block->getPrice(), 2, '.', '')) ?>">
          </div>
          <button class="btn btn-sm btn-ghost" type="button" data-remove-block>Quitar</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-outline" type="button" id="add-block">+ Agregar bloque</button>

    <?php if ($isEdit): ?>
      <div class="checkbox-row" style="margin:18px 0;">
        <input type="checkbox" id="active" name="active" <?= $venue->getIsActive() ? 'checked' : '' ?>>
        <label for="active">Local activo (visible en el catálogo)</label>
      </div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid var(--neutral-200);margin:18px 0;">
    <p style="font-weight:700;color:var(--neutral-800);margin-bottom:14px;">Ubicación</p>

    <input type="hidden" name="locationType" value="exacta">

    <div class="form-grid-3">
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
      <label for="venue-map">Ubicación en el mapa (opcional)</label>
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
      <p class="form-hint">Opcional: haz clic en el mapa o arrastra el marcador para marcar la ubicación del local. Si no marcas coordenadas, el local se guarda igual.</p>
    </div>

    <div class="form-group">
      <label for="description">Otras señas de la ubicación *</label>
      <textarea class="form-control" id="description" name="description" rows="3" maxlength="300"
        data-validate="required" required
        placeholder="Ej: frente al parque, portón azul, 200 m norte de..."><?= e($curDesc) ?></textarea>
    </div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear local' ?></button>
  </form>
</div>

<script>
(function () {
  var container = document.getElementById('rental-blocks');
  var add = document.getElementById('add-block');
  if (!container || !add) return;

  function rowHtml() {
    return '<div class="grid" style="grid-template-columns:1.4fr .8fr .8fr 1fr auto;gap:8px;align-items:end;" data-block-row>' +
      '<input type="hidden" name="blockId[]" value="0">' +
      '<div class="form-group" style="margin:0;"><label>Nombre</label><input class="form-control" type="text" name="blockName[]" placeholder="Ej: Mañana"></div>' +
      '<div class="form-group" style="margin:0;"><label>Inicio</label><input class="form-control" type="time" name="blockStart[]"></div>' +
      '<div class="form-group" style="margin:0;"><label>Fin</label><input class="form-control" type="time" name="blockEnd[]"></div>' +
      '<div class="form-group" style="margin:0;"><label>Precio</label><input class="form-control no-spinners" type="text" inputmode="decimal" name="blockPrice[]" data-currency-mask></div>' +
      '<button class="btn btn-sm btn-ghost" type="button" data-remove-block>Quitar</button></div>';
  }

  add.addEventListener('click', function () {
    container.insertAdjacentHTML('beforeend', rowHtml());
    var lastRow = container.lastElementChild;
    var priceInput = lastRow ? lastRow.querySelector('[data-currency-mask]') : null;
    if (priceInput && window.VenueFormCurrency) window.VenueFormCurrency.bind(priceInput);
  });

  container.addEventListener('click', function (e) {
    var button = e.target.closest('[data-remove-block]');
    if (!button) return;
    var row = button.closest('[data-block-row]');
    if (row) row.remove();
  });
})();
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
