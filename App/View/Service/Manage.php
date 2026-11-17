<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Servicios existentes</h1>
    <a href="<?= e(base_url('venue', 'list')) ?>">&larr; Volver a mis locales</a>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('service', 'showForm')) ?>">+ Agregar servicio</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (empty($groups)): ?>
  <div class="card empty">
    <span class="emoji">&#128722;</span>
    Todavía no tienes servicios registrados.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Servicio</th>
          <th>Locales activos</th>
          <th>Dónde se ofrece</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($groups as $entry): ?>
          <?php
            $group = $entry['group'];
            $services = $entry['services'];
            $activeCount = (int) $entry['activeCount'];
            $venueNames = $entry['venueNames'];
            $firstService = $services[0] ?? null;
          ?>
          <tr>
            <td>
              <strong><?= e($group->getName()) ?></strong>
              <?php if ($activeCount === 0): ?>
                <br><span class="badge neutral">Sin locales activos</span>
              <?php endif; ?>
            </td>
            <td><?= $activeCount ?></td>
            <td>
              <?= $venueNames !== [] ? e(implode(', ', $venueNames)) : '—' ?>
            </td>
            <td>
              <div class="actions">
                <a class="btn btn-sm btn-primary" href="<?= e(base_url('service', 'locations', ['groupId' => $group->getIdGroup()])) ?>">Administrar locales</a>
                <?php if ($firstService !== null): ?>
                  <a class="btn btn-sm btn-ghost" href="<?= e(base_url('service', 'transfer', ['id' => $firstService->getIdService()])) ?>">Mover/Duplicar</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
