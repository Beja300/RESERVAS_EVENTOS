<?php require_once __DIR__ . '/../_header.php';
$group = $context['group'];
$servicesByVenue = $context['servicesByVenue'];
$venues = $context['venues'];
?>

<div class="page-head">
  <div>
    <h1>Locales de <?= e($group->getName()) ?></h1>
    <a href="<?= e(base_url('service', 'groups')) ?>">&larr; Volver a servicios existentes</a>
  </div>
</div>

<div class="card form-card form-page">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <p class="muted" style="margin-bottom:14px;">
    Marca los locales activos donde se ofrece este servicio. Al desmarcar un local,
    el servicio se desactiva allí pero conserva su historial y puede reactivarse después.
  </p>

  <?php if (empty($venues)): ?>
    <div class="alert alert-error">No tienes locales activos.</div>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('service', 'updateLocations')) ?>" data-multi-venue-form>
      <?= csrf_field() ?>
      <input type="hidden" name="groupId" value="<?= (int) $group->getIdGroup() ?>">

      <div class="checkbox-row" style="margin-bottom:12px;">
        <input type="checkbox" id="locationsAll" data-check-all-venues>
        <label for="locationsAll"><strong>Seleccionar todos los locales activos</strong></label>
      </div>

      <div class="form-grid-3" style="margin-bottom:18px;">
        <?php foreach ($venues as $venue): ?>
          <?php
            $service = $servicesByVenue[$venue->getIdVenue()] ?? null;
            $checked = $service !== null && $service->getIsActive();
          ?>
          <div class="card" style="padding:12px 14px;">
            <label class="radio-option" style="justify-content:flex-start;">
              <input type="checkbox" name="venueIds[]" value="<?= (int) $venue->getIdVenue() ?>"
                     data-venue-checkbox <?= $checked ? 'checked' : '' ?>>
              <span>
                <strong><?= e($venue->getNameVenue()) ?></strong>
                <?php if ($service !== null && !$service->getIsActive()): ?>
                  <br><span class="badge neutral">Actualmente inactivo aquí</span>
                <?php endif; ?>
              </span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>

      <button class="btn btn-primary" type="submit">Guardar locales</button>
    </form>
  <?php endif; ?>
</div>

<script>
(function () {
  var all = document.querySelector('[data-check-all-venues]');
  var boxes = document.querySelectorAll('[data-venue-checkbox]');
  if (all) {
    all.addEventListener('change', function () {
      boxes.forEach(function (box) { box.checked = all.checked; });
    });
  }
})();
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
