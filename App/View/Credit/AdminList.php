<?php
require_once __DIR__ . '/../_header.php';
$stateLabels = [
  'pendiente' => ['Pendiente', 'warning'],
  'parcial'   => ['Abono parcial', 'info'],
  'pagado'    => ['Pagado', 'success'],
  'vencido'   => ['Vencido', 'danger'],
];
$totalOutstanding = 0.0;
?>

<div class="page-head">
  <div>
    <h1>Créditos y cuentas por cobrar</h1>
    <p class="muted">Todas las reservas financiadas de la plataforma.</p>
  </div>
</div>

<?php if (empty($credits)): ?>
  <div class="card empty">No hay créditos registrados.</div>
<?php else: ?>
  <div class="table-wrap card">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Cliente</th>
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
            if ($effective !== 'pagado') {
              $totalOutstanding += (float) $c['tbcreditbalance'];
            }
          ?>
          <tr>
            <td><?= (int) $c['tbcreditid'] ?></td>
            <td><?= e($c['clientname'] ?? '') ?></td>
            <td><?= e($c['tbvenuename'] ?? '') ?></td>
            <td><?= format_colones((float) $c['tbcredittotal']) ?></td>
            <td><?= format_colones((float) $c['tbcreditbalance']) ?></td>
            <td><?= e($c['tbcreditduedate']) ?></td>
            <td><span class="badge <?= e($tone) ?>"><?= e($label) ?></span></td>
            <td><a class="btn btn-sm btn-outline" href="<?= e(base_url('credit', 'detail', ['id' => (int) $c['tbcreditid']])) ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" style="text-align:right;">Saldo total pendiente</th>
          <th colspan="4" style="text-align:left;"><?= format_colones($totalOutstanding) ?></th>
        </tr>
      </tfoot>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
