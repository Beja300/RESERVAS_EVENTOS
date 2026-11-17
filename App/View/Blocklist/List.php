<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Clientes bloqueados</h1>
    <p class="muted">Los clientes bloqueados no pueden crear nuevas reservas en tus locales.</p>
  </div>
</div>

<?php if (!empty($_GET['saved'])): ?>
  <div class="alert alert-success">Cliente bloqueado correctamente.</div>
<?php endif; ?>
<?php if (!empty($_GET['removed'])): ?>
  <div class="alert alert-success">Cliente desbloqueado correctamente.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($suggestions)): ?>
  <div class="card" style="margin-bottom:18px;">
    <h3 style="margin-top:0;">Sugerencias por morosidad</h3>
    <p class="muted">Clientes con cuotas vencidas en tus créditos.</p>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Cliente</th><th>Correo</th><th>Cuotas vencidas</th><th>Monto vencido</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($suggestions as $row): ?>
            <tr>
              <td><?= e($row['clientname']) ?></td>
              <td><?= e($row['clientemail']) ?></td>
              <td><?= (int) $row['overduecount'] ?></td>
              <td>&#8353; <?= number_format((float) $row['overdueamount'], 2) ?></td>
              <td>
                <form method="post" action="<?= e(base_url('blocklist', 'block')) ?>" style="display:flex;gap:8px;align-items:center;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="clientId" value="<?= (int) $row['tbclientid'] ?>">
                  <input class="form-control" type="text" name="reason" placeholder="Motivo" style="width:180px;">
                  <button class="btn btn-sm btn-outline" type="submit">Bloquear</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="card form-card form-page" style="margin-bottom:18px;">
  <h3 style="margin-top:0;">Bloquear un cliente</h3>
  <form method="post" action="<?= e(base_url('blocklist', 'block')) ?>">
    <?= csrf_field() ?>
    <div class="form-grid-2">
      <div class="form-group">
        <label for="email">Correo del cliente *</label>
        <input class="form-control" type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="reason">Motivo</label>
        <input class="form-control" type="text" id="reason" name="reason" maxlength="255" placeholder="Ej: cuotas vencidas">
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Bloquear cliente</button>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0;">Bloqueos activos</h3>
  <?php if (empty($blocked)): ?>
    <p class="muted">No tienes clientes bloqueados.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Cliente</th><th>Correo</th><th>Motivo</th><th>Fecha</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($blocked as $row): ?>
            <tr>
              <td><?= e($row['clientname'] ?? '') ?></td>
              <td><?= e($row['clientemail'] ?? '') ?></td>
              <td><?= e($row['tbownerblockedclientreason'] ?? '') ?></td>
              <td><?= e($row['tbownerblockedclientdate'] ?? '') ?></td>
              <td>
                <form method="post" action="<?= e(base_url('blocklist', 'unblock')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="blockId" value="<?= (int) $row['tbownerblockedclientid'] ?>">
                  <button class="btn btn-sm btn-outline" type="submit">Desbloquear</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
