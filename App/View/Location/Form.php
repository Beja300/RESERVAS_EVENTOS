<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Nueva ubicación</h1>
    <a href="<?= e(base_url('location', 'list')) ?>">&larr; Volver a ubicaciones</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('location', 'create')) ?>">
    <?= csrf_field() ?>
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

    <div class="grid grid-2">
      <div class="form-group">
        <label for="town">Pueblo</label>
        <input class="form-control" type="text" id="town" name="town"
               placeholder="Pueblo o comunidad"
               value="<?= e($_POST['town'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="description">Descripción</label>
        <input class="form-control" type="text" id="description" name="description"
               placeholder="Otras señas de la ubicación"
               value="<?= e($_POST['description'] ?? '') ?>">
      </div>
    </div>

    <button class="btn btn-primary" type="submit">Crear ubicación</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
