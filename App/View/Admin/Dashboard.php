<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Panel de administración</h1>
    <p class="muted">Resumen del mes de <?= date('F Y', strtotime($yearMonth . '-01')) ?></p>
  </div>
</div>

<?php if (isset($_GET['cleaned'])): ?>
  <div class="alert alert-success">Los datos de prueba fueron eliminados correctamente.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <a class="btn btn-sm" href="<?= e(base_url('admin', 'dashboard', ['month' => $prevMonth])) ?>">&larr; Mes anterior</a>
    <form method="get" action="<?= e(base_url('admin', 'dashboard')) ?>" style="display:flex;align-items:center;gap:8px;">
      <input type="hidden" name="controller" value="admin">
      <input type="hidden" name="action" value="dashboard">
      <input class="form-control" style="width:auto;" type="month" name="month" value="<?= e($yearMonth) ?>">
      <button class="btn btn-primary btn-sm" type="submit">Ver mes</button>
    </form>
    <a class="btn btn-sm" href="<?= e(base_url('admin', 'dashboard', ['month' => $nextMonth])) ?>">Mes siguiente &rarr;</a>
  </div>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="stat"><div class="value">&#8353; <?= number_format($monthStats['ingreso_bruto'], 2) ?></div><div class="label">Ingreso bruto del mes</div></div>
  <div class="stat"><div class="value">&#8353; <?= number_format($monthStats['comision'], 2) ?></div><div class="label">Ganancia mensual (comisi&oacute;n 5%)</div></div>
  <div class="stat"><div class="value">&#8353; <?= number_format($monthStats['iva'], 2) ?></div><div class="label">IVA retenido</div></div>
  <div class="stat"><div class="value">&#8353; <?= number_format($monthStats['propietarios'], 2) ?></div><div class="label">Ingreso propietarios</div></div>
</div>

<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="stat"><div class="value"><?= count($bookings) ?></div><div class="label">Reservas este mes</div></div>
  <div class="stat"><div class="value"><?= count($topVenues) ?></div><div class="label">Locales top</div></div>
  <div class="stat"><div class="value"><?= count($topServices) ?></div><div class="label">Servicios top</div></div>
</div>

<h3 class="card-title" style="margin-bottom:12px;">Estad&iacute;sticas operativas</h3>

<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="stat"><div class="value"><?= $stateCounts['pendiente'] ?></div><div class="label">Reservas pendientes de confirmar</div></div>
  <div class="stat"><div class="value"><?= $stateCounts['cancelado'] ?></div><div class="label">Reservas canceladas</div></div>
  <div class="stat"><div class="value"><?= $stateCounts['confirmado'] ?></div><div class="label">Reservas confirmadas</div></div>
  <div class="stat"><div class="value"><?= $stateCounts['rechazado'] ?></div><div class="label">Reservas rechazadas</div></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;">
  <div class="card">
    <h3 class="card-title">Reservas por estado</h3>
    <?php $totalStates = array_sum($stateCounts); ?>
    <?php if ($totalStates === 0): ?>
      <p class="muted">Sin datos en este mes.</p>
    <?php else: ?>
      <ul style="list-style:none;padding:0;margin:0;">
        <?php foreach ([
          'pendiente' => 'Pendientes de confirmar',
          'confirmado' => 'Confirmadas',
          'cancelado' => 'Canceladas',
          'rechazado' => 'Rechazadas',
        ] as $key => $label): ?>
          <li style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
              <span><?= e($label) ?></span>
              <span class="badge info"><?= $stateCounts[$key] ?> (<?= round($stateCounts[$key] * 100 / $totalStates, 1) ?>%)</span>
            </div>
            <div style="height:8px;background:var(--neutral-200);border-radius:4px;overflow:hidden;">
              <div style="height:100%;width:<?= round($stateCounts[$key] * 100 / $totalStates, 1) ?>%;background:var(--primary);"></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Tasa de ocupaci&oacute;n por local</h3>
    <?php if (empty($occupancy)): ?>
      <p class="muted">Sin datos en este mes.</p>
    <?php else: ?>
      <ol style="padding-left:20px;">
        <?php foreach ($occupancy as $o): ?>
          <?php $rate = (int) $o['total'] > 0 ? round(((int) $o['confirmed']) * 100 / (int) $o['total'], 1) : 0; ?>
          <li style="margin-bottom:8px;">
            <strong><?= e($o['name']) ?></strong>
            <span class="badge info" style="margin-left:6px;"><?= (int) $o['confirmed'] ?>/<?= (int) $o['total'] ?> reservas</span>
            <span class="badge success" style="margin-left:6px;"><?= $rate ?>%</span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</div>

<h3 class="card-title" style="margin-bottom:12px;">Estad&iacute;sticas de clientes</h3>

<div class="grid grid-4" style="margin-bottom:20px;">
  <div class="stat"><div class="value"><?= $clientStats['nuevos'] ?></div><div class="label">Clientes nuevos del mes</div></div>
  <div class="stat"><div class="value"><?= $clientStats['recurrentes'] ?></div><div class="label">Clientes recurrentes del mes</div></div>
  <div class="stat"><div class="value"><?= $venueAvg !== null ? number_format($venueAvg, 1) . ' / 5' : '&mdash;' ?></div><div class="label">Calificaci&oacute;n promedio de locales</div></div>
  <div class="stat"><div class="value"><?= $serviceAvg !== null ? number_format($serviceAvg, 1) . ' / 5' : '&mdash;' ?></div><div class="label">Calificaci&oacute;n promedio de servicios</div></div>
</div>

<div class="grid grid-2" style="margin-bottom:20px;">
  <div class="card">
    <h3 class="card-title">Clientes con m&aacute;s reservas del mes</h3>
    <?php if (empty($topClients)): ?>
      <p class="muted">Sin datos en este mes.</p>
    <?php else: ?>
      <ol style="padding-left:20px;">
        <?php foreach ($topClients as $tc): ?>
          <li style="margin-bottom:8px;">
            <strong><?= e($tc['name']) ?></strong>
            <span class="badge info" style="margin-left:6px;"><?= (int) $tc['bookingCount'] ?> reservas</span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Rese&ntilde;as</h3>
    <?php if ($venueReviews === 0 && $serviceReviews === 0): ?>
      <p class="muted">Sin rese&ntilde;as todav&iacute;a.</p>
    <?php else: ?>
      <ul style="list-style:none;padding:0;margin:0;">
        <li style="margin-bottom:10px;">
          <strong>Locales:</strong> <?= $venueReviews ?> rese&ntilde;a(s)
          <?php if ($venueAvg !== null): ?>
            <span class="badge success" style="margin-left:6px;">&#9733; <?= number_format($venueAvg, 1) ?></span>
          <?php endif; ?>
        </li>
        <li style="margin-bottom:10px;">
          <strong>Servicios:</strong> <?= $serviceReviews ?> rese&ntilde;a(s)
          <?php if ($serviceAvg !== null): ?>
            <span class="badge success" style="margin-left:6px;">&#9733; <?= number_format($serviceAvg, 1) ?></span>
          <?php endif; ?>
        </li>
      </ul>
    <?php endif; ?>
  </div>
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
