<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/RoleSecurityService.php';
require_once __DIR__ . '/../Model/Role.php';

/**
 * Reglas compartidas de perfil (vale para Admin, Client y Owner):
 * validación de credenciales, cambio de contraseña con confirmación de
 * la actual, y auditoría de cambios de teléfono/correo.
 *
 * Reúne bloques que antes se copiaban en ClientController,
 * OwnerController y AdminController.
 */
class ProfileService
{
  private AuthService $authService;
  private RoleSecurityService $securityService;

  public function __construct()
  {
    $this->authService = new AuthService();
    $this->securityService = new RoleSecurityService();
  }

  /**
   * Cambia la contraseña solo si el usuario la solicitó (ambos campos
   * presentes). Exige confirmar la contraseña actual y validar la nueva.
   */
  public function changePasswordIfRequested(Role $role, string $currentPassword, string $newPassword): void
  {
    $hasCurrent = trim($currentPassword) !== '';
    $hasNew = trim($newPassword) !== '';

    if (!$hasCurrent && !$hasNew) {
      return;
    }

    if (!$hasCurrent || !$hasNew) {
      throw new BusinessRuleException('Para cambiar tu contraseña debes escribir la contraseña actual y la nueva.');
    }

    if (!password_verify($currentPassword, $role->getPassword())) {
      throw new BusinessRuleException('La contraseña actual no es correcta.');
    }

    $this->authService->validatePasswordStrength($newPassword);
    $this->securityService->changePassword($role->getIdRol(), $newPassword);
  }

  public function validateUniqueEmail(?string $currentEmail, string $newEmail): void
  {
    if (strtolower($newEmail) !== strtolower((string) $currentEmail)) {
      $this->authService->validateEmailIsUnique($newEmail);
    }
  }

  public function validateUniqueIdentification(?string $current, string $new): void
  {
    if ($new !== '' && strtolower($new) !== strtolower((string) $current)) {
      $this->authService->validateIdentificationIsUnique($new);
    }
  }

  public function validatePhone(?string $phoneNumber): void
  {
    $this->authService->validatePhoneFormat($phoneNumber);
  }

  /**
   * Audita y alerta ante cambios de teléfono y correo.
   */
  public function recordCredentialChanges(
    Role $role,
    ?string $currentPhone,
    ?string $newPhone,
    ?string $currentEmail,
    string $newEmail
  ): void {
    $this->securityService->recordPhoneChange($role->getIdRol(), $currentPhone, $newPhone);
    $this->securityService->recordEmailChange($role->getIdRol(), $currentEmail, $newEmail);
  }
}