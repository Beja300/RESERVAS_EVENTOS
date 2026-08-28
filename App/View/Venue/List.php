<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Mis locales</h1>
    <p class="muted">Administra los locales de tu negocio</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
</div>

<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    Aún no tienes locales registrados. Crea tu primer local.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Local</th>
          <th>Tipo</th>
          <th>Capacidad</th>
          <th>Estado</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($venues as $v): ?>
          <tr>
            <td><strong><?= e($v->getNameVenue()) ?></strong></td>
            <td><?= $v->getTypeVenue() !== '' ? e($v->getTypeVenue()) : 'General' ?></td>
            <td><?= (int) $v->getCapacityVenue() ?></td>
            <td>
              <?= $v->getIsActive() ? '<span class="badge success">Activo</span>' : '<span class="badge neutral">Inactivo</span>' ?>
            </td>
            <td>
              <div class="actions">
                <a class="btn btn-sm btn-outline" href="<?= e(base_url('venue', 'showForm', ['id' => $v->getIdVenue()])) ?>">Editar</a>
                <a class="btn btn-sm btn-accent" href="<?= e(base_url('service', 'list', ['venueId' => $v->getIdVenue()])) ?>">Servicios</a>
                <a class="btn btn-sm btn-primary" href="<?= e(base_url('booking', 'venueBookings', ['venueId' => $v->getIdVenue()])) ?>">Reservas</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
