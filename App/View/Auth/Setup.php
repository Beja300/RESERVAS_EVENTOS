<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Primer administrador · Reservas de Eventos</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
  <link rel="stylesheet" href="<?= e(css_url('auth/login')) ?>">
  <link rel="stylesheet" href="<?= e(css_url('admin/password-toggle')) ?>">
</head>
<body class="auth-page">
  <div class="auth-card container-narrow">
    <a class="btn btn-secondary btn-block" href="<?= e(base_url('auth', 'showLogin')) ?>">&larr; Volver al inicio de sesión</a>
    <h1>Crear administrador inicial</h1>
    <p class="subtitle">El sistema no tiene administradores. Crea la primera cuenta para poder gestionar el proyecto.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('auth', 'createFirstAdmin')) ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="name">Nombre <span class="required-mark">*</span></label>
        <input class="form-control" type="text" id="name" name="name" required autofocus>
        <div class="form-hint">Ej: Laura Fernández</div>
      </div>

      <div class="form-group">
        <label for="email">Correo electrónico <span class="required-mark">*</span></label>
        <input class="form-control" type="email" id="email" name="email" required>
        <div class="form-hint">Ej: laura.fdez@correo.com</div>
      </div>

      <div class="form-group">
        <label for="password">Contraseña <span class="required-mark">*</span></label>
        <div class="password-wrapper">
          <input class="form-control" type="password" id="password" name="password"
                 minlength="8" required>
          <button class="password-toggle" type="button" id="passwordToggle" aria-label="Mostrar contraseña">Mostrar</button>
        </div>
        <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura1</div>
      </div>

      <div class="form-group">
        <label for="phoneNumber">Teléfono</label>
        <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber" maxlength="8" placeholder="8 dígitos">
        <div class="form-hint">Ej: 8888-7777</div>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Crear administrador</button>
    </form>

    <div class="auth-footer">
      ¿Ya creaste el administrador? <a href="<?= e(base_url('auth', 'showLogin')) ?>">Inicia sesión aquí</a>
    </div>
  </div>

  <script src="<?= e(js_url('admin/password-toggle')) ?>"></script>
</body>
</html>
