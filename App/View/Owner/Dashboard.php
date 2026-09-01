<?php require_once __DIR__ . '/../_header.php';
$owner = $_SESSION['user'] ?? null;
$stats = $stats ?? [];
$monthLabel = $monthLabel ?? date('F Y');
$yearMonth = $yearMonth ?? date('Y-m');
$prevMonth = $prevMonth ?? date('Y-m', strtotime('-1 month'));
$nextMonth = $nextMonth ?? date('Y-m', strtotime('+1 month'));
$topVenue = $topVenue ?? [];
$topServices = $topServices ?? [];
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

<div class="card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <a class="btn btn-sm" href="<?= e(base_url('owner', 'dashboard', ['month' => $prevMonth])) ?>">&larr; Mes anterior</a>
    <form method="get" action="<?= e(base_url('owner', 'dashboard')) ?>" style="display:flex;align-items:center;gap:8px;">
      <input type="hidden" name="controller" value="owner">
      <input type="hidden" name="action" value="dashboard">
      <input class="form-control" style="width:auto;" type="month" name="month" value="<?= e($yearMonth) ?>">
      <button class="btn btn-primary btn-sm" type="submit">Ver mes</button>
    </form>
    <a class="btn btn-sm" href="<?= e(base_url('owner', 'dashboard', ['month' => $nextMonth])) ?>">Mes siguiente &rarr;</a>
  </div>
</div>

<div style="margin-bottom:24px; background-color:white;padding:16px;border-radius:6px;">
  <p class="muted" style="margin-bottom:12px;">Resumen de <?= e($monthLabel) ?></p>
  <div class="grid grid-3" style="margin-bottom:16px;">
    <div class="stat"><div class="value"><?= $m((float) ($stats['comision'] ?? 0)) ?></div><div class="label">Comisión al admin</div></div>
    <div class="stat"><div class="value"><?= $m((float) ($stats['ganancias'] ?? 0)) ?></div><div class="label">Ganancias del mes</div></div>
    <div class="stat"><div class="value"><?= $m((float) ($stats['totalBruto'] ?? 0)) ?></div><div class="label">Total recaudado</div></div>
  </div>
  <div class="grid grid-4">
    <div class="stat"><div class="value"><?= (int) ($stats['locales'] ?? 0) ?></div><div class="label">Locales</div></div>
    <div class="stat">
      <div class="value" style="color:<?= ($stats['porRevisar'] ?? 0) > 0 ? 'var(--warning)' : 'var(--primary)' ?>;"><?= (int) ($stats['porRevisar'] ?? 0) ?></div>
      <div class="label">Reservas por revisar</div>
    </div>
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

<div class="grid grid-2" style="margin-top:24px;">
  <div class="card">
    <h3 class="card-title">Local más solicitado</h3>
    <?php if (empty($topVenue)): ?>
      <p class="muted">Sin reservas en este mes.</p>
    <?php else: ?>
      <?php $tv = $topVenue[0]; ?>
      <strong><?= e($tv['name']) ?></strong>
      <span class="badge info" style="margin-left:6px;"><?= (int) $tv['bookingCount'] ?> reservas</span>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Servicios más agregados</h3>
    <?php if (empty($topServices)): ?>
      <p class="muted">Sin servicios en este mes.</p>
    <?php else: ?>
      <ol style="padding-left:20px;">
        <?php foreach ($topServices as $ts): ?>
          <li style="margin-bottom:8px;">
            <strong><?= e($ts['name']) ?></strong>
            <span class="badge info" style="margin-left:6px;"><?= (int) $ts['totalQuantity'] ?> uds.</span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
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
