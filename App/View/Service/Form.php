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
      <label for="name">Nombre del servicio *</label>
      <input class="form-control" type="text" id="name" name="name" required
             value="<?= e($isEdit ? $service->getNameService() : ($_POST['name'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="type">Tipo</label>
      <input class="form-control" type="text" id="type" name="type"
             placeholder="Ej: Catering, DJ, Decoración..."
             value="<?= e($isEdit && $service->getTypeService() !== '' ? $service->getTypeService() : ($_POST['type'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="price">Precio *</label>
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

<script>
  (function () {
    function base() { var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/'); }
    var form = document.querySelector('form[data-ajax-service-form]');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch(form.getAttribute('action'), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: new FormData(form)
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
          .then(function (r) {
            window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
            if (r.ok) {
              var venueIdInput = form.querySelector('[name="venueId"]');
              var vid = venueIdInput ? venueIdInput.value : '';
              setTimeout(function () { window.location.href = base() + '/index.php?controller=service&action=list&venueId=' + vid; }, 700);
            }
          });
      });
    }
  })();
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
