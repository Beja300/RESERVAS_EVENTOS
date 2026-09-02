<?php require_once __DIR__ . '/../_helpers.php';
// Vista de respaldo: listado de locales del propietario.
$venues = $venues ?? [];
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis locales</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
</head>
<body>
<header class="topbar"><div class="container"><a class="brand" href="<?= e(base_url('owner', 'dashboard')) ?>"><span>&#127881;</span> Mis locales</a></div></header>
<main class="container">
  <div class="page-head">
    <h1>Mis locales</h1>
    <a class="btn btn-primary" href="<?= e(base_url('venue', 'showForm')) ?>">+ Nuevo local</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (empty($venues)): ?>
    <div class="card empty"><span class="emoji">&#127968;</span>No tienes locales registrados.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($venues as $v): ?>
        <div class="card">
          <h3><?= e($v->getNameVenue()) ?></h3>
          <p class="muted">Capacidad: <?= (int) $v->getCapacityVenue() ?></p>
          <a class="btn btn-sm btn-outline" style="margin-top:12px;" href="<?= e(base_url('venue', 'showForm', ['id' => $v->getIdVenue()])) ?>">Editar</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<script src="<?= e(js_url()) ?>"></script>
<script>document.addEventListener('DOMContentLoaded', App.init);</script>
</body>
</html>
