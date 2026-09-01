<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Nueva promoción</h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Mis locales</a>
  </div>
</div>

<div class="card form-card" style="max-width:520px;">
  <?php if (isset($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($venues)): ?>
    <div class="alert alert-warning">Primero crea un local para poder gestionar promociones.</div>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('promotion', 'create')) ?>">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="venueId">Local *</label>
        <select class="form-control" id="venueId" name="venueId" required>
          <option value="">— Selecciona —</option>
          <?php foreach ($venues as $v): ?>
            <option value="<?= (int) $v->getIdVenue() ?>" <?= (int) $idVenue === (int) $v->getIdVenue() ? 'selected' : '' ?>>
              <?= e($v->getNameVenue()) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="label">Etiqueta *</label>
        <input class="form-control" type="text" id="label" name="label" required placeholder="Ej. 2x1 en sonido">
      </div>

      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div class="form-group">
          <label for="startDate">Fecha inicio</label>
          <input class="form-control" type="date" id="startDate" name="startDate">
        </div>
        <div class="form-group">
          <label for="endDate">Fecha fin</label>
          <input class="form-control" type="date" id="endDate" name="endDate">
        </div>
      </div>

      <div class="form-group">
        <label for="minServices">Mínimo de servicios *</label>
        <input class="form-control" type="number" id="minServices" name="minServices" value="1" min="1" required>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Crear promoción</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
