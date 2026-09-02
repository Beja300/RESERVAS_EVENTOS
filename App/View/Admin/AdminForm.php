<?php
$pageTitle = 'Nuevo administrador';
$pageCss = 'admin/password-toggle';
$pageJs = ['admin/password-toggle'];
require_once __DIR__ . '/../_header.php';
?>

<div class="page-head">
  <div>
    <h1>Nuevo administrador</h1>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:520px;">
  <form method="post" action="<?= e(base_url('admin', 'createAdmin')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="name">Nombre</label>
      <input class="form-control" type="text" id="name" name="name" required>
      <div class="form-hint">Ej: Laura Fernández</div>
    </div>
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required>
      <div class="form-hint">Ej: laura.fdez@correo.com</div>
    </div>
    <div class="form-group">
      <label for="password">Contraseña</label>
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
<div class="form-group" style="display:flex;gap:10px;margin-bottom:0;">
      <a class="btn btn-secondary" href="<?= e(base_url('admin', 'users')) ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Registrar administrador</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>