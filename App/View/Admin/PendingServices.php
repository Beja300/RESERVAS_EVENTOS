<?php $pageJs = ['admin/pending-services']; ?>
<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Servicios</h1>
    <p class="muted">Revisa y aprueba o rechaza los servicios solicitados</p>
  </div>
</div>

<!-- ===================== FILTRO GLOBAL ===================== -->
<div class="card" style="margin-bottom:24px;padding:16px;">
  <div class="actions" style="flex-wrap:wrap">
    <input class="form-control" type="text" id="filter-global"
           placeholder="Filtrar en ambas tablas (nombre, tipo, local, estado, admin...)"
           style="max-width:420px;flex:1 1 260px;">
    <select class="form-control" id="filter-history-state" style="max-width:180px;">
      <option value="">Historial: todos los estados</option>
      <option value="aprobado">Aprobado</option>
      <option value="rechazado">Rechazado</option>
    </select>
  </div>
</div>

<!-- ===================== SERVICIOS POR APROBAR ===================== -->
<div class="card" style="margin-bottom:24px;">
  <div class="page-head">
    <div>
      <h2 style="margin:0;">Servicios por aprobar</h2>
    </div>
  </div>

  <?php if (empty($services)): ?>
    <div class="card empty">
      <span class="emoji">&#9989;</span>
      No hay servicios pendientes de revisión.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table" id="table-pending">
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Local</th>
            <th>Estado</th>
            <th class="actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($services as $s): ?>
            <tr>
              <td><strong><?= e($s->getNameService()) ?></strong></td>
              <td><?= $s->getTypeService() !== '' ? e($s->getTypeService()) : '—' ?></td>
              <td>&#8353; <?= number_format($s->getPriceService(), 2) ?></td>
              <td>#<?= (int) $s->getIdLocal() ?></td>
              <td><span class="badge warning"><?= e($s->getStateService()) ?></span></td>
              <td>
                <div class="actions">
                  <a class="btn btn-sm" href="<?= e(base_url('service', 'detail', ['id' => $s->getIdService()])) ?>">Ver detalles</a>
                  <form method="post" action="<?= e(base_url('service', 'approve')) ?>"
                        data-ajax-service-action="approve" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $s->getIdService() ?>">
                    <button class="btn btn-sm btn-success" type="submit">Aprobar</button>
                  </form>
                  <form method="post" action="<?= e(base_url('service', 'reject')) ?>"
                        data-ajax-service-action="reject" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $s->getIdService() ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Rechazar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- ===================== HISTORIAL ===================== -->
<div class="card">
  <div class="page-head">
    <div>
      <h2 style="margin:0;">Historial de aprobaciones</h2>
    </div>
  </div>

  <?php if (empty($history)): ?>
    <div class="card empty">
      <span class="emoji">&#128220;</span>
      Aún no hay servicios aprobados o rechazados.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table" id="table-history">
        <thead>
          <tr>
            <th>Servicio</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Local</th>
            <th>Estado</th>
            <th>Aprobado por</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><strong><?= e($h['tbservicename']) ?></strong></td>
              <td><?= !empty($h['tbservicetype']) ? e($h['tbservicetype']) : '—' ?></td>
              <td>&#8353; <?= number_format((float) $h['tbserviceprice'], 2) ?></td>
              <td><?= !empty($h['venueName']) ? e($h['venueName']) : '—' ?></td>
              <td>
                <?php $badge = $h['tbservicestate'] === 'aprobado' ? 'success' : 'danger'; ?>
                <span class="badge <?= $badge ?>"><?= e($h['tbservicestate']) ?></span>
              </td>
              <td><?= !empty($h['approvedByName']) ? e($h['approvedByName']) : '—' ?></td>
              <td>
                <?php if (!empty($h['tbserviceapprovedon'])): ?>
                  <?= e(date('d/m/Y H:i', strtotime($h['tbserviceapprovedon']))) ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
