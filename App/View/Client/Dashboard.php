<?php $pageJs = ['client/dashboard']; ?>
<?php require_once __DIR__ . '/../_header.php';
$client = $_SESSION['user'] ?? null;
?>

<div class="page-head">
  <div>
    <h1>Hola, <?= e($client ? $client->getName() : '') ?> &#128075;</h1>
    <p class="muted">Tu panel de alquileres</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">+ Reservar local</a>
</div>

<div id="geo-module"
     data-has-location="<?= $hasLocation ? '1' : '0' ?>"
     data-geo-url="<?= e(base_url('api', 'geolocate')) ?>"
     data-save-url="<?= e(base_url('client', 'updateLocation')) ?>"
     data-csrf="<?= e(csrf_token()) ?>">
  <div class="card" style="padding:12px 16px;margin-bottom:22px;">
    <p style="margin:0;" class="muted">&#128205;
      <?php if (!$hasLocation): ?>
        Te ayudaremos a detectar tu ubicación para recomendarte locales cerca de ti en
        <a href="<?= e(base_url('client', 'recommendations')) ?>">Recomendaciones</a>.
      <?php else: ?>
        Usamos tu ubicación para recomendarte locales cerca de ti en
        <a href="<?= e(base_url('client', 'recommendations')) ?>">Recomendaciones</a>.
      <?php endif; ?>
    </p>
  </div>
</div>

<div class="page-head">
  <div>
    <h2 style="font-size:1.15rem;color:var(--neutral-700);">Locales alquilados</h2>
    <p class="muted">Tus últimas reservas</p>
  </div>
  <a href="<?= e(base_url('booking', 'myBookings')) ?>">Ver todas &rarr;</a>
</div>
<?php if (empty($bookings)): ?>
  <div class="card empty">
    <span class="emoji">&#128197;</span>
    Aún no has alquilado locales.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach (array_slice($bookings, 0, 5) as $b): ?>
      <div class="card">
        <div class="list-item" style="margin-bottom:0;box-shadow:none;border:none;padding:0;">
          <div>
            <div class="title"><?= e($venueNames[$b->getIdLocal()] ?? 'Local #' . (int) $b->getIdLocal()) ?></div>
            <div class="desc">
              <?php
                $dateCell = date('d/m/Y', strtotime($b->getBookingDate()));
                if ($b->getBookingEndDate() !== null) {
                  $dateCell .= ' — ' . date('d/m/Y', strtotime($b->getBookingEndDate()));
                }
              ?>
              <?= e($dateCell) ?>
            </div>
          </div>
          <?php
            $stateBadge = [
              'pendiente' => 'warning',
              'confirmado' => 'success',
              'cancelado' => 'neutral',
              'rechazado' => 'danger',
            ][$b->getBookingState()] ?? 'neutral';
          ?>
          <span class="badge <?= $stateBadge ?>"><?= e($b->getBookingState()) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 style="font-size:1.15rem;color:var(--neutral-700);margin-top:28px;">Locales más frecuentes</h2>
<p class="muted" style="margin-bottom:14px;">Los que más has revisado.</p>
<?php if (empty($frequentVenues)): ?>
  <div class="card empty">
    <span class="emoji">&#128269;</span>
    Explora locales en el catálogo: los que más revises aparecerán aquí.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($frequentVenues as $v): ?>
      <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <?php if ($v->getImageVenue() !== ''): ?>
            <img src="<?= e(image_url($v->getImageVenue())) ?>" alt="Foto de <?= e($v->getNameVenue()) ?>"
              style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
          <?php else: ?>
            <div style="height:110px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:2.4rem;margin-bottom:10px;"><span>&#127968;</span></div>
          <?php endif; ?>
          <h3 style="color:var(--neutral-900);margin-bottom:6px;"><?= e($v->getNameVenue()) ?></h3>
          <p class="muted">Capacidad: <?= (int) $v->getCapacityVenue() ?></p>
          <?php if (isset($locationByVenue[$v->getIdVenue()]) && $locationByVenue[$v->getIdVenue()] !== null): ?>
            <p class="muted">&#128205; <?= format_venue_location($locationByVenue[$v->getIdVenue()]) ?></p>
          <?php endif; ?>
          <span class="badge success"><?= (int) $visitCountByVenue[$v->getIdVenue()] ?> <?= (int) $visitCountByVenue[$v->getIdVenue()] === 1 ? 'visita' : 'visitas' ?></span>
        </div>
        <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'detail', ['id' => $v->getIdVenue()])) ?>">Ver local</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>