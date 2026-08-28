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
    <div class="detail-item"><div class="k">Ubicación</div><div class="v">Local #<?= (int) $venue->getIdUbication() ?></div></div>
    <div class="detail-item"><div class="k">Estado</div><div class="v"><span class="badge success">Disponible</span></div></div>
  </div>

  <a class="btn btn-primary" href="<?= e(base_url('booking', 'showForm', ['venueId' => $venue->getIdVenue()])) ?>">
    &#128197; Reservar este local
  </a>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
