<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión · Reservas de Eventos</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
  <style>
    .password-wrapper { position: relative; }
    .password-wrapper .form-control { padding-right: 44px; }
    .password-toggle {
      position: absolute; top: 50%; right: 8px; transform: translateY(-50%);
      border: none; background: none; cursor: pointer; color: var(--neutral-500);
      font-size: 0.85rem; font-weight: 600; padding: 4px 8px; font-family: inherit;
    }
    .password-toggle:hover { color: var(--primary); }
  </style>
</head>
<body class="auth-page">
  <div class="auth-card container-narrow">
    <a class="btn btn-secondary btn-block" href="<?= e(base_url('venue', 'catalog')) ?>">&larr; Volver al inicio</a>
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

  <script>
    (function () {
      var input = document.getElementById('password');
      var toggle = document.getElementById('passwordToggle');

      toggle.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        input.focus();
      });
    })();
  </script>
</body>
</html>
