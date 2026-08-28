<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión · Reservas de Eventos</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
</head>
<body class="auth-page">
  <div class="auth-card container-narrow">
    <h1>&#127881; Iniciar sesión</h1>
    <p class="subtitle">Accede a tu cuenta para reservar locales y servicios</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('auth', 'login')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input class="form-control" type="email" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>

    <div class="auth-footer">
      ¿No tienes cuenta? <a href="<?= e(base_url('auth', 'showRegister')) ?>">Regístrate aquí</a>
    </div>
  </div>
</body>
</html>
