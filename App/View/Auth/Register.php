<?php require_once __DIR__ . '/../_helpers.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrarse · Reservas de Eventos</title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
  <style>
    .tabs { display: flex; gap: 8px; margin-bottom: 22px; }
    .tabs button {
      flex: 1; padding: 10px; border: 1.5px solid var(--neutral-300);
      background: #fff; border-radius: 9px; font-weight: 600; cursor: pointer;
      color: var(--neutral-500); font-family: inherit;
    }
    .tabs button.active { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
  </style>
</head>
<body class="auth-page">
  <div class="auth-card container-narrow">
    <h1>&#128100; Crear cuenta</h1>
    <p class="subtitle">Únete a Reservas de Eventos</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist">
      <button type="button" class="active" data-tab="client" onclick="showTab('client')">Soy cliente</button>
      <button type="button" data-tab="owner" onclick="showTab('owner')">Soy propietario</button>
    </div>

    <form id="registerForm" method="post"
          data-action-client="<?= e(base_url('auth', 'registerClient')) ?>"
          data-action-owner="<?= e(base_url('auth', 'registerOwner')) ?>"
          action="<?= e(base_url('auth', 'registerClient')) ?>">
      <?= csrf_field() ?>

      <!-- Panel Cliente -->
      <div class="tab-panel active" id="panel-client">
        <div class="form-group">
          <label for="name">Nombre</label>
          <input class="form-control" type="text" id="name" name="name"
                 value="<?= e($_POST['name'] ?? '') ?>" required>
          <div class="form-hint">Ej: Ana Lucía</div>
        </div>
        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input class="form-control" type="email" id="email" name="email"
                 value="<?= e($_POST['email'] ?? '') ?>" required>
          <div class="form-hint">Ej: analucia@correo.com</div>
        </div>
        <div class="form-group">
          <label for="password">Contraseña</label>
          <input class="form-control" type="password" id="password" name="password"
                 minlength="8" required>
          <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura7</div>
        </div>
        <div class="form-group">
          <label for="phoneNumber">Teléfono</label>
          <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
                 value="<?= e($_POST['phoneNumber'] ?? '') ?>" maxlength="8" placeholder="8 dígitos">
          <div class="form-hint">Ej: 8888-7777</div>
        </div>
      </div>

      <!-- Panel Propietario -->
      <div class="tab-panel" id="panel-owner">
        <div class="form-group">
          <label for="ownerFirstName">Nombre</label>
          <input class="form-control" type="text" id="ownerFirstName" name="ownerFirstName"
                 value="<?= e($_POST['ownerFirstName'] ?? '') ?>" required>
          <div class="form-hint">Ej: María José</div>
        </div>
        <div class="form-group">
          <label for="ownerLastName">Apellidos</label>
          <input class="form-control" type="text" id="ownerLastName" name="ownerLastName"
                 value="<?= e($_POST['ownerLastName'] ?? '') ?>">
          <div class="form-hint">Ej: Pérez Rodríguez</div>
        </div>
        <div class="form-group">
          <label for="ownerAlias">Alias (apodo)</label>
          <input class="form-control" type="text" id="ownerAlias" name="ownerAlias"
                 value="<?= e($_POST['ownerAlias'] ?? '') ?>">
          <div class="form-hint">Ej: Mari</div>
        </div>
        <div class="form-group">
          <label for="ownerBusinessName">Nombre de negocio</label>
          <input class="form-control" type="text" id="ownerBusinessName" name="ownerBusinessName"
                 value="<?= e($_POST['ownerBusinessName'] ?? '') ?>" required>
          <div class="form-hint">Ej: Salon comunal</div>
        </div>
        <div class="form-group">
          <label for="ownerIdentification">Cédula / identificación</label>
          <input class="form-control" type="text" id="ownerIdentification" name="ownerIdentification"
                 value="<?= e($_POST['ownerIdentification'] ?? '') ?>">
          <div class="form-hint">Ej: 1-2345-0678</div>
        </div>
        <div class="form-group">
          <label for="ownerEmail">Correo electrónico</label>
          <input class="form-control" type="email" id="ownerEmail" name="email"
                 value="<?= e($_POST['email'] ?? '') ?>" required>
          <div class="form-hint">Ej: mariosepe@correo.com</div>
        </div>
        <div class="form-group">
          <label for="ownerPassword">Contraseña</label>
          <input class="form-control" type="password" id="ownerPassword" name="password"
                 minlength="8" required>
          <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura7</div>
        </div>
        <div class="form-group">
          <label for="ownerPhone">Teléfono</label>
          <input class="form-control" type="tel" id="ownerPhone" name="phoneNumber"
                 value="<?= e($_POST['phoneNumber'] ?? '') ?>" maxlength="8" placeholder="8 dígitos">
          <div class="form-hint">Ej: 8888-7777</div>
        </div>
      </div>

      <button class="btn btn-primary btn-block" type="submit">Registrarme</button>
    </form>

    <div class="auth-footer">
      ¿Ya tienes cuenta? <a href="<?= e(base_url('auth', 'showLogin')) ?>">Inicia sesión</a>
    </div>
  </div>

  <script src="<?= e(js_url()) ?>"></script>
  <script src="<?= e(js_url('auth-register')) ?>"></script>
  <script>document.addEventListener('DOMContentLoaded', App.init);</script>
</body>
</html>
