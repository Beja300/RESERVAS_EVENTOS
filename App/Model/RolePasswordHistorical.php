<?php

/**
 * ENTIDAD: RolePasswordHistorical — mini-tabla de solo registro
 * (append-only) del historial de contraseñas de una identidad Rol.
 * Permite validar intentos de ataque: reutilización de contraseñas
 * antiguas y cambios demasiado frecuentes.
 * Las contraseñas SIEMPRE se guardan haseadas (bcrypt).
 */
class RolePasswordHistorical
{
  private int $idRolePasswordHistorical;
  private int $idRole;
  private string $actualPassword;
  private string $newPassword;
  private string $date;

  public function __construct(
    int $idRolePasswordHistorical = 0,
    int $idRole = 0,
    string $actualPassword = '',
    string $newPassword = '',
    string $date = ''
  ) {
    $this->idRolePasswordHistorical = $idRolePasswordHistorical;
    $this->idRole = $idRole;
    $this->actualPassword = $actualPassword;
    $this->newPassword = $newPassword;
    $this->date = $date;
  }

  // Getters
  public function getIdRolePasswordHistorical(): int
  {
    return $this->idRolePasswordHistorical;
  }

  public function getIdRole(): int
  {
    return $this->idRole;
  }

  public function getActualPassword(): string
  {
    return $this->actualPassword;
  }

  public function getNewPassword(): string
  {
    return $this->newPassword;
  }

  public function getDate(): string
  {
    return $this->date;
  }

  // Setters
  public function setIdRolePasswordHistorical(int $idRolePasswordHistorical): void
  {
    $this->idRolePasswordHistorical = $idRolePasswordHistorical;
  }

  public function setIdRole(int $idRole): void
  {
    $this->idRole = $idRole;
  }

  public function setActualPassword(string $actualPassword): void
  {
    $this->actualPassword = $actualPassword;
  }

  public function setNewPassword(string $newPassword): void
  {
    $this->newPassword = $newPassword;
  }

  public function setDate(string $date): void
  {
    $this->date = $date;
  }
}