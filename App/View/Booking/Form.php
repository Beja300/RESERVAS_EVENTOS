<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Reservar: <?= e($venue->getNameVenue()) ?></h1>
    <a href="<?= e(base_url('venue', 'detail', ['id' => $venue->getIdVenue()])) ?>">&larr; Volver al local</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('booking', 'create')) ?>">
    <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">

    <div class="form-group">
      <label for="date">Fecha del evento *</label>
      <input class="form-control" type="date" id="date" name="date" required min="<?= date('Y-m-d') ?>"
             value="<?= e($_POST['date'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="eventType">Tipo de evento</label>
      <select class="form-control" id="eventType" name="eventType">
        <option value="">— Selecciona —</option>
        <option value="boda" <?= ($_POST['eventType'] ?? '') === 'boda' ? 'selected' : '' ?>>Boda</option>
        <option value="cumpleanos" <?= ($_POST['eventType'] ?? '') === 'cumpleanos' ? 'selected' : '' ?>>Cumpleaños</option>
        <option value="empresarial" <?= ($_POST['eventType'] ?? '') === 'empresarial' ? 'selected' : '' ?>>Empresarial</option>
        <option value="otro" <?= ($_POST['eventType'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
      </select>
    </div>

    <?php if (!empty($services)): ?>
      <p style="font-weight:700;color:var(--neutral-800);margin-bottom:8px;">Servicios disponibles</p>
      <p class="form-hint" style="margin-bottom:14px;">Podrás añadir servicios después de crear la reserva.</p>
    <?php endif; ?>

    <button class="btn btn-primary btn-block" type="submit">Crear reserva</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
