<?php require_once __DIR__ . '/../_header.php';
$client = $_SESSION['user'] ?? null;
?>

<div class="page-head">
  <div>
    <h1>Hola, <?= e($client ? $client->getName() : '') ?> &#128075;</h1>
    <p class="muted">Encuentra tu próximo evento aquí</p>
  </div>
  <a class="btn btn-primary" href="<?= e(base_url('venue', 'catalog')) ?>">Explorar locales</a>
</div>

<h2 style="font-size:1.15rem;color:var(--neutral-700);margin-bottom:14px;">Recomendados para ti</h2>
<?php if (empty($recommendations)): ?>
  <div class="card empty">
    <span class="emoji">&#128269;</span>
    Explora el catálogo para descubrir locales.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($recommendations as $r): ?>
      <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
        <div>
          <h3 style="color:var(--neutral-900);margin-bottom:6px;"><?= e($r->getNameVenue()) ?></h3>
          <p class="muted">Capacidad: <?= (int) $r->getCapacityVenue() ?></p>
        </div>
        <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'detail', ['id' => $r->getIdVenue()])) ?>">Ver local</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="page-head" style="margin-top:28px;">
  <h2 style="font-size:1.15rem;color:var(--neutral-700);">Mis reservas</h2>
  <a href="<?= e(base_url('booking', 'myBookings')) ?>">Ver todas &rarr;</a>
</div>
<?php if (empty($bookings)): ?>
  <div class="card empty">
    <span class="emoji">&#128197;</span>
    Aún no tienes reservas.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach (array_slice($bookings, 0, 3) as $b): ?>
      <div class="card">
        <div class="list-item" style="margin-bottom:0;box-shadow:none;border:none;padding:0;">
          <div>
            <div class="title">Local #<?= (int) $b->getIdLocal() ?></div>
            <div class="desc"><?= e(date('d/m/Y', strtotime($b->getBookingDate()))) ?></div>
          </div>
          <span class="badge warning"><?= e($b->getBookingState()) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
