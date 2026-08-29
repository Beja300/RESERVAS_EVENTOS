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

<?php if ($owner !== null): ?>
<div class="card" style="margin-top:18px;">
  <h3 style="margin-bottom:12px;">Propietario del local</h3>
  <a href="<?= e(base_url('venue', 'showOwner', ['ownerId' => $owner->getIdOwner(), 'venueId' => $venue->getIdVenue()])) ?>"
     style="display:inline-flex;align-items:center;gap:14px;text-decoration:none;">
    <?php if ($owner->getImageOwner() !== ''): ?>
      <img src="<?= e(image_url($owner->getImageOwner())) ?>" alt="Foto del propietario"
           style="width:72px;height:72px;border-radius:50%;object-fit:cover;box-shadow:0 0 0 4px #fff,0 0 0 5px var(--neutral-200),0 4px 12px rgba(0,0,0,.15);">
    <?php else: ?>
      <span class="avatar" aria-hidden="true"
            style="width:72px;height:72px;font-size:2rem;">&#128100;</span>
    <?php endif; ?>
    <span>
      <span style="display:block;font-weight:700;color:var(--neutral-900);">
        <?= e($owner->getFirstNameOwner()) ?><?= $owner->getLastNameOwner() !== '' ? ' ' . e($owner->getLastNameOwner()) : '' ?>
      </span>
      <span class="muted">Ver información del propietario &rarr;</span>
    </span>
  </a>
</div>
<?php endif; ?>

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
            <td data-service-avg>
              <span data-stars>
                <?php if (isset($ratingByService[$s->getIdService()])): ?>
                  <span class="rating-stars"><?= str_repeat('&#9733;', (int) round($ratingByService[$s->getIdService()])) . str_repeat('&#9734;', 5 - (int) round($ratingByService[$s->getIdService()])) ?></span>
                  <span class="muted"><?= number_format($ratingByService[$s->getIdService()], 1) ?> / 5</span>
                <?php else: ?>
                  <span class="muted">Sin calificaciones</span>
                <?php endif; ?>
              </span>
            </td>
            <td>
              <?php if (current_user_type() !== null): ?>
                <details>
                  <summary class="btn btn-outline btn-sm">Calificar servicio</summary>
                  <form method="post" action="<?= e(base_url('venue', 'rateService')) ?>" data-ajax-rate="service" style="margin-top:8px;">
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
            <tr data-service-comments-row>
              <td colspan="5" class="comment-list" data-service-comments>
                <?= render_partial(__DIR__ . '/_serviceComments.php', ['comments' => $serviceComments[$s->getIdService()] ?? []]) ?>
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
  <form method="post" action="<?= e(base_url('venue', 'rate')) ?>" data-ajax-rate="venue">
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

<div class="card" style="margin-top:18px;">
  <h3 style="margin-bottom:10px;">Comentarios del local</h3>
  <div data-venue-comments>
    <?= render_partial(__DIR__ . '/_venueComments.php', ['venueComments' => $venueComments ?? []]) ?>
  </div>
</div>

<script src="<?= e(js_url('stars')) ?>"></script>
<script>
  (function () {
    function base() { var p = (window.location.pathname || '').split('/'); p.pop(); return p.join('/'); }

    function apiUrl(action, extra) {
      var q = '?controller=api&action=' + encodeURIComponent(action);
      if (extra) {
        Object.keys(extra).forEach(function (k) { q += '&' + k + '=' + encodeURIComponent(extra[k]); });
      }
      return base() + '/index.php' + q;
    }

    function updateAvg(el, value) {
      var s = '';
      for (var i = 1; i <= 5; i++) s += i <= Math.round(value) ? '\u2605' : '\u2606';
      if (el) {
        el.innerHTML = s + ' <span class="muted">' + Number(value).toFixed(1) + ' / 5</span>';
      }
    }

    function submitForm(form, onOk) {
      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form)
      }).then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, data: j }; });
      }).then(function (r) {
        window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
        if (r.ok) onOk(r.data);
      });
    }

    // Calificación del local
    document.querySelectorAll('form[data-ajax-rate="venue"]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(form, function (data) {
          var idInput = form.querySelector('[name="venueId"]');
          fetch(apiUrl('venueComments', { id: idInput ? idInput.value : '' }))
            .then(function (r) { return r.json(); })
            .then(function (j) {
              var box = document.querySelector('[data-venue-comments]');
              if (box) box.innerHTML = j.html;
            });
        });
      });
    });

    // Calificación de servicios
    document.querySelectorAll('form[data-ajax-rate="service"]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(form, function (data) {
          var row = form.closest('tr');
          var svcIdInput = form.querySelector('[name="serviceId"]');
          var svcId = svcIdInput ? svcIdInput.value : null;
          // Actualiza el promedio mostrado en la misma fila.
          if (row) {
            var avgCell = row.querySelector('td[data-service-avg]');
            if (avgCell) updateAvg(avgCell.querySelector('[data-stars]') || avgCell, data.avg);
          }
          if (svcId) {
            fetch(apiUrl('serviceComments', { id: svcId }))
              .then(function (r) { return r.json(); })
              .then(function (j) {
                var commentsTd = row && row.nextElementSibling
                  ? row.nextElementSibling.querySelector('[data-service-comments]')
                  : null;
                if (commentsTd) commentsTd.innerHTML = j.html;
              });
          }
        });
      });
    });
  })();
</script>
<?php require_once __DIR__ . '/../_footer.php'; ?>