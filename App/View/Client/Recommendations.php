<?php $pageJs = ['client/dashboard']; ?>
<?php require_once __DIR__ . '/../_header.php';
$client = $_SESSION['user'] ?? null;
?>

<div class="page-head">
  <div>
    <h1>Recomendaciones</h1>
    <p class="muted">Locales pensados para ti</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">Explorar locales</a>
</div>

<h2 style="font-size:1.15rem;color:var(--neutral-700);margin-bottom:14px;">Recomendados para ti</h2>
<?php if (empty($recommendations)): ?>
  <div class="card empty">
    <span class="emoji">&#128269;</span>
    Explora el catálogo para descubrir locales.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($recommendations as $r): ?>
      <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <?php if ($r->getImageVenue() !== ''): ?>
            <img src="<?= e(image_url($r->getImageVenue())) ?>" alt="Foto de <?= e($r->getNameVenue()) ?>"
              style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
          <?php else: ?>
            <div style="height:110px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:2.4rem;margin-bottom:10px;"><span>&#127968;</span></div>
          <?php endif; ?>
          <h3 style="color:var(--neutral-900);margin-bottom:6px;"><?= e($r->getNameVenue()) ?></h3>
          <p class="muted">Capacidad: <?= (int) $r->getCapacityVenue() ?></p>
          <?php if (isset($locationByVenue[$r->getIdVenue()]) && $locationByVenue[$r->getIdVenue()] !== null): ?>
            <p class="muted">&#128205; <?= format_venue_location($locationByVenue[$r->getIdVenue()]) ?>
              <?php if (isset($distanceLabelByVenue[$r->getIdVenue()])): ?>
                <span class="badge info" style="margin-left:4px;"><?= e($distanceLabelByVenue[$r->getIdVenue()]) ?></span>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </div>
        <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'detail', ['id' => $r->getIdVenue()])) ?>">Ver local</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div id="geo-module"
     data-has-location="<?= $hasLocation ? '1' : '0' ?>"
     data-geo-url="<?= e(base_url('api', 'geolocate')) ?>"
     data-save-url="<?= e(base_url('client', 'updateLocation')) ?>"
     data-csrf="<?= e(csrf_token()) ?>">

  <h2 style="font-size:1.15rem;color:var(--neutral-700);margin:28px 0 14px;">Locales cerca de ti</h2>
  <?php if ($hasLocation && !empty($nearbyVenues)): ?>
    <div class="grid">
      <?php foreach ($nearbyVenues as $v): ?>
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
              <p class="muted">&#128205; <?= format_venue_location($locationByVenue[$v->getIdVenue()]) ?>
                <?php if (isset($distanceLabelByVenue[$v->getIdVenue()])): ?>
                  <span class="badge info" style="margin-left:4px;"><?= e($distanceLabelByVenue[$v->getIdVenue()]) ?></span>
                <?php endif; ?>
              </p>
            <?php endif; ?>
          </div>
          <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'detail', ['id' => $v->getIdVenue()])) ?>">Ver local</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif (!$hasLocation): ?>
    <div class="card empty">
      <span class="emoji">&#128205;</span>
      Configura tu ubicación en
      <a href="<?= e(base_url('client', 'profile')) ?>">Mi perfil</a>
      (o permítenos detectarla) para ver locales cerca de ti.
    </div>
  <?php else: ?>
    <div class="card empty">
      <span class="emoji">&#128205;</span>
      No hay locales en tu provincia por ahora.
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>