<?php require_once __DIR__ . '/../_header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (isset($bookings)): ?>
  <!-- Vista de reservas del admin -->
  <div class="page-head">
    <div>
      <h1>Panel de reservas</h1>
      <p class="muted">Reservas del mes de <?= date('F Y', strtotime($yearMonth . '-01')) ?></p>
    </div>
  </div>

  <div class="card" style="margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <a class="btn btn-sm" href="<?= e(base_url('admin', 'bookings', ['month' => $prevMonth])) ?>">&larr; Mes anterior</a>
      <form method="get" action="<?= e(base_url('admin', 'bookings')) ?>" style="display:flex;align-items:center;gap:8px;">
        <input type="hidden" name="controller" value="admin">
        <input type="hidden" name="action" value="bookings">
        <input class="form-control" style="width:auto;" type="month" name="month" value="<?= e($yearMonth) ?>">
        <button class="btn btn-primary btn-sm" type="submit">Ver mes</button>
      </form>
      <a class="btn btn-sm" href="<?= e(base_url('admin', 'bookings', ['month' => $nextMonth])) ?>">Mes siguiente &rarr;</a>
    </div>
  </div>

  <?php if (!empty($refundsPending)): ?>
    <div class="card" style="border-color:var(--warning);margin-bottom:18px;">
      <h2 style="font-size:1.05rem;color:var(--warning);margin-bottom:10px;">
        Solicitudes de reembolso pendientes (<?= count($refundsPending) ?>)
      </h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Reserva</th>
              <th>Cliente</th>
              <th>Local</th>
              <th>Fecha</th>
              <th>Motivo</th>
              <th class="actions">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($refundsPending as $rf): ?>
              <tr>
                <td>#<?= (int) $rf['tbbookingrefundbookingid'] ?></td>
                <td><?= e($rf['clientName'] ?? '—') ?></td>
                <td><?= e($rf['venueName'] ?? '—') ?></td>
                <td><?= e(date('d/m/Y', strtotime($rf['tbbookingdate']))) ?></td>
                <td><?= e($rf['tbbookingrefunddetail']) ?></td>
                <td>
                  <a class="btn btn-sm btn-primary"
                     href="<?= e(base_url('admin', 'bookingDetail', ['id' => $rf['tbbookingrefundbookingid']])) ?>">Revisar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($bookings)): ?>
    <div class="card empty"><span class="emoji">&#128197;</span>No hay reservas en este mes.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <!-- <th>ID</th> -->
            <th>Cliente</th>
            <th>Local</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <!-- <td>#<?= (int) $b['tbbookingid'] ?></td> -->
              <td><?= e($b['clientName'] ?? '—') ?></td>
              <td><?= e($b['venueName'] ?? '—') ?></td>
              <td><?= e(date('d/m/Y', strtotime($b['tbbookingdate']))) ?></td>
              <td>
                <?php
                  $badge = ['pendiente' => 'warning', 'confirmado' => 'success', 'cancelado' => 'neutral', 'rechazado' => 'danger'][$b['tbbookingstate']] ?? 'neutral';
                ?>
                <span class="badge <?= $badge ?>"><?= e($b['tbbookingstate']) ?></span>
              </td>
              <td>
                <a class="btn btn-sm btn-primary"
                   href="<?= e(base_url('admin', 'bookingDetail', ['id' => $b['tbbookingid']])) ?>">Ver detalle</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 style="font-size:1.1rem;color:var(--neutral-700);margin:24px 0 12px;">
    Historial global de modificaciones
  </h2>
  <div class="form-group" style="max-width:360px;margin-bottom:12px;">
    <input class="form-control" type="search" data-table-filter=".history-table"
           placeholder="Buscar por reserva, responsable o acción...">
  </div>
  <?php if (empty($history)): ?>
    <p class="muted">Aún no hay modificaciones registradas.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table history-table">
        <thead>
          <tr>
            <th>Reserva</th>
            <th>Cliente</th>
            <th>Fecha reserva</th>
            <th>Responsable</th>
            <th>Acción</th>
            <th>Detalle</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td>#<?= (int) $h['tbbookinghistorybookingid'] ?></td>
              <td><?= e($h['clientName'] ?? '—') ?></td>
              <td><?= e($h['tbbookingdate'] ? date('d/m/Y', strtotime($h['tbbookingdate'])) : '—') ?></td>
              <td><?= e($h['responsibleName'] ?? '—') ?></td>
              <td><span class="badge neutral"><?= e($h['tbbookinghistoryaction']) ?></span></td>
              <td><?= e($h['tbbookinghistorydetail'] ?? '—') ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($h['tbbookinghistorydate']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php else: ?>
  <!-- Vista de usuarios del admin -->

  <?php if (isset($_GET['created']) || isset($_GET['updated'])): ?>
    <?php
      $flashType = isset($_GET['updated']) ? 'updated' : 'created';
      $flashKey = $_GET[$flashType];
      $createdMsg = [
        '1' => 'Administrador creado correctamente.',
        'client' => 'Cliente creado correctamente.',
        'owner' => 'Propietario creado correctamente.',
      ];
      $updatedMsg = [
        'admin' => 'Administrador actualizado correctamente.',
        'client' => 'Cliente actualizado correctamente.',
        'owner' => 'Propietario actualizado correctamente.',
      ];
      $flashMsg = ($flashType === 'updated' ? $updatedMsg : $createdMsg)[$flashKey]
        ?? 'Cambios guardados correctamente.';
    ?>
    <div class="alert alert-success"><?= e($flashMsg) ?></div>
  <?php endif; ?>

  <div class="form-group" style="max-width:360px;margin-bottom:18px;">
    <input class="form-control" type="search" data-table-filter=".users-table"
           placeholder="Buscar por nombre, correo o teléfono...">
  </div>

  <?php
    $sections = [
      [
        'title' => 'Administradores', 'typeKey' => 'admin', 'items' => $admins ?? [],
        'addUrl' => base_url('admin', 'showAdminForm'), 'addLabel' => '+ Agregar administrador',
      ],
      [
        'title' => 'Clientes', 'typeKey' => 'client', 'items' => $clients ?? [],
        'addUrl' => base_url('admin', 'showClientForm'), 'addLabel' => '+ Agregar cliente',
      ],
      [
        'title' => 'Propietarios', 'typeKey' => 'owner', 'items' => $owners ?? [],
        'addUrl' => base_url('admin', 'showOwnerForm'), 'addLabel' => '+ Agregar propietario',
      ],
    ];
  ?>

  <?php foreach ($sections as $section):
    $title = $section['title'];
    $typeKey = $section['typeKey'];
    $items = $section['items'];
  ?>
    <h2 style="font-size:1.1rem;color:var(--neutral-700);margin:20px 0 12px;display:flex;justify-content:space-between;align-items:center;">
      <?= $title ?>
      <?php if (!empty($section['addUrl'])): ?>
        <a class="btn btn-sm btn-primary" href="<?= e($section['addUrl']) ?>"><?= e($section['addLabel']) ?></a>
      <?php endif; ?>
    </h2>
    <?php if (empty($items)): ?>
      <p class="muted">No hay registros.</p>
    <?php else: ?>
      <div class="table-wrap" style="margin-bottom:16px;">
        <table class="table users-table">
          <thead>
            <tr>
              <th>ID Rol</th>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Teléfono</th>
              <th>Estado</th>
              <th class="actions">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $user): ?>
              <tr>
                <td>#<?= (int) $user->getIdRol() ?></td>
                <td><?= e($user->getName()) ?></td>
                <td><?= e($user->getEmail()) ?></td>
                <td><?= e($user->getPhoneNumber() ?? '—') ?></td>
                <td>
                  <?= $user->getIsActive() ? '<span class="badge success">Activo</span>' : '<span class="badge neutral">Inactivo</span>' ?>
                </td>
                <td>
                  <div class="actions">
                    <a class="btn btn-sm btn-secondary"
                       href="<?= e(base_url('admin', 'showEditForm', ['type' => $typeKey, 'id' => $user->getIdRol()])) ?>">Editar</a>
                    <?php if ($user->getIsActive()): ?>
                      <form method="post" action="<?= e(base_url('admin', 'deactivateUser')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $user->getIdRol() ?>">
                        <input type="hidden" name="type" value="<?= e($typeKey) ?>">
                        <button class="btn btn-sm btn-warning" type="submit">Desactivar</button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="<?= e(base_url('admin', 'activateUser')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $user->getIdRol() ?>">
                        <button class="btn btn-sm btn-success" type="submit">Activar</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<script src="<?= e(js_url('admin-list')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>
