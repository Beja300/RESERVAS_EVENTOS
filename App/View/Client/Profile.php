<?php require_once __DIR__ . '/../_header.php';

$client = $client ?? ($_SESSION['user'] ?? null);
if ($client === null) {
  echo '<div class="alert alert-error">Sesión no válida. Inicia sesión de nuevo.</div>';
  require_once __DIR__ . '/../_footer.php';
  exit;
}
?>

<div class="page-head">
  <div>
    <h1>Mi perfil</h1>
    <a href="<?= e(base_url('client', 'dashboard')) ?>">&larr; Volver a mi panel</a>
  </div>
</div>

<div class="card form-card" style="max-width:560px;">
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('client', 'updateProfile')) ?>">
    <div class="form-group">
      <label for="name">Nombre</label>
      <input class="form-control" type="text" id="name" name="name" required
             value="<?= e($client->getName()) ?>">
    </div>

    <div class="form-group">
      <label for="email">Correo electrónico</label>
      <input class="form-control" type="email" id="email" name="email" required
             value="<?= e($client->getEmail()) ?>">
    </div>

    <div class="form-group">
      <label for="phoneNumber">Teléfono</label>
      <input class="form-control" type="tel" id="phoneNumber" name="phoneNumber"
             maxlength="8" value="<?= e($client->getPhoneNumber() ?? '') ?>">
    </div>

    <button class="btn btn-primary" type="submit">Guardar cambios</button>
  </form>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
