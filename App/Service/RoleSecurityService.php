<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/RolePasswordHistoricalRepository.php';
require_once __DIR__ . '/../Repository/RolePhoneHistoricalRepository.php';
require_once __DIR__ . '/../Repository/RoleEmailHistoricalRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../Model/RolePasswordHistorical.php';
require_once __DIR__ . '/../Model/RolePhoneHistorical.php';
require_once __DIR__ . '/../Model/RoleEmailHistorical.php';
require_once __DIR__ . '/../Model/Notification.php';

/**
 * SERVICIO DE SEGURIDAD DE LA IDENTIDAD ROL.
 *
 * Centraliza las reglas de negocio de seguridad sobre las credenciales
 * (contraseña, teléfono y correo) y perpetúa cada movimiento en las
 * mini-tablas históricas (append-only, sin columna "active") para
 * validar posibles ataques informáticos sin sobrecargar tbrole.
 *
 * Frecuencias definidas:
 *  - Password: no reutilizar las últimas 5 | máx. 1 cambio/24h | máx. 3 en
 *    30 días (exceder => se bloquea el cambio y se alerta).
 *  - Teléfono: máx. 1 cambio/15 días | máx. 3 en 6 meses (exceder => se
 *    permite pero se emite la ADVERTENCIA al usuario y al admin).
 *  - Correo: máx. 1 cambio/30 días (exceder => advertencia).
 */
class RoleSecurityService
{
  // Configuración de frecuencias (política de seguridad)
  private const PASSWORD_REUSE_SAMPLES = 5;
  private const PASSWORD_MIN_INTERVAL_HOURS = 24;
  private const PASSWORD_MAX_PER_30_DAYS = 3;

  private const PHONE_MIN_INTERVAL_DAYS = 15;
  private const PHONE_MAX_PER_180_DAYS = 3;

  private const EMAIL_MIN_INTERVAL_DAYS = 30;

  private RoleRepository $roleRepo;
  private RolePasswordHistoricalRepository $passwordRepo;
  private RolePhoneHistoricalRepository $phoneRepo;
  private RoleEmailHistoricalRepository $emailRepo;
  private NotificationRepository $notificationRepo;
  private AuthService $authService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->roleRepo = new RoleRepository($connection);
    $this->passwordRepo = new RolePasswordHistoricalRepository($connection);
    $this->phoneRepo = new RolePhoneHistoricalRepository($connection);
    $this->emailRepo = new RoleEmailHistoricalRepository($connection);
    $this->notificationRepo = new NotificationRepository($connection);
    $this->authService = new AuthService();
  }

  // =========================================================
  // CAMBIO DE CONTRASEÑA (flujo normal del propio usuario)
  // La contraseña actual ya fue verificada por el controlador.
  // =========================================================
  public function changePassword(int $roleId, string $newPassword): void
  {
    $role = $this->roleRepo->findById($roleId);
    if ($role === null) {
      throw new BusinessRuleException('La identidad no existe.');
    }

    $this->authService->validatePasswordStrength($newPassword);

    if ($this->passwordRepo->wasUsedInLast($roleId, $newPassword, self::PASSWORD_REUSE_SAMPLES)) {
      throw new BusinessRuleException('No puedes reutilizar una de tus últimas 5 contraseñas.');
    }

    $lastChange = $this->passwordRepo->findLastChangeDate($roleId);
    $count30 = $this->passwordRepo->countInLastDays($roleId, 30);

    $tooSoon = $this->withinHours($lastChange, self::PASSWORD_MIN_INTERVAL_HOURS);
    $tooMany = $count30 >= self::PASSWORD_MAX_PER_30_DAYS;

    if ($tooSoon || $tooMany) {
      $this->notifySecurityIncident(
        $roleId,
        "cambios de contraseña demasiado frecuentes (intento bloqueado)."
      );
      throw new BusinessRuleException(
        'Demasiados cambios de contraseña en poco tiempo; tu cuenta fue marcada por seguridad.'
      );
    }

    $this->persistPasswordChange($roleId, $newPassword, $role->getPassword());
  }

  // =========================================================
  // RESETEO DE CONTRASEÑA (lo hace un admin sobre otro usuario)
  // No bloquea por frecuencia, pero deja trazabilidad y avisa al dueño.
  // =========================================================
  public function adminResetPassword(int $roleId, string $newPassword): void
  {
    $role = $this->roleRepo->findById($roleId);
    if ($role === null) {
      throw new BusinessRuleException('La identidad no existe.');
    }

    $this->authService->validatePasswordStrength($newPassword);

    if ($this->passwordRepo->wasUsedInLast($roleId, $newPassword, self::PASSWORD_REUSE_SAMPLES)) {
      throw new BusinessRuleException('Esa contraseña ya fue usada recientemente por el usuario.');
    }

    $this->persistPasswordChange($roleId, $newPassword, $role->getPassword());

    $this->notify(
      $roleId,
      'Un administrador restableció tu contraseña. Si no lo solicitaste, reporta el incidente.',
      'index.php?controller=auth&action=showLogin'
    );
  }

  // =========================================================
  // CAMBIO DE TELÉFONO: PERPETUA DEBERÍA ANUNCIARSE, ADVERTENCIA SI
  // CAMBIA DEMASIADO. El registro en tbrole ya lo hace el controlador
  // con roleRepo->update; aquí se audita y se alerta.
  // =========================================================
  public function recordPhoneChange(int $roleId, ?string $actualPhone, ?string $newPhone): void
  {
    if (trim((string) $newPhone) === '' || $newPhone === $actualPhone) {
      return;
    }

    $this->authService->validatePhoneFormat($newPhone);

    $this->phoneRepo->save(new RolePhoneHistorical(
      idRole: $roleId,
      actualPhone: $actualPhone,
      newPhone: $newPhone
    ));

    $lastChange = $this->phoneRepo->findLastChangeDate($roleId);
    $count180 = $this->phoneRepo->countInLastDays($roleId, 180);

    $tooSoon = $this->withinDays($lastChange, self::PHONE_MIN_INTERVAL_DAYS);
    $tooMany = $count180 >= self::PHONE_MAX_PER_180_DAYS;

    if ($tooSoon || $tooMany) {
      $this->notifySecurityIncident(
        $roleId,
        'cambio de teléfono excesivo (' . $count180 . ' en 6 meses).'
      );
    }
  }

  // =========================================================
  // CAMBIO DE CORREO: audita y alerta si cambia demasiado seguido.
  // =========================================================
  public function recordEmailChange(int $roleId, string $actualEmail, string $newEmail): void
  {
    if (strtolower($newEmail) === strtolower($actualEmail)) {
      return;
    }

    $this->authService->validateEmailIsUnique($newEmail);

    $this->emailRepo->save(new RoleEmailHistorical(
      idRole: $roleId,
      actualEmail: $actualEmail,
      newEmail: $newEmail
    ));

    $lastChange = $this->emailRepo->findLastChangeDate($roleId);
    if ($this->withinDays($lastChange, self::EMAIL_MIN_INTERVAL_DAYS)) {
      $this->notifySecurityIncident($roleId, 'cambio de correo demasiado frecuente.');
    }
  }

  // =========================================================
  // CONSULTA DE HISTORIALES (para pantallas de auditoría)
  // =========================================================
  public function getPasswordHistory(int $roleId): array
  {
    return $this->passwordRepo->findByRole($roleId);
  }

  public function getPhoneHistory(int $roleId): array
  {
    return $this->phoneRepo->findByRole($roleId);
  }

  public function getEmailHistory(int $roleId): array
  {
    return $this->emailRepo->findByRole($roleId);
  }

  // =========================================================
  // PRIVADOS
  // =========================================================
  private function persistPasswordChange(int $roleId, string $newPassword, string $actualHash): void
  {
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $this->roleRepo->updatePasswordHashed($roleId, $newHash);

    $this->passwordRepo->save(new RolePasswordHistorical(
      idRole: $roleId,
      actualPassword: $actualHash,
      newPassword: $newHash
    ));
  }

  private function notifySecurityIncident(int $roleId, string $detail): void
  {
    $this->notify(
      $roleId,
      'Actividad sospechosa en tu cuenta: ' . $detail . ' Revisa tu historial.',
      'index.php?controller=client&action=profile'
    );

    foreach ($this->notificationRepo->findAdminRoleIds() as $adminRoleId) {
      $this->notify(
        $adminRoleId,
        'ALERTA de seguridad: el rol #' . $roleId . ' registró ' . $detail,
        'index.php?controller=admin&action=users'
      );
    }
  }

  private function notify(int $roleId, string $message, string $link): void
  {
    $this->notificationRepo->save(new Notification(
      idNotification: 0,
      idRol: $roleId,
      messageNotification: $message,
      dateNotification: date('Y-m-d H:i:s'),
      isActive: true,
      isRead: false,
      link: $link
    ));
  }

  private function withinHours(?string $dateTime, int $hours): bool
  {
    if ($dateTime === null || $dateTime === '') {
      return false;
    }

    $delta = time() - strtotime($dateTime);

    return $delta >= 0 && $delta < $hours * 3600;
  }

  private function withinDays(?string $dateTime, int $days): bool
  {
    if ($dateTime === null || $dateTime === '') {
      return false;
    }

    $delta = time() - strtotime($dateTime);

    return $delta >= 0 && $delta < $days * 86400;
  }
}