<?php
$pageCss = 'venue/detail';
$pageJs = ['venue/stars', 'venue/venue-comments', 'venue/service-review', 'venue/favorite'];
?>
<?php require_once __DIR__ . '/../_header.php';
if ($venue === null) {
  echo '<div class="alert alert-error">Local no encontrado.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1 style="margin-top:8px;"><?= e($venue->getNameVenue()) ?></h1>
  </div>
  <?php if (current_user_type() !== null): ?>
    <button type="button" id="favoriteBtn" class="btn btn-outline <?= $isFavorite ? 'is-favorite' : '' ?>"
            data-venue-id="<?= (int) $venue->getIdVenue() ?>"
            data-favorite="<?= $isFavorite ? '1' : '0' ?>"
            data-url="<?= e(base_url('venue', 'favorite')) ?>"
            data-csrf="<?= e(csrf_token()) ?>">
      <span class="fav-icon"><?= $isFavorite ? '&#9829;' : '&#9825;' ?></span>
      <span class="fav-label"><?= $isFavorite ? 'Favorito' : 'Favorito' ?></span>
    </button>
  <?php endif; ?>

</div>
<?php if ($owner !== null): ?>
  <div style="display:flex;align-items:center;flex-wrap:wrap;gap:6px 14px;margin-top:14px;padding-top:12px;border-top:1px solid var(--neutral-200);font-size:.92rem;">
    <span>
      <strong>Propietario:</strong>
      <?= e($owner->getFirstNameOwner()) ?><?= $owner->getLastNameOwner() !== ''
        ? ' ' . e($owner->getLastNameOwner())
        : '' ?>
    </span>

    <span>
      <strong>Correo:</strong>
      <?= e($owner->getEmail()) ?>
    </span>

    <?php if ($owner->getPhoneNumber() !== null && $owner->getPhoneNumber() !== ''): ?>
      <span>
        <strong>Teléfono:</strong>
        <?= e(format_phone_number($owner->getPhoneNumber())) ?>
      </span>
    <?php endif; ?>

    <a href="<?= e(base_url('venue', 'showOwner', [
      'ownerId' => $owner->getIdOwner(),
      'venueId' => $venue->getIdVenue()
    ])) ?>">
      Ver información del propietario &rarr;
    </a>
  </div>
<?php endif; ?>
<br>
<?php if ($venue->getImageVenue() !== ''): ?>
  <div style="border-radius:10px;overflow:hidden;margin-bottom:18px;box-shadow:0 4px 14px rgba(0,0,0,.12);">
    <img src="<?= e(image_url($venue->getImageVenue())) ?>" alt="Foto de <?= e($venue->getNameVenue()) ?>"
         style="width:100%;max-height:340px;object-fit:cover;display:block;">
  </div>
<?php endif; ?>

<?php if (!empty($venueImages)): ?>
  <div class="actions" style="flex-wrap:wrap;gap:10px;margin-bottom:18px;">
    <?php foreach ($venueImages as $image): ?>
      <img src="<?= e(image_url($image->getPath())) ?>" alt="Galería de <?= e($venue->getNameVenue()) ?>"
           style="width:170px;height:120px;object-fit:cover;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.12);">
    <?php endforeach; ?>
  </div>
<?php endif; ?>


<div class="card">

  <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">

    <a class="btn btn-primary"
       href="<?= e(base_url('booking', 'showForm', ['venueId' => $venue->getIdVenue()])) ?>">
      &#128197; Reservar este local
    </a>
  </div>

  <div class="detail-grid">
    <div class="detail-item">
      <div class="k">Tipo</div>
      <div class="v">
        <?= $venue->getTypeVenue() !== '' ? e($venue->getTypeVenue()) : 'General' ?>
      </div>
    </div>

    <div class="detail-item">
      <div class="k">Capacidad</div>
      <div class="v">
        <?php if ($venue->getMinCapacity() !== null): ?>
          <?= (int) $venue->getMinCapacity() ?> a
        <?php endif; ?>
        <?= (int) $venue->getCapacityVenue() ?> personas
      </div>
    </div>

    <div class="detail-item">
      <div class="k">Precio de renta</div>
      <div class="v">
        &#8353; <?= number_format($venue->getPriceVenue(), 2) ?>
        <span class="muted">por evento</span>
      </div>
    </div>

    <?php if ($venue->getSizeVenue() !== null): ?>
      <div class="detail-item">
        <div class="k">Tamaño</div>
        <div class="v"><?= e($venue->getSizeVenue()) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($venue->getBathroomsVenue() !== null): ?>
      <div class="detail-item">
        <div class="k">Baños</div>
        <div class="v"><?= (int) $venue->getBathroomsVenue() ?></div>
      </div>
    <?php endif; ?>

    <?php if ($venue->getHasParking()): ?>
      <div class="detail-item">
        <div class="k">Parqueo</div>
        <div class="v">
          Sí<?= $venue->getParkingCapacity() !== null
            ? ' (' . (int) $venue->getParkingCapacity() . ' vehículos)'
            : '' ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($venue->getCleaningFee() > 0): ?>
      <div class="detail-item">
        <div class="k">Limpieza</div>
        <div class="v">&#8353; <?= number_format($venue->getCleaningFee(), 2) ?></div>
      </div>
    <?php endif; ?>

    <div class="detail-item">
      <div class="k">Calificación</div>
      <div class="v" id="venueAvgRating">
        <?php if ($avgRating !== null): ?>
          <span class="rating-stars">
            <?= str_repeat('&#9733;', (int) round($avgRating))
              . str_repeat('&#9734;', 5 - (int) round($avgRating)) ?>
          </span>
          <span class="muted"><?= number_format($avgRating, 1) ?> / 5</span>
        <?php else: ?>
          Sin calificaciones
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
    $breakdown = $venueRatingBreakdown ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $breakdownTotal = array_sum($breakdown);
  ?>

  <?php if ($breakdownTotal > 0): ?>
    <div class="rating-breakdown" style="margin-top:14px;">
      <h4 style="margin:0 0 8px;">
        Distribución de calificaciones (<?= (int) $breakdownTotal ?>)
      </h4>
      <?php for ($star = 5; $star >= 1; $star--):
        $count = (int) $breakdown[$star];
        $pct = $breakdownTotal > 0 ? round($count * 100 / $breakdownTotal) : 0;
      ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
          <span class="muted" style="width:52px;"><?= $star ?> &#9733;</span>
          <span style="flex:1;background:var(--neutral-200);border-radius:6px;height:10px;overflow:hidden;">
            <span style="display:block;height:100%;width:<?= $pct ?>%;background:var(--primary-500,#f59e0b);"></span>
          </span>
          <span class="muted" style="width:70px;text-align:right;">
            <?= $count ?> (<?= $pct ?>%)
          </span>
        </div>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <div class="detail-item" style="margin-top:14px;">
    <div class="k">Ubicación</div>
    <div class="v">
      <?php if ($location !== null): ?>
        <?php $isApproximateLocation = $location->getTypeLocation() === 'aproximada'; ?>
        <div><?= format_venue_location($location) ?></div>

        <?php if (!$isApproximateLocation && $location->getTownLocation() !== null && $location->getTownLocation() !== ''): ?>
          <div class="muted">Barrio: <?= e($location->getTownLocation()) ?></div>
        <?php endif; ?>

        <?php if ($location->getDescriptionLocation() !== null && $location->getDescriptionLocation() !== ''): ?>
          <div class="muted">Señas: <?= e($location->getDescriptionLocation()) ?></div>
        <?php endif; ?>
      <?php else: ?>
        <span class="muted">Sin ubicación registrada</span>
      <?php endif; ?>
    </div>
  </div>



  <?php if ($venue->getDescriptionVenue() !== null && $venue->getDescriptionVenue() !== ''): ?>
    <div style="margin-top:16px;">
      <h4 style="margin-bottom:8px;">Sobre el local</h4>
      <p><?= nl2br(e($venue->getDescriptionVenue())) ?></p>
    </div>
  <?php endif; ?>

  <?php $rentalBlocks = $rentalBlocks ?? []; ?>
  <?php if (!empty($rentalBlocks)): ?>
    <div style="margin-top:16px;">
      <h4 style="margin-bottom:8px;">Bloques de alquiler</h4>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Bloque</th><th>Horario</th><th>Precio</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rentalBlocks as $block): ?>
              <tr>
                <td><?= e($block->getName()) ?></td>
                <td><?= e(substr($block->getStartTime(), 0, 5)) ?>–<?= e(substr($block->getEndTime(), 0, 5)) ?></td>
                <td>&#8353; <?= number_format($block->getPrice(), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

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
              <td>
                <?php $serviceGallery = $serviceImages[$s->getIdService()] ?? []; ?>
                <?php if (!empty($serviceGallery)): ?>
                  <img src="<?= e(image_url($serviceGallery[0]->getPath())) ?>"
                       alt="<?= e($s->getNameService()) ?>"
                       style="width:56px;height:42px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px;">
                <?php endif; ?>
                <?= e($s->getNameService()) ?>
              </td>
              <td><?= $s->getTypeService() !== null ? e($s->getTypeService()) : '—' ?></td>
              <td>&#8353; <?= number_format($s->getPriceService(), 2) ?></td>
              <td id="serviceAvg-<?= (int) $s->getIdService() ?>">
                <?php if (isset($ratingByService[$s->getIdService()])): ?>
                  <span class="rating-stars">
                    <?= str_repeat('&#9733;', (int) round($ratingByService[$s->getIdService()]))
                      . str_repeat('&#9734;', 5 - (int) round($ratingByService[$s->getIdService()])) ?>
                  </span>
                  <span class="muted">
                    <?= number_format($ratingByService[$s->getIdService()], 1) ?> / 5
                  </span>
                <?php else: ?>
                  Sin calificaciones
                <?php endif; ?>
              </td>
              <td>
                <?php if (current_user_type() !== null): ?>
                  <details>
                    <summary class="btn btn-outline btn-sm">Calificar servicio</summary>
                    <form method="post" action="<?= e(base_url('venue', 'rateService')) ?>"
                          style="margin-top:8px;"
                          class="service-rate-form"
                          data-service-id="<?= (int) $s->getIdService() ?>"
                          data-avg-id="serviceAvg-<?= (int) $s->getIdService() ?>"
                          data-comments-id="serviceComments-<?= (int) $s->getIdService() ?>"
                          data-comments-url="<?= e(base_url('api', 'serviceComments', ['id' => $s->getIdService()])) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="serviceId" value="<?= (int) $s->getIdService() ?>">
                      <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">

                      <div class="form-group">
                        <div class="star-widget is-sm"
                             data-value="<?= (int) (isset($myRatingByService[$s->getIdService()])
                               ? $myRatingByService[$s->getIdService()]->getStars()
                               : 0) ?>">
                          <input type="hidden" name="stars" value="">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star" data-star="<?= $i ?>"
                                    aria-label="<?= $i ?> estrellas">&#9733;</button>
                          <?php endfor; ?>
                        </div>
                      </div>

                      <div class="form-group">
                        <input class="form-control" name="comment"
                               placeholder="Reserva (opcional)"
                               value="<?= e(isset($myRatingByService[$s->getIdService()])
                                 ? $myRatingByService[$s->getIdService()]->getComment()
                                 : '') ?>">
                      </div>

                      <button class="btn btn-primary btn-sm" type="submit">
                        <?= isset($myRatingByService[$s->getIdService()])
                          ? 'Actualizar calificación'
                          : 'Publicar calificación' ?>
                      </button>
                      <span class="service-rate-ok" hidden>
                        Reseña guardada correctamente.
                      </span>
                    </form>
                  </details>
                <?php else: ?>
                  <span class="muted">Inicia sesión</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td colspan="5" class="comment-list"
                  id="serviceComments-<?= (int) $s->getIdService() ?>">
                <?= render_partial(__DIR__ . '/_serviceComments.php', [
                  'comments' => $serviceComments[$s->getIdService()]
                ]) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if (current_user_type() !== null): ?>
  <?php $hasMyVenueRating = $myVenueRating !== null; ?>
  <div class="card" id="commentCard" style="max-width:520px;margin-top:18px;">
    <div class="page-head" style="margin:0 0 10px;">
      <h3 style="margin:0;">Tu reseña sobre este local</h3>
    </div>
    <form id="commentForm" method="post"
          action="<?= e(base_url('venue', 'rate')) ?>"
          data-update-url="<?= e(base_url('venue', 'updateComment')) ?>"
          data-refresh-url="<?= e(base_url('api', 'venueComments', ['id' => $venue->getIdVenue()])) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="venueId" value="<?= (int) $venue->getIdVenue() ?>">
      <input type="hidden" name="commentId"
             value="<?= $hasMyVenueRating ? (int) $myVenueRating->getIdVenueRating() : '' ?>">

      <div class="form-group">
        <label>Calificación</label>
        <div class="star-widget" id="venueStarWidget"
             data-value="<?= $hasMyVenueRating ? (int) $myVenueRating->getStars() : 0 ?>">
          <input type="hidden" name="stars" value="">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star" data-star="<?= $i ?>"
                    aria-label="<?= $i ?> estrellas">&#9733;</button>
          <?php endfor; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="comment">Reseña</label>
        <textarea class="form-control" id="comment" name="comment" rows="3"
                  placeholder="Escribe tu reseña sobre este local..."><?= $hasMyVenueRating
                    ? e($myVenueRating->getComment())
                    : '' ?></textarea>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-primary" id="submitComment" type="submit">
          <?= $hasMyVenueRating ? 'Actualizar reseña' : 'Publicar reseña' ?>
        </button>
        <button class="btn btn-outline" id="cancelEdit" type="button"
                style="display:none;">
          Cancelar edición
        </button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card" id="venueCommentsCard" style="margin-top:18px;">
  <h3 style="margin-bottom:10px;">Reseñas del local</h3>
  <div id="venueCommentsList">
    <?= render_partial(__DIR__ . '/_venueComments.php', [
      'venueComments' => $venueComments
    ]) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
