<?php require_once __DIR__ . '/../_header.php';

$activeServices = array_values(array_filter($services, static fn($s): bool => $s->getIsActive()));
$inactiveServices = array_values(array_filter($services, static fn($s): bool => !$s->getIsActive()));
$venueName = $venue !== null ? $venue->getNameVenue() : '';
?>

<div class="page-head">
  <div>
    <h1>Servicios<?= $venueName !== '' ? ' — ' . e($venueName) : ' del local' ?></h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Volver a mis locales</a>
  </div>
  <div class="actions">
    <a class="btn btn-ghost" href="<?= e(base_url('service', 'groups')) ?>">Servicios existentes</a>
    <a class="btn btn-primary" href="<?= e(base_url('service', 'showForm', ['venueId' => $idVenue])) ?>">+ Nuevo servicio</a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (empty($services)): ?>
  <div class="card empty">
    <span class="emoji">&#128722;</span>
    Este local aún no tiene servicios. Añade el primero.
  </div>
<?php else: ?>
  <h2 style="font-size:1.05rem;margin:18px 0 10px;">Activos</h2>
  <?php if (empty($activeServices)): ?>
    <div class="card empty" style="padding:18px;">No hay servicios activos en este local.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activeServices as $s): ?>
            <tr>
              <td><strong><?= e($s->getNameService()) ?></strong></td>
              <td><?= $s->getTypeService() !== '' ? e($s->getTypeService()) : '—' ?></td>
              <td>&#8353; <?= number_format($s->getPriceService(), 2) ?> / <?= e($s->getUnitService()) ?></td>
              <td>
                <?= (int) $s->getMinQuantity() ?>
                <?php if ($s->getMaxQuantity() !== null): ?>–<?= (int) $s->getMaxQuantity() ?><?php endif; ?>
                <?php if ($s->getIncludedQuantity() > 0): ?>
                  <?php $included = (int) $s->getIncludedQuantity(); ?>
                  <span class="muted">(<?= $included ?> incluida<?= $included === 1 ? '' : 's' ?>)</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="actions">
                  <a class="btn btn-sm btn-outline" href="<?= e(base_url('service', 'showForm', ['id' => $s->getIdService(), 'venueId' => $idVenue])) ?>">Editar</a>
                  <?php if ($s->getIdGroup() !== null): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= e(base_url('service', 'locations', ['groupId' => $s->getIdGroup()])) ?>">Locales</a>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-ghost" href="<?= e(base_url('service', 'transfer', ['id' => $s->getIdService()])) ?>">Mover/Duplicar</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <h2 style="font-size:1.05rem;margin:24px 0 10px;">Inactivos</h2>
  <?php if (empty($inactiveServices)): ?>
    <div class="card empty" style="padding:18px;">No hay servicios inactivos en este local.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inactiveServices as $s): ?>
            <tr>
              <td><strong><?= e($s->getNameService()) ?></strong></td>
              <td><?= $s->getTypeService() !== '' ? e($s->getTypeService()) : '—' ?></td>
              <td>&#8353; <?= number_format($s->getPriceService(), 2) ?> / <?= e($s->getUnitService()) ?></td>
              <td>
                <div class="actions">
                  <a class="btn btn-sm btn-outline" href="<?= e(base_url('service', 'showForm', ['id' => $s->getIdService(), 'venueId' => $idVenue])) ?>">Editar</a>
                  <?php if ($s->getIdGroup() !== null): ?>
                    <a class="btn btn-sm btn-ghost" href="<?= e(base_url('service', 'locations', ['groupId' => $s->getIdGroup()])) ?>">Locales</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
