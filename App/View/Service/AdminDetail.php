<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Detalle del servicio</h1>
    <p class="muted">Información completa del servicio, su propietario y su local</p>
  </div>
  <a class="btn btn-sm" href="<?= e(base_url('service', 'pending')) ?>">&larr; Volver a pendientes</a>
</div>

<div class="grid grid-2" style="margin-bottom:20px;">
  <div class="card">
    <h3 class="card-title">Servicio</h3>
    <div class="detail-item"><div class="k">Nombre</div><div class="v"><strong><?= e($service->getNameService()) ?></strong></div></div>
    <div class="detail-item"><div class="k">Tipo</div><div class="v"><?= $service->getTypeService() !== '' && $service->getTypeService() !== null ? e($service->getTypeService()) : '—' ?></div></div>
    <div class="detail-item"><div class="k">Precio</div><div class="v">&#8353; <?= number_format($service->getPriceService(), 2) ?></div></div>
    <div class="detail-item"><div class="k">Estado</div><div class="v">
      <?php
        $badge = match ($service->getStateService()) {
          'aprobado'  => 'success',
          'rechazado' => 'danger',
          default     => 'warning',
        };
      ?>
      <span class="badge <?= $badge ?>"><?= e($service->getStateService()) ?></span>
    </div></div>
    <?php if ($service->getApprovedBy() !== null): ?>
      <div class="detail-item"><div class="k">Aprobado por</div><div class="v">
        <?= $approvedBy !== null ? e($approvedBy->getName()) : '#' . (int) $service->getApprovedBy() ?>
      </div></div>
      <div class="detail-item"><div class="k">Fecha de aprobación</div><div class="v"><?= e($service->getApprovedOn()) ?></div></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">Local</h3>
    <?php if ($venue === null): ?>
      <p class="muted">Local no encontrado.</p>
    <?php else: ?>
      <div class="detail-item"><div class="k">Nombre</div><div class="v"><strong><?= e($venue->getNameVenue()) ?></strong></div></div>
      <div class="detail-item"><div class="k">Tipo</div><div class="v"><?= e($venue->getTypeVenue()) !== '' ? e($venue->getTypeVenue()) : '—' ?></div></div>
      <div class="detail-item"><div class="k">Capacidad</div><div class="v"><?= (int) $venue->getCapacityVenue() ?> personas</div></div>
      <div class="detail-item"><div class="k">Precio de renta</div><div class="v">&#8353; <?= number_format($venue->getPriceVenue(), 2) ?></div></div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h3 class="card-title">Propietario que lo solicita</h3>
  <?php if ($owner === null): ?>
    <p class="muted">Propietario no encontrado.</p>
  <?php else: ?>
    <div class="grid grid-2">
      <div>
        <div class="detail-item"><div class="k">Nombre</div><div class="v"><?= e($owner->getFirstNameOwner()) ?> <?= e($owner->getLastNameOwner()) ?></div></div>
        <div class="detail-item"><div class="k">Alias</div><div class="v"><?= $owner->getAliasOwner() !== '' ? e($owner->getAliasOwner()) : '—' ?></div></div>
        <div class="detail-item"><div class="k">No. identificación</div><div class="v"><?= $owner->getIdentificationNumberOwner() !== '' ? e($owner->getIdentificationNumberOwner()) : '—' ?></div></div>
      </div>
      <div>
        <div class="detail-item"><div class="k">Correo</div><div class="v"><?= e($owner->getEmail()) ?></div></div>
        <div class="detail-item"><div class="k">Teléfono</div><div class="v"><?= $owner->getPhoneNumber() !== null && $owner->getPhoneNumber() !== '' ? e($owner->getPhoneNumber()) : '—' ?></div></div>
        <div class="detail-item"><div class="k">Estado</div><div class="v">
          <span class="badge <?= $owner->getIsOwnerActive() ? 'success' : 'neutral' ?>"><?= $owner->getIsOwnerActive() ? 'Activo' : 'Inactivo' ?></span>
        </div></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_footer.php'; ?>
