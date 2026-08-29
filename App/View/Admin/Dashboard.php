<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Panel de administración</h1>
    <p class="muted">Resumen del mes de <?= date('F Y', strtotime(date('Y-m'))) ?></p>
  </div>
</div>

<?php if (isset($_GET['cleaned'])): ?>
  <div class="alert alert-success">Los datos de prueba fueron eliminados correctamente.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">

<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="stat"><div class="value"><?= count($bookings) ?></div><div class="label">Reservas este mes</div></div>
  <div class="stat"><div class="value"><?= count($topVenues) ?></div><div class="label">Locales activos</div></div>
  <div class="stat"><div class="value"><?= count($topServices) ?></div><div class="label">Servicios top</div></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h3 class="card-title">Locales más reservados</h3>
    <?php if (empty($topVenues)): ?>
      <p class="muted">Sin datos todavía.</p>
    <?php else: ?>
      <ol style="padding-left:20px;">
        <?php foreach ($topVenues as $tv): ?>
          <li style="margin-bottom:8px;">
            <strong><?= e($tv['name']) ?></strong>
            <span class="badge info" style="margin-left:6px;"><?= (int) $tv['bookingCount'] ?> reservas</span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Servicios más solicitados</h3>
    <?php if (empty($topServices)): ?>
      <p class="muted">Sin datos todavía.</p>
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

<?php require_once __DIR__ . '/../_footer.php'; ?>
