<?php $pageJs = ['client/profile', 'venue/location']; ?>
<?php require_once __DIR__ . '/../_header.php';

$client = $client ?? ($_SESSION['user'] ?? null);
if ($client === null) {
  echo '<div class="alert alert-error">Sesión no válida. Inicia sesión de nuevo.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}

$location = $location ?? null;
?>

<div class="page-head">
  <div>
    <h1>Mi perfil</h1>
  </div>
</div>

<div class="card form-card" style="max-width:620px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('client', 'updateProfile')) ?>"
        enctype="multipart/form-data" data-validate-form data-ajax-client-profile>
    <?= csrf_field() ?>

    <div class="form-group" style="margin-bottom:16px;">
      <label>Foto de perfil</label>
      <div style="text-align:center;margin-bottom:12px;">
        <img id="clientPhotoPreview"
             src="<?= $client->getImageClient() !== '' ? e(image_url($client->getImageClient())) : '' ?>"
             alt="Foto de perfil"
             style="display:<?= $client->getImageClient() !== '' ? 'inline-block' : 'none' ?>;width:128px;height:128px;border-radius:50%;object-fit:cover;vertical-align:middle;box-shadow:0 0 0 6px #fff,0 0 0 7px var(--neutral-200),0 6px 16px rgba(0,0,0,.18);">
      </div>
      <input class="form-control" type="file" id="image" name="image" accept="image/*" data-photo-input="clientPhotoPreview">
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
             value="<?= e($client->getName()) ?>">
    </div>

    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required
             data-validate="email"
             value="<?= e($client->getEmail()) ?>">
    </div>

    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
             data-validate="phone"
             maxlength="8" value="<?= e($client->getPhoneNumber() ?? '') ?>">
    </div>

    <hr style="border:none;border-top:1px solid var(--neutral-200);margin:18px 0;">
    <p style="font-weight:700;color:var(--neutral-800);margin-bottom:14px;">Ubicación</p>

    <div class="grid grid-3">
      <div class="form-group">
        <label for="province">Provincia</label>
        <select class="form-control" id="province" name="province" data-level="province"
                data-value="<?= e($location !== null ? $location->getProvinceLocation() : '') ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="canton">Cantón</label>
        <select class="form-control" id="canton" name="canton" data-level="canton" disabled
                data-value="<?= e($location !== null ? $location->getCantonLocation() : '') ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
      <div class="form-group">
        <label for="district">Distrito</label>
        <select class="form-control" id="district" name="district" data-level="district" disabled
                data-value="<?= e($location !== null ? $location->getDistrictLocation() : '') ?>">
          <option value="">— Selecciona —</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label for="town">Pueblo</label>
      <input class="form-control" type="text" id="town" name="town"
             value="<?= e($location !== null ? $location->getTownLocation() : ($_POST['town'] ?? '')) ?>">
    </div>
    <div class="form-group">
      <label for="description">Descripción / señas</label>
      <input class="form-control" type="text" id="description" name="description"
             value="<?= e($location !== null ? $location->getDescriptionLocation() : ($_POST['description'] ?? '')) ?>">
    </div>

    <button class="btn btn-primary" type="submit">Guardar cambios</button>
  </form>
</div>

<div class="card" style="max-width:620px;margin-top:18px;border-color:var(--danger-light);">
  <h3 style="margin-bottom:6px;color:var(--danger);">Desactivar cuenta</h3>
  <p class="muted" style="margin-bottom:12px;">
    Al desactivar tu cuenta ya no podrás iniciar sesión, pero tus reservas e historial
    se conservan. Puedes volver a activarla más adelante.
  </p>
  <form method="post" action="<?= e(base_url('client', 'deactivateAccount')) ?>"
        data-ajax-client-deactivate>
    <?= csrf_field() ?>
    <button class="btn btn-danger" type="submit">Desactivar mi cuenta</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>