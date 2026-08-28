<?php require_once __DIR__ . '/../_helpers.php';
// Vista de respaldo para edición de perfil de cliente.
$error = $error ?? null;
$client = $client ?? ($_SESSION['user'] ?? null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar perfil</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
</head>
<body class="auth-page">
  <div class="auth-card container-narrow">
    <h1>Editar perfil</h1>
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(base_url('client', 'updateProfile')) ?>">
      <div class="form-group">
        <label for="name">Nombre</label>
        <input class="form-control" type="text" id="name" name="name" required value="<?= e($client->getName()) ?>">
      </div>
      <div class="form-group">
        <label for="email">Correo</label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e($client->getEmail()) ?>">
      </div>
      <div class="form-group">
        <label for="phoneNumber">Teléfono</label>
        <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber" maxlength="8" value="<?= e($client->getPhoneNumber() ?? '') ?>">
      </div>
      <button class="btn btn-primary btn-block" type="submit">Guardar</button>
    </form>
  </div>
</body>
</html>
