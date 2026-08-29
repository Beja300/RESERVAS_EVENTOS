<?php require_once __DIR__ . '/../_header.php';
if ($owner === null) {
  echo '<div class="alert alert-error">Propietario no encontrado.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
$name = $owner->getFirstNameOwner();
$lastName = $owner->getLastNameOwner();
?>

<div class="page-head">
  <div>
    <h1>Propietario</h1>
  </div>
  <?php if ($returnVenueId > 0): ?>
    <a class="btn btn-outline" href="<?= e(base_url('venue', 'detail', ['id' => $returnVenueId])) ?>">&larr; Volver al local</a>
  <?php else: ?>
    <a class="btn btn-outline" href="<?= e(base_url('venue', 'catalog')) ?>">&larr; Volver al catálogo</a>
  <?php endif; ?>
</div>

<div class="card" style="max-width:560px;">
  <div style="text-align:center;">
    <?php if ($owner->getImageOwner() !== ''): ?>
      <img src="<?= e(image_url($owner->getImageOwner())) ?>" alt="Foto del propietario"
           style="display:inline-block;width:128px;height:128px;border-radius:50%;object-fit:cover;vertical-align:middle;box-shadow:0 0 0 6px #fff,0 0 0 7px var(--neutral-200),0 6px 16px rgba(0,0,0,.18);margin-bottom:16px;">
    <?php else: ?>
      <span class="avatar" aria-hidden="true"
            style="width:128px;height:128px;font-size:3.2rem;margin-bottom:16px;">&#128100;</span>
    <?php endif; ?>
  </div>

  <h2 style="text-align:center;margin-bottom:20px;">
    <?= e($name) ?><?= $lastName !== '' ? ' ' . e($lastName) : '' ?>
  </h2>

  <div class="detail-grid">
    <div class="detail-item"><div class="k">Nombre</div><div class="v"><?= e($name) ?></div></div>
    <?php if ($lastName !== ''): ?>
      <div class="detail-item"><div class="k">Apellidos</div><div class="v"><?= e($lastName) ?></div></div>
    <?php endif; ?>
    <?php if ($owner->getAliasOwner() !== ''): ?>
      <div class="detail-item"><div class="k">Alias</div><div class="v"><?= e($owner->getAliasOwner()) ?></div></div>
    <?php endif; ?>
    <div class="detail-item"><div class="k">Correo</div><div class="v"><?= e($owner->getEmail()) ?></div></div>
    <?php if ($owner->getPhoneNumber() !== '' && $owner->getPhoneNumber() !== null): ?>
      <div class="detail-item"><div class="k">Teléfono</div><div class="v"><?= e($owner->getPhoneNumber()) ?></div></div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($ownerVenues)): ?>
<div class="card" style="margin-top:18px;">
  <h3 style="margin-bottom:12px;">Locales de este propietario</h3>
  <?php foreach ($ownerVenues as $v): ?>
    <a class="list-item" href="<?= e(base_url('venue', 'detail', ['id' => $v->getIdVenue()])) ?>"
       style="text-decoration:none;">
      <span>
        <span class="title" style="display:block;"><?= e($v->getNameVenue()) ?></span>
        <span class="desc"><?= $v->getTypeVenue() !== '' ? e($v->getTypeVenue()) : 'General' ?></span>
      </span>
      <span class="muted">&rarr;</span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>