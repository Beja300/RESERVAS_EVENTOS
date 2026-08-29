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
    <div class="detail-item"><div class="k">Precio de renta</div><div class="v">&#8353; <?= number_format($venue->getPriceVenue(), 2) ?> <span class="muted">por evento</span></div></div>
    <div class="detail-item"><div class="k">Ubicación</div><div class="v">Local #<?= (int) $venue->getIdLocation() ?></div></div>
    <div class="detail-item"><div class="k">Estado</div><div class="v"><span class="badge success">Disponible</span></div></div>
    <div class="detail-item"><div class="k">Calificación</div>
      <div class="v">
        <?php if ($avgRating !== null): ?>
          <span class="rating-stars"><?= str_repeat('&#9733;', (int) round($avgRating)) . str_repeat('&#9734;', 5 - (int) round($avgRating)) ?></span>
          <span class="muted"><?= number_format($avgRating, 1) ?> / 5</span>
        <?php else: ?>
          Sin calificaciones
        <?php endif; ?>
      </div>
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
              <?php if (isset($ratingByService[$s->getIdService()])): ?>
                <span class="rating-stars"><?= str_repeat('&#9733;', (int) round($ratingByService[$s->getIdService()])) . str_repeat('&#9734;', 5 - (int) round($ratingByService[$s->getIdService()])) ?></span>
                <span class="muted"><?= number_format($ratingByService[$s->getIdService()], 1) ?> / 5</span>
              <?php else: ?>
                Sin calificaciones
              <?php endif; ?>
            </td>
            <td>
              <?php if (current_user_type() !== null): ?>
                <details>
                  <summary class="btn btn-outline btn-sm">Calificar servicio</summary>
                  <form method="post" action="<?= e(base_url('venue', 'rateService')) ?>" style="margin-top:8px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="serviceId" value="<?= (int) $s->getIdService() ?>">
                    <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">
                    <div class="form-group">
                      <div class="star-widget is-sm" data-value="<?= (int) (isset($myRatingByService[$s->getIdService()]) ? $myRatingByService[$s->getIdService()]->getStars() : 0) ?>">
                        <input type="hidden" name="stars" value="">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <button type="button" class="star" data-star="<?= $i ?>" aria-label="<?= $i ?> estrellas">&#9733;</button>
                        <?php endfor; ?>
                      </div>
                    </div>
                    <div class="form-group">
                      <input class="form-control" name="comment" placeholder="Comentario (opcional)" value="<?= e(isset($myRatingByService[$s->getIdService()]) ? $myRatingByService[$s->getIdService()]->getComment() : '') ?>">
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">
                      <?= isset($myRatingByService[$s->getIdService()]) ? 'Actualizar calificación' : 'Publicar calificación' ?>
                    </button>
                  </form>
                </details>
              <?php else: ?>
                <span class="muted">Inicia sesión</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php if (!empty($serviceComments[$s->getIdService()])): ?>
            <tr>
              <td colspan="5" class="comment-list">
                <?php foreach ($serviceComments[$s->getIdService()] as $c): ?>
                  <div class="comment-item">
                    <span class="c-author"><?= e($c['tbrolename']) ?></span>
                    <span class="rating-stars"><?= str_repeat('&#9733;', (int) $c['tbserviceratingstars']) . str_repeat('&#9734;', 5 - (int) $c['tbserviceratingstars']) ?></span>
                    <?php if (!empty($c['tbserviceratingcomment'])): ?>
                      <div class="c-body"><?= e($c['tbserviceratingcomment']) ?></div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endif; ?>
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
      <label>Calificación *</label>
      <div class="star-widget" data-value="<?= (int) ($myVenueRating !== null ? $myVenueRating->getStars() : 0) ?>">
        <input type="hidden" name="stars" value="">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <button type="button" class="star" data-star="<?= $i ?>" aria-label="<?= $i ?> estrellas">&#9733;</button>
        <?php endfor; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="comment">Comentario (opcional)</label>
      <textarea class="form-control" id="comment" name="comment" rows="3"><?= e($myVenueRating !== null ? $myVenueRating->getComment() : '') ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">
      <?= $myVenueRating !== null ? 'Actualizar calificación' : 'Publicar calificación' ?>
    </button>
  </form>
</div>
<?php endif; ?>

<?php if (!empty($venueComments)): ?>
<div class="card" style="margin-top:18px;">
  <h3 style="margin-bottom:10px;">Comentarios del local</h3>
  <?php foreach ($venueComments as $c): ?>
    <div class="comment-item">
      <span class="c-author"><?= e($c['tbrolename']) ?></span>
      <span class="rating-stars"><?= str_repeat('&#9733;', (int) $c['tbvenueratingstars']) . str_repeat('&#9734;', 5 - (int) $c['tbvenueratingstars']) ?></span>
      <?php if (!empty($c['tbvenueratingcomment'])): ?>
        <div class="c-body"><?= e($c['tbvenueratingcomment']) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="<?= e(js_url('stars')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>