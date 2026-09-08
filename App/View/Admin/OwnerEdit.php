<?php
$pageTitle = 'Editar propietario';
$pageCss = 'admin/password-toggle';
$pageJs = ['admin/password-toggle'];
require_once __DIR__ . '/../_header.php';
$u = $user;
?>

<div class="page-head">
  <div>
    <h1>Editar propietario</h1>
    <a href="<?= e(base_url('admin', 'users')) ?>">&larr; Volver a usuarios</a>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:520px;">
  <form method="post" action="<?= e(base_url('admin', 'updateUser')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $u->getIdRol() ?>">
    <input type="hidden" name="type" value="owner">
    <div class="form-group">
      <label for="name">Nombre de negocio</label>
      <input class="form-control" type="text" id="name" name="name"
             value="<?= e($_POST['name'] ?? $u->getName()) ?>" required>
      <div class="form-hint">Ej: Pastelería Doña Tere</div>
    </div>
    <div class="form-group">
      <label for="ownerLastName">Apellidos</label>
      <input class="form-control" type="text" id="ownerLastName" name="ownerLastName"
             value="<?= e($_POST['ownerLastName'] ?? $u->getLastNameOwner()) ?>">
      <div class="form-hint">Ej: Pérez Rodríguez</div>
    </div>
    <div class="form-group">
      <label for="ownerAlias">Alias (apodo)</label>
      <input class="form-control" type="text" id="ownerAlias" name="ownerAlias"
             value="<?= e($_POST['ownerAlias'] ?? $u->getAliasOwner()) ?>">
      <div class="form-hint">Ej: Mari</div>
    </div>
    <div class="form-group">
      <label for="ownerIdentification">Cédula / identificación</label>
      <input class="form-control" type="text" id="ownerIdentification" name="ownerIdentification"
             value="<?= e($_POST['ownerIdentification'] ?? $u->getIdentificationNumberOwner()) ?>">
      <div class="form-hint">Ej: 1-2345-0678</div>
    </div>
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email"
             value="<?= e($_POST['email'] ?? $u->getEmail()) ?>" required>
      <div class="form-hint">Ej: mariosepe@correo.com</div>
    </div>
    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
             value="<?= e($_POST['phoneNumber'] ?? $u->getPhoneNumber() ?? '') ?>"
             maxlength="8" placeholder="8 dígitos">
      <div class="form-hint">Ej: 8888-7777</div>
    </div>
    <div class="form-group">
      <label for="password">Contraseña (opcional)</label>
      <div class="password-wrapper">
        <input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password">
        <button class="password-toggle" type="button" id="passwordToggle" aria-label="Mostrar contraseña">Mostrar</button>
      </div>
      <div class="form-hint">Déjala vacía para no cambiarla. Mínimo 8 caracteres y un número.</div>
    </div>
    <div class="form-group" style="display:flex;gap:10px;margin-bottom:0;">
      <a class="btn btn-secondary" href="<?= e(base_url('admin', 'users')) ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>