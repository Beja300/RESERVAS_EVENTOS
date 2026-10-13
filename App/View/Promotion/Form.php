<?php
require_once __DIR__ . '/../_header.php';

$isEdit = isset($promotion) && $promotion !== null;
$idVenue = $idVenue ?? (int) ($_GET['venueId'] ?? 0);
$error = $error ?? null;

// Valor de un campo: prioriza lo reenviado por POST (para no perder lo
// escrito cuando falla la validación) y si no, el dato existente.
$value = static function (string $key, $fallback = '') {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    return $fallback;
};
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar promoción' : 'Nueva promoción' ?></h1>
    <a href="<?= e(base_url('promotion', 'list', ['venueId' => $idVenue])) ?>">&larr; Volver a promociones</a>
  </div>
</div>

<div class="card form-card" style="max-width:520px;">
  <?php if ($error !== null): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($venues)): ?>
    <div class="alert alert-warning">Primero crea un local para poder gestionar promociones.</div>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('promotion', $isEdit ? 'update' : 'create')) ?>" data-validate-form>
      <?= csrf_field() ?>
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $promotion->getIdPromotion() ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="venueId">Local <span class="required-mark">*</span></label>
        <select class="form-control" id="venueId" name="venueId" required data-validate="required">
          <option value="">— Selecciona —</option>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int) $v->getIdVenue() ?>" <?= (int) $idVenue === (int) $v->getIdVenue() ? 'selected' : '' ?>>
              <?= e($v->getNameVenue()) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="label">Etiqueta <span class="required-mark">*</span></label>
        <input class="form-control" type="text" id="label" name="label" required maxlength="100"
               data-validate="required" placeholder="Ej. 2x1 en sonido"
               value="<?= e($value('label', $isEdit ? $promotion->getLabel() : '')) ?>">
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?= e($value('description', $isEdit ? $promotion->getDescription() : '')) ?></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="form-group">
          <label for="startDate">Fecha inicio</label>
          <input class="form-control" type="date" id="startDate" name="startDate"
                 value="<?= e($value('startDate', $isEdit ? (string) $promotion->getStartDate() : '')) ?>">
        </div>
        <div class="form-group">
          <label for="endDate">Fecha fin</label>
          <input class="form-control" type="date" id="endDate" name="endDate"
                 value="<?= e($value('endDate', $isEdit ? (string) $promotion->getEndDate() : '')) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="minServices">Mínimo de servicios <span class="required-mark">*</span></label>
        <input class="form-control" type="number" id="minServices" name="minServices" min="1" required
               data-validate="positive"
               value="<?= e($value('minServices', $isEdit ? $promotion->getMinServices() : 1)) ?>">
      </div>

      <?php if ($isEdit): ?>
        <div class="form-group">
          <label>
            <input type="checkbox" name="active" value="1" <?= $promotion->getIsActive() ? 'checked' : '' ?>>
            Promoción activa
          </label>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary btn-block" type="submit">
        <?= $isEdit ? 'Guardar cambios' : 'Crear promoción' ?>
      </button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
