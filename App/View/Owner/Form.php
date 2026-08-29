<?php require_once __DIR__ . '/../_header.php';

$owner = $owner ?? ($_SESSION['user'] ?? null);
if ($owner === null) {
  echo '<div class="alert alert-error">Sesión no válida. Inicia sesión de nuevo.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

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

<div class="page-head">
  <div>
    <h1>Mi perfil</h1>
  </div>
</div>

<?php if (isset($_GET['updated'])): ?>
  <div class="alert alert-success">Tus datos se actualizaron correctamente.</div>
<?php elseif (isset($_GET['removed'])): ?>
  <div class="alert alert-success">Foto de perfil eliminada.</div>
<?php endif; ?>

<div class="card form-card" style="max-width:560px;margin-bottom:16px;">
  <h3 class="card-title">Foto de perfil</h3>
  <?php if ($owner->getImageOwner() !== ''): ?>
    <div style="text-align:center;">
      <img src="<?= e(image_url($owner->getImageOwner())) ?>" alt="Foto de perfil"
           style="display:inline-block;width:128px;height:128px;border-radius:50%;object-fit:cover;vertical-align:middle;box-shadow:0 0 0 6px #fff,0 0 0 7px var(--neutral-200),0 6px 16px rgba(0,0,0,.18);margin-bottom:14px;">
      <form method="post" action="<?= e(base_url('owner', 'removePhoto')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-warning" type="submit"
                onclick="return confirm('¿Eliminar tu foto de perfil?');">Eliminar foto</button>
      </form>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:0;">Aún no tienes foto de perfil.</p>
  <?php endif; ?>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('owner', 'updateProfile')) ?>"
        enctype="multipart/form-data" data-validate-form>
    <?= csrf_field() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label>Cambiar foto de perfil</label>
      <input class="form-control" type="file" id="image" name="image" accept="image/*">
      <small class="muted">Sube una foto (jpg, png, webp, gif; máx. 2 MB).</small>
    </div>

    <div class="form-group">
      <label>O bien, imagen por URL</label>
      <input class="form-control" type="text" id="imageUrl" name="imageUrl"
             placeholder="https://..."
             value="<?= e(trim($_POST['imageUrl'] ?? '')) ?>">
    </div>

    <h3 class="card-title">Datos del negocio</h3>

    <div class="form-group">
      <label for="name">Nombre de negocio</label>
      <input class="form-control" type="text" id="name" name="name"
             value="<?= e($_POST['name'] ?? $owner->getName()) ?>">
      <div class="form-hint">Ej: Salon comunal</div>
    </div>

    <div class="form-group">
      <label for="ownerLastName">Apellidos</label>
      <input class="form-control" type="text" id="ownerLastName" name="ownerLastName"
             value="<?= e($_POST['ownerLastName'] ?? $owner->getLastNameOwner()) ?>">
      <div class="form-hint">Ej: Pérez Rodríguez</div>
    </div>

    <div class="form-group">
      <label for="ownerAlias">Alias (apodo)</label>
      <input class="form-control" type="text" id="ownerAlias" name="ownerAlias"
             value="<?= e($_POST['ownerAlias'] ?? $owner->getAliasOwner()) ?>">
      <div class="form-hint">Ej: Mari</div>
    </div>

    <div class="form-group">
      <label for="ownerIdentification">Cédula / identificación</label>
      <input class="form-control" type="text" id="ownerIdentification" name="ownerIdentification"
             value="<?= e($_POST['ownerIdentification'] ?? $owner->getIdentificationNumberOwner()) ?>">
      <div class="form-hint">Ej: 1-2345-0678</div>
    </div>

    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required
             data-validate="email"
             value="<?= e($_POST['email'] ?? $owner->getEmail()) ?>">
    </div>

    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
             data-validate="phone"
             maxlength="8" value="<?= e($_POST['phoneNumber'] ?? $owner->getPhoneNumber() ?? '') ?>">
      <div class="form-hint">Ej: 8888-7777</div>
    </div>

    <?php $showPasswordFields = !empty($_POST['currentPassword']) || !empty($_POST['newPassword']); ?>
    <h3 class="card-title" style="margin-top:22px;">Cambiar contraseña</h3>
    <label class="checkbox" style="margin-bottom:12px;">
      <input type="checkbox" id="changePasswordCheck" <?= $showPasswordFields ? 'checked' : '' ?>>
      Quiero cambiar mi contraseña
    </label>

    <div id="passwordFields" <?= $showPasswordFields ? '' : 'style="display:none;"' ?>>
      <div class="form-group">
        <label for="currentPassword">Contraseña actual</label>
        <div class="password-wrapper">
          <input class="form-control" type="password" id="currentPassword" name="currentPassword" autocomplete="current-password">
          <button class="password-toggle" type="button" id="currentPasswordToggle" aria-label="Mostrar contraseña">Mostrar</button>
        </div>
      </div>

      <div class="form-group">
        <label for="newPassword">Contraseña nueva</label>
        <div class="password-wrapper">
          <input class="form-control" type="password" id="newPassword" name="newPassword" minlength="8" autocomplete="new-password">
          <button class="password-toggle" type="button" id="newPasswordToggle" aria-label="Mostrar contraseña">Mostrar</button>
        </div>
        <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura7</div>
      </div>
    </div>

    <button class="btn btn-primary" type="submit">Guardar cambios</button>
  </form>
</div>

<script>
  (function () {
    var pwCheck = document.getElementById('changePasswordCheck');
    var pwFields = document.getElementById('passwordFields');
    if (pwCheck && pwFields) {
      pwCheck.addEventListener('change', function () {
        pwFields.style.display = pwCheck.checked ? '' : 'none';
        if (!pwCheck.checked) {
          document.getElementById('currentPassword').value = '';
          document.getElementById('newPassword').value = '';
        }
      });
    }

    [['currentPassword', 'currentPasswordToggle'], ['newPassword', 'newPasswordToggle']].forEach(function (pair) {
      var input = document.getElementById(pair[0]);
      var toggle = document.getElementById(pair[1]);

      toggle.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        input.focus();
      });
    });
  })();
</script>

<?php require_once __DIR__ . '/../_footer.php'; ?>