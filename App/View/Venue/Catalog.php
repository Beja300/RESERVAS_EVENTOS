<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Explorar locales</h1>
    <p class="muted">Descubre los mejores lugares para tus eventos</p>
  </div>
</div>

<div class="form-group" style="max-width:420px;margin-bottom:18px;">
  <input class="form-control" type="search" data-card-filter=".venue-card"
         placeholder="Buscar por nombre o tipo de local...">
</div>

<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    No hay locales disponibles por el momento.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($venues as $v): ?>
      <div class="card venue-card" style="display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <div style="height:150px;border-radius:8px;background:linear-gradient(135deg,var(--primary-light),var(--neutral-100));display:flex;align-items:center;justify-content:center;font-size:3rem;margin-bottom:14px;">
            <?= $v->getImageVenue() !== '' ? htmlspecialchars($v->getImageVenue()) : '&#127968;' ?>
          </div>
          <h3 style="margin-bottom:6px;color:var(--neutral-900);"><?= e($v->getNameVenue()) ?></h3>
          <p style="color:var(--neutral-500);font-size:0.92rem;">
            <?= $v->getTypeVenue() !== '' ? e($v->getTypeVenue()) : 'General' ?>
            &nbsp;·&nbsp; Capacidad: <?= (int) $v->getCapacityVenue() ?>
          </p>
          <?php if (isset($ratingsByVenue[$v->getIdVenue()])): ?>
            <p style="color:var(--amber, #f59e0b);font-size:0.9rem;margin-top:6px;">
              &#11088; <?= number_format($ratingsByVenue[$v->getIdVenue()], 1) ?> / 5
            </p>
          <?php endif; ?>
          <?php if (isset($promosByVenue[$v->getIdVenue()])): ?>
            <?php foreach ($promosByVenue[$v->getIdVenue()] as $pn): ?>
              <span class="badge success" style="margin-top:6px;">&#127881; <?= e($pn) ?></span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div style="margin-top:14px;">
          <a class="btn btn-primary btn-block" href="<?= e(base_url('venue', 'detail', ['id' => $v->getIdVenue()])) ?>">Ver local</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="<?= e(js_url('catalog')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>
