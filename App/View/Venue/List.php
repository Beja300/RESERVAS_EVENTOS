<?php $pageJs = ['venue/list']; ?>
<?php require_once __DIR__ . '/../_header.php';

$activeVenues = array_values(array_filter($venues, static fn($v): bool => $v->getIsActive()));
$inactiveVenues = array_values(array_filter($venues, static fn($v): bool => !$v->getIsActive()));

$renderRow = static function ($v, bool $isActive): void { ?>
  <tr>
    <td><strong><?= e($v->getNameVenue()) ?></strong></td>
    <td><?= $v->getTypeVenue() !== '' ? e($v->getTypeVenue()) : 'General' ?></td>
    <td><?= (int) $v->getCapacityVenue() ?></td>
    <td>
      <div class="actions">
        <a class="btn btn-sm btn-outline" href="<?= e(base_url('venue', 'showForm', ['id' => $v->getIdVenue()])) ?>">Editar</a>
        <?php if ($isActive): ?>
          <a class="btn btn-sm btn-accent" href="<?= e(base_url('service', 'showForm', ['venueId' => $v->getIdVenue()])) ?>">Agregar servicio</a>
        <?php endif; ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(base_url('service', 'list', ['venueId' => $v->getIdVenue()])) ?>">Servicios</a>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('booking', 'venueBookings', ['venueId' => $v->getIdVenue()])) ?>">Reservas</a>
      </div>
    </td>
  </tr>
<?php };
?>

<div class="page-head">
  <div>
    <h1>Mis locales</h1>
    <p class="muted">Administra los locales de tu negocio</p>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(base_url('service', 'groups')) ?>">Editar servicios existentes</a>
    <a class="btn btn-accent" href="<?= e(base_url('service', 'showForm')) ?>">+ Agregar servicio</a>
    <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
  </div>
</div>

<div class="card" style="margin-bottom:18px;padding:16px;">
  <div class="actions" style="flex-wrap:wrap">
    <input class="form-control" type="search" id="venue-search"
           placeholder="Buscar por nombre del local..."
           style="max-width:320px;flex:1 1 220px;">
    <button class="btn btn-ghost" type="button" id="venue-clear">Limpiar búsqueda</button>
  </div>
</div>

<?php if (empty($venues)): ?>
  <div class="card empty">
    <span class="emoji">&#127968;</span>
    Aún no tienes locales registrados. Crea tu primer local.
  </div>
<?php else: ?>

  <h2 style="margin:0 0 12px;">Activos</h2>
  <?php if (empty($activeVenues)): ?>
    <div class="card empty">No hay locales activos.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table venue-table">
        <thead>
          <tr>
            <th>Local</th>
            <th>Tipo</th>
            <th>Capacidad</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activeVenues as $v): ?>
            <?php $renderRow($v, true); ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 style="margin:28px 0 12px;">Inactivos</h2>
  <?php if (empty($inactiveVenues)): ?>
    <div class="card empty">No hay locales inactivos.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table venue-table">
        <thead>
          <tr>
            <th>Local</th>
            <th>Tipo</th>
            <th>Capacidad</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inactiveVenues as $v): ?>
            <?php $renderRow($v, false); ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
