<?php require_once __DIR__ . '/../_header.php';
if ($venue === null) {
  echo '<div class="alert alert-error">Local no encontrado.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <a href="<?= e(base_url('venue', 'catalog')) ?>">&larr; Volver al catálogo</a>
    <h1 style="margin-top:8px;"><?= e($venue->getNameVenue()) ?></h1>
  </div>
</div>

<div class="card">
  <div class="detail-grid">
    <div class="detail-item"><div class="k">Tipo</div><div class="v"><?= $venue->getTypeVenue() !== '' ? e($venue->getTypeVenue()) : 'General' ?></div></div>
    <div class="detail-item"><div class="k">Capacidad</div><div class="v"><?= (int) $venue->getCapacityVenue() ?> personas</div></div>
    <div class="detail-item"><div class="k">Ubicación</div><div class="v">Local #<?= (int) $venue->getIdLocation() ?></div></div>
    <div class="detail-item"><div class="k">Estado</div><div class="v"><span class="badge success">Disponible</span></div></div>
    <div class="detail-item"><div class="k">Calificación</div>
      <div class="v"><?= $avgRating !== null ? '&#11088; ' . number_format($avgRating, 1) . ' / 5' : 'Sin calificaciones' ?></div>
    </div>
  </div>

  <?php if (!empty($promotions)): ?>
    <div style="margin:16px 0 0;">
      <h4 style="margin-bottom:8px;">Promociones activas</h4>
      <?php foreach ($promotions as $promo): ?>
        <div class="alert alert-info" style="text-align:left;margin-bottom:8px;">
          <strong>&#127881; <?= e($promo->getLabel()) ?></strong><br>
          <span class="muted"><?= e($promo->getDescription()) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <a class="btn btn-primary" href="<?= e(base_url('booking', 'showForm', ['venueId' => $venue->getIdVenue()])) ?>">
    &#128197; Reservar este local
  </a>
</div>

<?php if (!empty($services)): ?>
<div class="card" style="margin-top:18px;">
  <h3 style="margin-bottom:12px;">Servicios del local</h3>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Servicio</th>
          <th>Tipo</th>
          <th>Precio</th>
          <th>Calificación</th>
          <th>Calificar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?= e($s->getNameService()) ?></td>
            <td><?= $s->getTypeService() !== null ? e($s->getTypeService()) : '—' ?></td>
            <td>&#8353; <?= number_format($s->getPriceService(), 2) ?></td>
            <td>
              <?= isset($ratingByService[$s->getIdService()])
                ? '&#11088; ' . number_format($ratingByService[$s->getIdService()], 1) . ' / 5'
                : 'Sin calificaciones' ?>
            </td>
            <td>
              <?php if (current_user_type() !== null): ?>
                <details>
                  <summary class="btn btn-outline btn-sm">Calificar</summary>
                  <form method="post" action="<?= e(base_url('venue', 'rateService')) ?>" style="margin-top:8px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="serviceId" value="<?= (int) $s->getIdService() ?>">
                    <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">
                    <div class="form-group">
                      <select class="form-control" name="stars" required>
                        <option value="">Estrellas</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <option value="<?= $i ?>"><?= str_repeat('&#11088;', $i) ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <input class="form-control" name="comment" placeholder="Comentario (opcional)">
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">Enviar</button>
                  </form>
                </details>
              <?php else: ?>
                <span class="muted">Inicia sesión</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (current_user_type() !== null): ?>
<div class="card" style="max-width:520px;margin-top:18px;">
  <h3 style="margin-bottom:10px;">Calificar este local</h3>
  <form method="post" action="<?= e(base_url('venue', 'rate')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">
    <div class="form-group">
      <label for="stars">Calificación *</label>
      <select class="form-control" id="stars" name="stars" required>
        <option value="">— Selecciona —</option>
        <option value="1">&#11088;</option>
        <option value="2">&#11088;&#11088;</option>
        <option value="3">&#11088;&#11088;&#11088;</option>
        <option value="4">&#11088;&#11088;&#11088;&#11088;</option>
        <option value="5">&#11088;&#11088;&#11088;&#11088;&#11088;</option>
      </select>
    </div>
    <div class="form-group">
      <label for="comment">Comentario (opcional)</label>
      <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Publicar calificación</button>
  </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
