<?php require_once __DIR__ . '/../_header.php';
$owner = $_SESSION['user'] ?? null;
?>

<div class="page-head">
  <div>
    <h1>Panel de <?= e($owner ? $owner->getName() : 'propietario') ?></h1>
    <p class="muted">Administra tus locales y revisa las reservas</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
</div>

<h2 style="font-size:1.15rem;color:var(--neutral-700);margin-bottom:14px;">Mis locales</h2>
<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    Aún no tienes locales. Crea tu primer local para empezar.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($venues as $v): ?>
      <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <h3 style="color:var(--neutral-900);margin-bottom:6px;"><?= e($v->getNameVenue()) ?></h3>
          <p class="muted">Capacidad: <?= (int) $v->getCapacityVenue() ?></p>
          <p class="muted">Reservas: <?= count($bookings[$v->getIdVenue()] ?? []) ?></p>
        </div>
        <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('booking', 'venueBookings', ['venueId' => $v->getIdVenue()])) ?>">Ver reservas</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
