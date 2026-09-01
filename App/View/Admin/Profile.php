<?php require_once __DIR__ . '/../_header.php';

$admin = $_SESSION['user'] ?? null;
if ($admin === null) {
  echo '<div class="alert alert-error">Sesión no válida. Inicia sesión de nuevo.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

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

<div class="card form-card" style="max-width:560px;margin-bottom:16px;">
  <h3 class="card-title">Foto de perfil</h3>
  <div style="text-align:center;">
    <img id="adminPhotoPreview" src="<?= $admin->getImageAdmin() !== '' ? e(image_url($admin->getImageAdmin())) : '' ?>" alt="Foto de perfil"
         style="display:<?= $admin->getImageAdmin() !== '' ? 'inline-block' : 'none' ?>;width:128px;height:128px;border-radius:50%;object-fit:cover;vertical-align:middle;box-shadow:0 0 0 6px #fff,0 0 0 7px var(--neutral-200),0 6px 16px rgba(0,0,0,.18);margin-bottom:14px;">
    <?php if ($admin->getImageAdmin() !== ''): ?>
      <form method="post" action="<?= e(base_url('admin', 'removePhoto')) ?>" data-ajax-admin-photo>
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-warning" type="submit">Eliminar foto</button>
      </form>
    <?php else: ?>
      <p class="muted" style="margin:0;">Aún no tienes foto de perfil.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('admin', 'updateProfile')) ?>"
        enctype="multipart/form-data" data-validate-form data-ajax-admin-profile>
    <?= csrf_field() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label>Cambiar foto de perfil</label>
      <input class="form-control" type="file" id="image" name="image" accept="image/*"
             data-photo-input="adminPhotoPreview">
      <small class="muted">Sube una foto (jpg, png, webp, gif; máx. 2 MB).</small>
    </div>

    <div class="form-group">
      <label>O bien, imagen por URL</label>
      <input class="form-control" type="text" id="imageUrl" name="imageUrl"
             placeholder="https://..."
             value="<?= e(trim($_POST['imageUrl'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="name">Nombre</label>
      <input class="form-control" type="text" id="name" name="name" required
             value="<?= e($_POST['name'] ?? $admin->getName()) ?>">
      <div class="form-hint">Ej: Laura Fernández</div>
    </div>

    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required
             data-validate="email"
             value="<?= e($_POST['email'] ?? $admin->getEmail()) ?>">
    </div>

    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
             data-validate="phone"
             maxlength="8" value="<?= e($_POST['phoneNumber'] ?? $admin->getPhoneNumber() ?? '') ?>">
      <div class="form-hint">Ej: 8888-7777</div>
    </div>

    <?php $showPasswordFields = !empty($_POST['changePassword']); ?>
    <h3 class="card-title" style="margin-top:22px;">Cambiar contraseña</h3>
    <label class="checkbox" style="margin-bottom:12px;">
      <input type="checkbox" id="changePasswordCheck" name="changePassword" value="1" <?= $showPasswordFields ? 'checked' : '' ?>>
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

<div class="card form-card" style="max-width:560px;border-color:var(--danger-light);">
  <h3 style="margin-bottom:6px;color:var(--danger);">Desactivar cuenta</h3>
  <p class="muted" style="margin-bottom:12px;">
    Al desactivar tu cuenta ya no podrás iniciar sesión. No podrás desactivarla si eres el
    único administrador activo del sistema.
  </p>
  <form method="post" action="<?= e(base_url('admin', 'deactivateAccount')) ?>"
        data-ajax-admin-deactivate>
    <?= csrf_field() ?>
    <button class="btn btn-danger" type="submit">Desactivar mi cuenta</button>
  </form>
</div>

<script src="<?= e(js_url('admin/profile')) ?>"></script>
<?php require_once __DIR__ . '/../_footer.php'; ?>