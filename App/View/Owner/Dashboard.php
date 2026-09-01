<?php require_once __DIR__ . '/../_header.php';
$owner = $_SESSION['user'] ?? null;
$stats = $stats ?? [];
$monthLabel = $monthLabel ?? date('F Y');
$m = function (float $v): string {
  return '&#8353; ' . number_format($v, 2);
};
$next = $stats['proximaReserva'] ?? null;
?>

<div class="page-head">
  <div>
    <h1>Bienvenid@, <?= e($owner ? $owner->getName() : 'propietario') ?></h1>
    <p class="muted">Administra tus locales y revisa las reservas</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
</div>

<div style="margin-bottom:24px; background-color:white;padding:16px;border-radius:6px;">
  <p class="muted" style="margin-bottom:12px;">Resumen de <?= e($monthLabel) ?></p>
  <div class="grid grid-4">
    <div class="stat"><div class="value"><?= (int) ($stats['locales'] ?? 0) ?></div><div class="label">Locales</div></div>
    <div class="stat">
      <div class="value" style="color:<?= ($stats['porRevisar'] ?? 0) > 0 ? 'var(--warning)' : 'var(--primary)' ?>;"><?= (int) ($stats['porRevisar'] ?? 0) ?></div>
      <div class="label">Reservas por revisar</div>
    </div>
    <div class="stat"><div class="value"><?= $m((float) ($stats['ganancias'] ?? 0)) ?></div><div class="label">Ganancias del mes</div></div>
    <div class="stat"><div class="value"><?= $m((float) ($stats['comision'] ?? 0)) ?></div><div class="label">Comisión al admin</div></div>
    <div class="stat"><div class="value"><?= $m((float) ($stats['totalBruto'] ?? 0)) ?></div><div class="label">Total recaudado</div></div>
    <div class="stat"><div class="value"><?= (int) ($stats['reservasMes'] ?? 0) ?></div><div class="label">Reservas del mes</div></div>
    <div class="stat">
      <div class="value" style="font-size:1.25rem;">
        <?php if ($next !== null): ?>
          <?= e(date('d/m/Y', strtotime($next['tbbookingdate']))) ?>
        <?php else: ?>
          &mdash;
        <?php endif; ?>
      </div>
      <div class="label"><?= $next !== null ? 'Próxima: ' . e($next['tbvenuename']) : 'Sin próximas reservas' ?></div>
    </div>
    <div class="stat">
      <?php if (($stats['rating'] ?? null) !== null): ?>
        <div class="value"><?= number_format((float) $stats['rating'], 1) ?>&#9733;</div>
        <div class="label">Rating promedio de mis locales</div>
      <?php else: ?>
        <div class="value">&mdash;</div>
        <div class="label">Sin calificaciones</div>
      <?php endif; ?>
    </div>
  </div>
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
