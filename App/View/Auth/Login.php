<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión · Reservas de Eventos</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
  <link rel="stylesheet" href="<?= e(css_url('auth/login')) ?>">
</head>
<body class="auth-page">
  <div class="auth-card container-narrow" style="position:relative;">
    <form method="post" action="<?= e(base_url('auth', 'cleanDemo')) ?>"
          style="position:absolute;top:12px;right:12px;"
          onsubmit="return confirm('¿Limpiar la base de datos y restaurar los datos de prueba? Esta acción eliminará todos los datos actuales.');">
      <?= csrf_field() ?>
      <button class="btn btn-secondary btn-sm" type="submit">clean</button>
    </form>
    <a class="btn btn-secondary btn-block" href="<?= e(base_url('venue', 'catalog')) ?>">&larr; Volver al inicio</a>
    <h1>Iniciar sesión</h1>
    <p class="subtitle">Accede a tu cuenta para reservar locales y servicios</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
      <div class="alert alert-success">Datos de prueba restaurados correctamente.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error"><?= e($_GET['error']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('auth', 'login')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input class="form-control" type="email" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        <div class="form-hint">Ej: analucia@correo.com</div>
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="password-wrapper">
          <input class="form-control" type="password" id="password" name="password" required>
          <button class="password-toggle" type="button" id="passwordToggle" aria-label="Mostrar contraseña">Mostrar</button>
        </div>
        <div class="form-hint">Ej: ClaveSegura7</div>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>

    <div class="auth-footer">
      ¿No tienes cuenta? <a href="<?= e(base_url('auth', 'showRegister')) ?>">Regístrate aquí</a>
    </div>
  </div>

  <script src="<?= e(js_url('auth/login')) ?>"></script>
</body>
</html>
