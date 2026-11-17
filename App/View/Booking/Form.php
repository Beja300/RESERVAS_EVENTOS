<?php
$pageCss = 'booking/form';
$pageJs = ['booking/form'];

require_once __DIR__ . '/../_header.php';
if ($venue === null) {
  echo '<div class="alert alert-error">Local no encontrado.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1>Reservar: <?= e($venue->getNameVenue()) ?></h1>
    <a href="<?= e(base_url('venue', 'detail', ['id' => $venue->getIdVenue()])) ?>">&larr; Volver al local</a>
  </div>
</div>

<div class="card form-card form-page">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('booking', 'create')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">

    <div class="form-grid-2">
      <div class="form-group">
        <label for="date">Fecha de inicio <span class="required-mark">*</span></label>
        <input class="form-control" type="date" id="date" name="date" required
               min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>"
               value="<?= e(trim($_POST['date'] ?? '')) ?>"
               data-booked-dates='<?= e(json_encode($bookedDates ?? [])) ?>'>
      </div>
      <div class="form-group">
        <label for="endDate">Fecha final <span class="required-mark">*</span></label>
        <input class="form-control" type="date" id="endDate" name="endDate" required
               min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>"
               value="<?= e(trim($_POST['endDate'] ?? '')) ?>">
      </div>
    </div>
    <p class="datepicker-error" id="bookingDateError" hidden></p>
    <p class="form-hint">Elige el día de inicio y luego el día final. El precio se acumula por día.</p>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="attendees">Cantidad de asistentes <span class="required-mark">*</span></label>
        <input class="form-control no-spinners" type="text" inputmode="numeric" pattern="[0-9]*"
               id="attendees" name="attendees" required
               min="<?= (int) ($venue->getMinCapacity() ?? 1) ?>"
               max="<?= (int) $venue->getCapacityVenue() ?>"
               value="<?= e(trim($_POST['attendees'] ?? '')) ?>">
        <p class="form-hint">
          Capacidad: <?= (int) ($venue->getMinCapacity() ?? 1) ?> a <?= (int) $venue->getCapacityVenue() ?> personas.
        </p>
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
    </div>

    <?php $rentalBlocks = $rentalBlocks ?? []; ?>
    <?php if (!empty($rentalBlocks)): ?>
      <div class="form-group">
        <label for="rentalBlockId">Bloque de alquiler</label>
        <select class="form-control" id="rentalBlockId" name="rentalBlockId" data-rental-block>
          <option value="" data-price="<?= e($venue->getPriceVenue()) ?>">Precio general — &#8353; <?= number_format($venue->getPriceVenue(), 2) ?></option>
          <?php foreach ($rentalBlocks as $block): ?>
            <option value="<?= (int) $block->getIdBlock() ?>" data-price="<?= e($block->getPrice()) ?>"
              <?= (string) ($_POST['rentalBlockId'] ?? '') === (string) $block->getIdBlock() ? 'selected' : '' ?>>
              <?= e($block->getName()) ?> (<?= e(substr($block->getStartTime(), 0, 5)) ?>–<?= e(substr($block->getEndTime(), 0, 5)) ?>) — &#8353; <?= number_format($block->getPrice(), 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group" id="eventDetailGroup" style="display:<?= ($_POST['eventType'] ?? '') === 'otro' ? '' : 'none' ?>;">
      <label for="eventDetail">¿De qué trata tu evento? <span class="required-mark">*</span></label>
      <textarea class="form-control" id="eventDetail" name="eventDetail" rows="3" maxlength="255"
                placeholder="Cuéntale al propietario de qué trata tu evento..."><?= e($_POST['eventDetail'] ?? '') ?></textarea>
      <small class="muted">Esta descripción la verá el propietario del local.</small>
    </div>

    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--neutral-200);">
      <p style="font-weight:700;color:var(--neutral-900);" data-venue-price="<?= e($venue->getPriceVenue()) ?>">
        Precio por día: &#8353; <?= number_format($venue->getPriceVenue(), 2) ?>
      </p>
      <p class="form-hint" id="bookingPriceSummary">
        El precio se acumula por día (del 10 al 12 = 3 días). El total mostrará el precio × días, los servicios y el IVA.
      </p>
    </div>

    <button class="btn btn-primary btn-block" type="submit">Crear reserva</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
