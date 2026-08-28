<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Promociones</h1>
    <a href="<?= e(base_url('promotion', 'showForm', ['venueId' => $idVenue])) ?>">+ Nueva promoción</a>
    <a href="<?= e(base_url('venue', 'list')) ?>" style="margin-left:12px;">&larr; Mis locales</a>
  </div>
</div>

<?php if (isset($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php
$nameByService = [];
foreach ($availableServices as $s) {
  $nameByService[$s->getIdService()] = $s->getNameService();
}
?>

<?php if (empty($promotions)): ?>
  <div class="card empty">
    <span class="emoji">&#127881;</span>
    Todavía no tienes promociones para este local.
  </div>
<?php else: ?>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr));">
    <?php foreach ($promotions as $promo): ?>
      <div class="card">
        <h3 style="margin-bottom:8px;"><?= e($promo->getLabel()) ?></h3>
        <p class="muted"><?= e($promo->getDescription() !== '' ? $promo->getDescription() : 'Sin descripción') ?></p>
        <div class="detail-grid" style="margin:12px 0;">
          <div class="detail-item"><div class="k">Desde</div><div class="v"><?= $promo->getStartDate() ? e(date('d/m/Y', strtotime($promo->getStartDate()))) : '—' ?></div></div>
          <div class="detail-item"><div class="k">Hasta</div><div class="v"><?= $promo->getEndDate() ? e(date('d/m/Y', strtotime($promo->getEndDate()))) : '—' ?></div></div>
          <div class="detail-item"><div class="k">Mín. servicios</div><div class="v"><?= (int) $promo->getMinServices() ?></div></div>
          <div class="detail-item"><div class="k">Estado</div><div class="v"><span class="badge <?= $promo->getIsActive() ? 'success' : 'neutral' ?>"><?= $promo->getIsActive() ? 'Activa' : 'Inactiva' ?></span></div></div>
        </div>

        <h4 style="margin-bottom:8px;">Servicios incluidos</h4>
        <?php $included = $servicesByPromotion[$promo->getIdPromotion()] ?? []; ?>
        <?php if (empty($included)): ?>
          <p class="muted">Aún no incluye servicios.</p>
        <?php else: ?>
          <ul style="margin:0 0 12px;padding-left:18px;">
            <?php foreach ($included as $ps): ?>
              <li><?= isset($nameByService[$ps->getIdService()]) ? e($nameByService[$ps->getIdService()]) : 'Servicio #' . (int) $ps->getIdService() ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('promotion', 'addService')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="promotionId" value="<?= (int) $promo->getIdPromotion() ?>">
          <input type="hidden" name="venueId" value="<?= (int) $idVenue ?>">
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <select class="form-control" name="serviceId" required style="flex:1;min-width:180px;">
              <option value="">— Agregar servicio —</option>
              <?php
                $includedIds = array_map(fn($ps) => $ps->getIdService(), $included);
                foreach ($availableServices as $s):
                  if (in_array($s->getIdService(), $includedIds, true)) continue;
              ?>
                <option value="<?= (int) $s->getIdService() ?>"><?= e($s->getNameService()) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-outline" type="submit">Agregar</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
