<?php
$pageTitle = 'Nuevo propietario';
$pageCss = 'admin/password-toggle';
$pageJs = ['admin/password-toggle'];
require_once __DIR__ . '/../_header.php';
?>

<div class="page-head">
  <div>
    <h1>Nuevo propietario</h1>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card form-card form-page">
  <form method="post" action="<?= e(base_url('admin', 'createOwner')) ?>" data-validate-form>
    <?= csrf_field() ?>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="ownerFirstName">Nombre <span class="required-mark">*</span></label>
        <input class="form-control" type="text" id="ownerFirstName" name="ownerFirstName"
               data-validate="required" maxlength="300" required>
        <div class="form-hint">Ej: María José</div>
      </div>

      <div class="form-group">
        <label for="ownerLastName">Apellidos</label>
        <input class="form-control" type="text" id="ownerLastName" name="ownerLastName"
               maxlength="250">
        <div class="form-hint">Ej: Pérez Rodríguez</div>
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="ownerAlias">Alias (apodo)</label>
        <input class="form-control" type="text" id="ownerAlias" name="ownerAlias"
               maxlength="100">
        <div class="form-hint">Ej: Mari</div>
      </div>

      <div class="form-group">
        <label for="ownerIdentification">Cédula / identificación</label>
        <input class="form-control" type="text" id="ownerIdentification"
               name="ownerIdentification" maxlength="30">
        <div class="form-hint">Ej: 1-2345-0678</div>
      </div>
    </div>

    <div class="form-group">
      <label for="ownerBusinessName">Nombre de negocio <span class="required-mark">*</span></label>
      <input class="form-control" type="text" id="ownerBusinessName"
             name="ownerBusinessName" data-validate="required"
             maxlength="300" required>
      <div class="form-hint">Ej: Pastelería Doña Tere</div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="email">Correo electrónico <span class="required-mark">*</span></label>
        <input class="form-control" type="email" id="email" name="email"
               data-validate="required email" maxlength="300" required>
        <div class="form-hint">Ej: mariosepe@correo.com</div>
      </div>

      <div class="form-group">
        <label for="phoneNumber">Teléfono <span class="required-mark">*</span></label>
        <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber" data-mask="phone"
               data-validate="phone" maxlength="15" placeholder="+506 8888-8888">
        <div class="form-hint">Ej: 8888-7777</div>
      </div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label for="password">Contraseña <span class="required-mark">*</span></label>
        <div class="password-wrapper">
          <input class="form-control" type="password" id="password" name="password"
                 data-validate="required password" minlength="8"
                 autocomplete="new-password" required>
          <button class="password-toggle" type="button" id="passwordToggle"
                  aria-label="Mostrar contraseña">Mostrar</button>
        </div>
        <div class="form-hint">Mínimo 8 caracteres y un número. Ej: ClaveSegura7</div>
      </div>

      <div class="form-group">
        <label for="passwordConfirm">Confirmar contraseña <span class="required-mark">*</span></label>
        <input class="form-control" type="password" id="passwordConfirm"
               name="passwordConfirm"
               data-validate="required match"
               data-match="password"
               data-match-message="Las contraseñas no coinciden."
               minlength="8" autocomplete="new-password" required>
      </div>
    </div>

    <div class="form-actions">
      <a class="btn btn-secondary" href="<?= e(base_url('admin', 'users')) ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Registrar propietario</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
