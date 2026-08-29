<?php
$pageTitle = 'Nuevo propietario';
require_once __DIR__ . '/../_header.php';
?>

<div class="page-head">
  <div>
    <h1>Nuevo propietario</h1>
  </div>
</div>

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

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:520px;">
  <form method="post" action="<?= e(base_url('admin', 'createOwner')) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="ownerFirstName">Nombre</label>
      <input class="form-control" type="text" id="ownerFirstName" name="ownerFirstName" required>
      <div class="form-hint">Ej: María José</div>
    </div>
    <div class="form-group">
      <label for="ownerLastName">Apellidos</label>
      <input class="form-control" type="text" id="ownerLastName" name="ownerLastName">
      <div class="form-hint">Ej: Pérez Rodríguez</div>
    </div>
    <div class="form-group">
      <label for="ownerAlias">Alias (apodo)</label>
      <input class="form-control" type="text" id="ownerAlias" name="ownerAlias">
      <div class="form-hint">Ej: Mari</div>
    </div>
    <div class="form-group">
      <label for="ownerBusinessName">Nombre de negocio</label>
      <input class="form-control" type="text" id="ownerBusinessName" name="ownerBusinessName" required>
      <div class="form-hint">Ej: Pastelería Doña Tere</div>
    </div>
    <div class="form-group">
      <label for="ownerIdentification">Cédula / identificación</label>
      <input class="form-control" type="text" id="ownerIdentification" name="ownerIdentification">
      <div class="form-hint">Ej: 1-2345-0678</div>
    </div>
    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required>
      <div class="form-hint">Ej: mariosepe@correo.com</div>
    </div>
    <div class="form-group">
      <label for="password">Contraseña</label>
      <div class="password-wrapper">
        <input class="form-control" type="password" id="password" name="password"
               minlength="8" required>
        <button class="password-toggle" type="button" id="passwordToggle" aria-label="Mostrar contraseña">Mostrar</button>
      </div>
      <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura7</div>
    </div>
    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber" maxlength="8" placeholder="8 dígitos">
      <div class="form-hint">Ej: 8888-7777</div>
    </div>
    <div class="form-group" style="display:flex;gap:10px;margin-bottom:0;">
      <a class="btn btn-secondary" href="<?= e(base_url('admin', 'users')) ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Registrar propietario</button>
    </div>
  </form>
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
<?php require_once __DIR__ . '/../_footer.php'; ?>