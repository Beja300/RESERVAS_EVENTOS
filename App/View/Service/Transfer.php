<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Mover o duplicar servicio</h1>
    <a href="<?= e(base_url('service', 'groups')) ?>">&larr; Volver a servicios existentes</a>
  </div>
</div>

<div class="card form-card form-page">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if ($service !== null): ?>
    <p style="margin-bottom:16px;">
      Servicio: <strong><?= e($service->getNameService()) ?></strong><br>
      Local actual: <strong><?= e($currentVenue?->getNameVenue() ?? '—') ?></strong>
    </p>
  <?php endif; ?>

  <?php if (empty($venues)): ?>
    <div class="alert alert-error">No tienes otro local activo disponible.</div>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('service', 'moveOrDuplicate')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="idService" value="<?= (int) ($service?->getIdService() ?? 0) ?>">

      <div class="form-group">
        <label for="targetVenueId">Local de destino *</label>
        <select class="form-control" id="targetVenueId" name="targetVenueId" required>
          <option value="">— Selecciona —</option>
          <?php foreach ($venues as $venue): ?>
            <option value="<?= (int) $venue->getIdVenue() ?>"><?= e($venue->getNameVenue()) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Acción *</label>
        <div class="radio-list">
          <label class="radio-option">
            <input type="radio" name="mode" value="duplicate" checked>
            <span>Duplicar: conserva el servicio actual y crea una copia en el destino.</span>
          </label>
          <label class="radio-option">
            <input type="radio" name="mode" value="move">
            <span>Mover: traslada el servicio al destino y desaparece del local actual.</span>
          </label>
        </div>
      </div>

      <button class="btn btn-primary" type="submit">Confirmar</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
