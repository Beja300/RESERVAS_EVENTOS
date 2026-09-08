<?php

/**
 * BaseController — Comportamiento común de todos los controladores.
 *
 * Centraliza:
 *  - Guards de autenticación por rol (requireLogin/Admin/Owner/Client).
 *  - Redirecciones al front controller con URL construida desde el rol.
 *  - Acceso al usuario en sesión y a su roleId (auditoría).
 *
 * Cada controlador solo declara sus acciones y delegación a servicios.
 */
abstract class BaseController
{
  // =========================================================
  // SESIÓN
  // =========================================================
  protected function startSession(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  // =========================================================
  // GUARDS DE AUTENTICACIÓN
  // =========================================================
  protected function requireLogin(?string $expectedType = null): void
  {
    $this->startSession();

    $type = $_SESSION['type'] ?? null;

    $ok = $expectedType === null
      ? $type !== null
      : $type === $expectedType;

    if (!$ok) {
      $this->redirect('auth', 'showLogin');
    }
  }

  protected function requireAdmin(): void
  {
    $this->requireLogin('admin');
  }

  protected function requireOwner(): void
  {
    $this->requireLogin('owner');
  }

  protected function requireClient(): void
  {
    $this->requireLogin('client');
  }

  // =========================================================
  // REDIRECCIÓN HACIA EL FRONT CONTROLLER
  // =========================================================
  protected function redirect(string $controller, string $action, array $params = []): void
  {
    $query = http_build_query(array_merge(
      ['controller' => $controller, 'action' => $action],
      $params
    ));
    header('Location: ../../Public/index.php?' . $query);
    exit;
  }

  // =========================================================
  // USUARIO EN SESIÓN
  // =========================================================
  protected function currentUser(): ?object
  {
    return $_SESSION['user'] ?? null;
  }

  /**
   * RoleId del usuario en sesión (para auditoría). Devuelve 0 si no hay
   * sesión o el modelo no expone getIdRol().
   */
  protected function currentRoleId(): int
  {
    $user = $this->currentUser();
    return $user !== null && method_exists($user, 'getIdRol')
      ? (int) $user->getIdRol()
      : 0;
  }
}