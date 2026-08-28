<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Ubicaciones</h1>
    <p class="muted">Direcciones registradas en el sistema</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('location', 'showForm')) ?>">+ Nueva ubicación</a>
</div>

<?php if (empty($locations)): ?>
  <div class="card empty">
    <span class="emoji">&#128205;</span>
    No hay ubicaciones registradas.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Provincia</th>
          <th>Cantón</th>
          <th>Distrito</th>
          <th>Dirección</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($locations as $loc): ?>
          <tr>
            <td>#<?= (int) $loc->getIdLocation() ?></td>
            <td><?= e($loc->getProvinceLocation()) ?></td>
            <td><?= e($loc->getCantonLocation()) ?></td>
            <td><?= e($loc->getDistrictLocation()) ?></td>
            <td><?= $loc->getAddressLocation() !== '' ? e($loc->getAddressLocation()) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
