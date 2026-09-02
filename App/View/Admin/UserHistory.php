<?php $pageJs = ['admin/list']; ?>
<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Historial de acciones de usuarios</h1>
    <p class="muted">Todas las acciones registradas por los usuarios del sistema</p>
  </div>
</div>

<div class="form-group" style="max-width:420px;margin-bottom:18px;">
  <input class="form-control" type="search" data-table-filter=".user-history-table"
         placeholder="Buscar por responsable, acción, local o servicio...">
</div>

<?php if (empty($history)): ?>
  <div class="card empty">
    <span class="emoji">&#128220;</span>
    Aún no hay acciones registradas por los usuarios.
  </div>
<?php else: ?>
  <?php
    $actionLabels = [
      'VIEW'    => 'Vio',
      'SEARCH'  => 'Buscó',
      'BOOKING' => 'Reservó',
      'PURCHASE'=> 'Pagó',
      'APPROVE' => 'Aprobó',
      'FAVORITE'=> 'Marcó favorito',
    ];
  ?>
  <div class="table-wrap">
    <table class="table user-history-table">
      <thead>
        <tr>
          <th>Responsable</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>Detalle</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $h): ?>
          <?php
            $entity = $h['tbuserhistoryentity'] ?? '';
            $entityId = $h['tbuserhistoryentityid'];
            $action = $h['tbuserhistoryaction'] ?? '';

            if ($entity === 'Venue') {
              if ($action === 'SEARCH') {
                $entidad = 'Local (búsqueda)';
                $details = implode(', ', array_filter([
                  $h['locationProvince'] ?? null,
                  $h['locationCanton'] ?? null,
                  $h['locationDistrict'] ?? null,
                ]));
                $details = $details !== '' ? 'Búsqueda en catálogo: ' . $details : 'Búsqueda en el catálogo';
              } else {
                $entidad = 'Local';
                $details = !empty($h['venueName']) ? $h['venueName'] : ('Local #' . (int) $entityId);
              }
            } elseif ($entity === 'Service') {
              $entidad = 'Servicio';
              $details = !empty($h['serviceName']) ? $h['serviceName'] : ('Servicio #' . (int) $entityId);
            } else {
              $entidad = $entity !== '' ? $entity : '—';
              $details = $entityId !== null ? ('#' . (int) $entityId) : '—';
            }
          ?>
          <tr>
            <td><?= e($h['responsibleName'] ?? '—') ?></td>
            <td><span class="badge neutral"><?= e($actionLabels[$action] ?? $action) ?></span></td>
            <td><?= e($entidad) ?></td>
            <td><?= e($details) ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($h['tbuserhistorydate']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>