<?php require_once __DIR__ . '/../_header.php'; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (isset($bookings)): ?>
  <!-- Vista de reservas del admin -->
  <div class="page-head">
    <div>
      <h1>Reservas del mes</h1>
      <a href="<?= e(base_url('admin', 'dashboard')) ?>">&larr; Volver al panel</a>
    </div>
  </div>

  <div class="card" style="max-width:320px;margin-bottom:18px;">
    <form method="post" action="<?= e(base_url('admin', 'bookings')) ?>">
      <div class="form-group">
        <label for="month">Mes</label>
        <input class="form-control" type="month" id="month" name="month" value="<?= e(date('Y-m')) ?>">
      </div>
      <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
    </form>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="card empty"><span class="emoji">&#128197;</span>No hay reservas en este mes.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
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
              <td>#<?= (int) $b->getIdBooking() ?></td>
              <td>#<?= (int) $b->getIdClient() ?></td>
              <td>#<?= (int) $b->getIdLocal() ?></td>
              <td><?= e(date('d/m/Y', strtotime($b->getBookingDate()))) ?></td>
              <td>
                <?php
                  $badge = ['pendiente' => 'warning', 'confirmado' => 'success', 'cancelado' => 'neutral', 'rechazado' => 'danger'][$b->getBookingState()] ?? 'neutral';
                ?>
                <span class="badge <?= $badge ?>"><?= e($b->getBookingState()) ?></span>
              </td>
              <td>
                <div class="actions">
                  <form method="post" action="<?= e(base_url('admin', 'approvePayment')) ?>">
                    <input type="hidden" name="id" value="<?= (int) $b->getIdBooking() ?>">
                    <button class="btn btn-sm btn-success" type="submit">Aprobar pago</button>
                  </form>
                  <form method="post" action="<?= e(base_url('admin', 'rejectPayment')) ?>"
                        data-confirm="¿Rechazar el pago de esta reserva?">
                    <input type="hidden" name="id" value="<?= (int) $b->getIdBooking() ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Rechazar pago</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php else: ?>
  <!-- Vista de usuarios del admin -->
  <div class="page-head">
    <div>
      <h1>Gestión de usuarios</h1>
      <a href="<?= e(base_url('admin', 'dashboard')) ?>">&larr; Volver al panel</a>
    </div>
  </div>

  <div class="form-group" style="max-width:360px;margin-bottom:18px;">
    <input class="form-control" type="search" data-table-filter=".users-table"
           placeholder="Buscar por nombre, correo o teléfono...">
  </div>

  <?php
    $sections = [
      ['title' => 'Administradores', 'typeKey' => 'admin', 'items' => $admins ?? []],
      ['title' => 'Clientes', 'typeKey' => 'client', 'items' => $clients ?? []],
      ['title' => 'Propietarios', 'typeKey' => 'owner', 'items' => $owners ?? []],
    ];
  ?>

  <?php foreach ($sections as $section):
    $title = $section['title'];
    $typeKey = $section['typeKey'];
    $items = $section['items'];
  ?>
    <h2 style="font-size:1.1rem;color:var(--neutral-700);margin:20px 0 12px;"><?= $title ?></h2>
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
                  <?php if ($user->getIsActive()): ?>
                    <form method="post" action="<?= e(base_url('admin', 'deactivateUser')) ?>">
                      <input type="hidden" name="id" value="<?= (int) $user->getIdRol() ?>">
                      <input type="hidden" name="type" value="<?= e($typeKey) ?>">
                      <button class="btn btn-sm btn-warning" type="submit">Desactivar</button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= e(base_url('admin', 'activateUser')) ?>">
                      <input type="hidden" name="id" value="<?= (int) $user->getIdRol() ?>">
                      <button class="btn btn-sm btn-success" type="submit">Activar</button>
                    </form>
                  <?php endif; ?>
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
