<?php $pageJs = ['client/dashboard']; ?>
<?php require_once __DIR__ . '/../_header.php';
$client = $_SESSION['user'] ?? null;
?>
<div class="page-head">
  <div>
    <h1>Mis favoritos</h1>
    <p class="muted">Locales que has marcado con &#9829;</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">Explorar locales</a>
</div>

<?php if (empty($favoriteVenues)): ?>
  <div class="card empty">
    <span class="emoji">&#9825;</span>
    Aún no tienes locales favoritos. Marca con el coraz&oacute;n los locales que te gusten.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($favoriteVenues as $v): ?>
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
          <?php if (isset($ratingsByVenue[$v->getIdVenue()])): ?>
            <p class="muted"><span class="rating-stars"><?= str_repeat('&#9733;', (int) round($ratingsByVenue[$v->getIdVenue()])) . str_repeat('&#9734;', 5 - (int) round($ratingsByVenue[$v->getIdVenue()])) ?></span> <?= number_format($ratingsByVenue[$v->getIdVenue()], 1) ?> / 5</p>
          <?php else: ?>
            <p class="muted">Sin calificaciones</p>
          <?php endif; ?>
          <?php if (isset($locationByVenue[$v->getIdVenue()]) && $locationByVenue[$v->getIdVenue()] !== null): ?>
            <p class="muted">&#128205; <?= format_venue_location($locationByVenue[$v->getIdVenue()]) ?></p>
          <?php endif; ?>
        </div>
        <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'detail', ['id' => $v->getIdVenue()])) ?>">Ver local</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>