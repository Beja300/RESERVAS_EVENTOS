<?php require_once __DIR__ . '/../_helpers.php';
// Vista de respaldo para acciones de administración.
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administración</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
</head>
<body>
<header class="topbar"><div class="container"><a class="brand" href="<?= e(base_url('admin', 'dashboard')) ?>"><span>&#128187;</span> Administración</a></div></header>
<main class="container">
  <div class="page-head">
    <h1>Administración</h1>
  </div>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>
  <div class="grid">
    <a class="card hoverable" href="<?= e(base_url('admin', 'users')) ?>">
      <h3>Usuarios</h3>
      <p class="muted">Gestionar clientes, propietarios y administradores.</p>
    </a>
    <a class="card hoverable" href="<?= e(base_url('admin', 'bookings')) ?>">
      <h3>Reservas</h3>
      <p class="muted">Revisar y aprobar pagos del mes.</p>
    </a>
    <a class="card hoverable" href="<?= e(base_url('service', 'pending')) ?>">
      <h3>Servicios</h3>
      <p class="muted">Aprobar servicios solicitados por propietarios.</p>
    </a>
  </div>
</main>
</body>
</html>
