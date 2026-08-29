<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Servicios por aprobar</h1>
    <p class="muted">Revisa y aprueba o rechaza los servicios solicitados</p>
  </div>
</div>

<?php if (empty($services)): ?>
  <div class="card empty">
    <span class="emoji">&#9989;</span>
    No hay servicios pendientes de revisión.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
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

<script>
  (function () {
    document.querySelectorAll('form[data-ajax-service-action]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch(form.getAttribute('action'), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: new FormData(form)
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
          .then(function (r) {
            window.App && App.toast(r.data.message, r.ok ? 'success' : 'error');
            if (r.ok) {
              var row = form.closest('tr');
              if (row) row.remove();
              if (!document.querySelector('tbody tr') || !document.querySelector('#pending-count')) {
                setTimeout(function () { window.location.reload(); }, 700);
              }
            }
          });
      });
    });
  })();
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>
