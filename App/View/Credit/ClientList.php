<?php
require_once __DIR__ . '/../_header.php';
$stateLabels = [
  'pendiente' => ['Pendiente', 'warning'],
  'parcial'   => ['Abono parcial', 'info'],
  'pagado'    => ['Pagado', 'success'],
  'vencido'   => ['Vencido', 'danger'],
];
?>

<div class="page-head">
  <div>
    <h1>Mis créditos</h1>
    <p class="muted">Reservas financiadas y su saldo pendiente.</p>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <strong>Saldo total pendiente:</strong>
  <span style="font-size:1.3rem;color:var(--neutral-900);">&#8353; <?= number_format($outstanding, 2) ?></span>
</div>

<?php if (empty($credits)): ?>
  <div class="card empty">No tienes créditos activos.</div>
<?php else: ?>
  <div class="table-wrap card">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Local</th>
          <th>Total</th>
          <th>Saldo</th>
          <th>Vence</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($credits as $c): ?>
          <?php
            $state = $c['tbcreditstate'];
            $effective = ($state !== 'pagado' && $c['tbcreditduedate'] < date('Y-m-d')) ? 'vencido' : $state;
            [$label, $tone] = $stateLabels[$effective] ?? [$effective, 'info'];
          ?>
          <tr>
            <td><?= (int) $c['tbcreditid'] ?></td>
            <td><?= e($c['tbvenuename'] ?? '') ?></td>
            <td>&#8353; <?= number_format((float) $c['tbcredittotal'], 2) ?></td>
            <td>&#8353; <?= number_format((float) $c['tbcreditbalance'], 2) ?></td>
            <td><?= e($c['tbcreditduedate']) ?></td>
            <td><span class="badge <?= e($tone) ?>"><?= e($label) ?></span></td>
            <td><a class="btn btn-sm btn-outline" href="<?= e(base_url('credit', 'detail', ['id' => (int) $c['tbcreditid']])) ?>">Ver / Abonar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
