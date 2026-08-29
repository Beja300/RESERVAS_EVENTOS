<?php require_once __DIR__ . '/../_header.php';

$isEdit = $venue !== null;
$action = $isEdit ? base_url('venue', 'update') : base_url('venue', 'create');
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

  <form method="post" action="<?= e($action) ?>">
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
      <label for="image">Imagen (URL)</label>
      <input class="form-control" type="text" id="image" name="image"
        placeholder="https://..."
        value="<?= e($isEdit && $venue->getImageVenue() !== '' ? $venue->getImageVenue() : ($_POST['image'] ?? '')) ?>">
    </div>

    <?php if (!$isEdit): ?>
      <hr style="border:none;border-top:1px solid var(--neutral-200);margin:18px 0;">
      <p style="font-weight:700;color:var(--neutral-800);margin-bottom:14px;">Ubicación</p>

      <div class="grid grid-3">
        <div class="form-group">
          <label for="province">Provincia *</label>
          <input class="form-control" type="text" id="province" name="province" required
            value="<?= e($_POST['province'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="canton">Cantón *</label>
          <input class="form-control" type="text" id="canton" name="canton" required
            value="<?= e($_POST['canton'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="district">Distrito *</label>
          <input class="form-control" type="text" id="district" name="district" required
            value="<?= e($_POST['district'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="town">Pueblo</label>
        <input class="form-control" type="text" id="town" name="town"
          value="<?= e($_POST['town'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="description">Descripción</label>
        <input class="form-control" type="text" id="description" name="description"
          value="<?= e($_POST['description'] ?? '') ?>">
      </div>
    <?php else: ?>
      <div class="checkbox-row">
        <input type="checkbox" id="active" name="active" <?= $venue->getIsActive() ? 'checked' : '' ?>>
        <label for="active">Local activo (visible en el catálogo)</label>
      </div>
    <?php endif; ?>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear local' ?></button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
