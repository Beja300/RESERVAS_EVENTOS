<?php require_once __DIR__ . '/../_header.php';

$pageJs = ['venue/location', 'venue/catalog'];

$filters = $filters ?? [
  'province' => '',
  'canton'   => '',
  'district' => '',
  'type'     => '',
  'q'        => '',
];
?>

<div class="page-head">
  <div>
    <h1>Explorar locales</h1>
    <p class="muted">Descubre los mejores lugares para tus eventos</p>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <form method="get" action="<?= e(base_url('venue', 'catalog')) ?>" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
    <div class="form-group" style="flex:2;min-width:200px;margin:0;">
      <label for="q">Buscar</label>
      <input class="form-control" type="search" id="q" name="q"
             placeholder="Nombre o palabra clave..."
             value="<?= e($filters['q']) ?>">
    </div>
    <div class="form-group" style="min-width:160px;margin:0;">
      <label for="province">Provincia</label>
      <select class="form-control" id="province" name="province" data-level="province"
              data-value="<?= e($filters['province']) ?>">
        <option value="">— Todas —</option>
      </select>
    </div>
    <div class="form-group" style="min-width:160px;margin:0;">
      <label for="canton">Cantón</label>
      <select class="form-control" id="canton" name="canton" data-level="canton" disabled
              data-value="<?= e($filters['canton']) ?>">
        <option value="">— Todos —</option>
      </select>
    </div>
    <div class="form-group" style="min-width:160px;margin:0;">
      <label for="district">Distrito</label>
      <select class="form-control" id="district" name="district" data-level="district" disabled
              data-value="<?= e($filters['district']) ?>">
        <option value="">— Todos —</option>
      </select>
    </div>
    <div class="form-group" style="min-width:140px;margin:0;">
      <label for="type">Tipo de local</label>
      <input class="form-control" type="text" id="type" name="type"
             placeholder="Ej: Salón"
             value="<?= e($filters['type']) ?>">
    </div>
    <button class="btn btn-primary" type="submit">Filtrar</button>
    <a class="btn btn-ghost" href="<?= e(base_url('venue', 'catalog')) ?>">Limpiar</a>
  </form>
</div>

<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    No hay locales que coincidan con tu búsqueda.
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
          <p style="color:var(--neutral-900);font-weight:700;margin-top:6px;">
            &#8353; <?= number_format($v->getPriceVenue(), 2) ?>
            <span class="muted" style="font-weight:400;">por evento</span>
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

<?php require_once __DIR__ . '/../_footer.php'; ?>