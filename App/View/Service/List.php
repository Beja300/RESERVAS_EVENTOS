<?php require_once __DIR__ . '/../_header.php';

$venue = null;
// Intentamos cargar el nombre del local para mostrar contexto (opcional).
?>

<div class="page-head">
  <div>
    <h1>Servicios del local</h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Volver a mis locales</a>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('service', 'showForm', ['venueId' => $idVenue])) ?>">+ Nuevo servicio</a>
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
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Servicio</th>
          <th>Tipo</th>
          <th>Precio</th>
          <th>Estado</th>
          <th>Activo</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><strong><?= e($s->getNameService()) ?></strong></td>
            <td><?= $s->getTypeService() !== '' ? e($s->getTypeService()) : '—' ?></td>
            <td>&#8353; <?= number_format($s->getPriceService(), 2) ?></td>
            <td>
              <?php
                $badge = [
                  'aprobado' => 'success',
                  'solicitado' => 'warning',
                  'rechazado' => 'danger',
                ][$s->getStateService()] ?? 'neutral';
              ?>
              <span class="badge <?= $badge ?>"><?= e($s->getStateService()) ?></span>
            </td>
            <td><?= $s->getIsActive() ? 'Sí' : 'No' ?></td>
            <td>
              <a class="btn btn-sm btn-outline" href="<?= e(base_url('service', 'showForm', ['id' => $s->getIdService(), 'venueId' => $idVenue])) ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
