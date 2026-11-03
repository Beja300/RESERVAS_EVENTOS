<?php

/**
 * ENTIDAD: RoleEmailHistorical — mini-tabla de solo registro del
 * historial de correos de una identidad Rol. El correo es la identidad
 * de acceso (login), por eso sus cambios se auditan para detectar
 * actividad sospechosa.
 */
class RoleEmailHistorical
{
  private int $idRoleEmailHistorical;
  private int $idRole;
  private ?string $actualEmail;
  private string $newEmail;
  private string $date;

  public function __construct(
    int $idRoleEmailHistorical = 0,
    int $idRole = 0,
    ?string $actualEmail = null,
    string $newEmail = '',
    string $date = ''
  ) {
    $this->idRoleEmailHistorical = $idRoleEmailHistorical;
    $this->idRole = $idRole;
    $this->actualEmail = $actualEmail;
    $this->newEmail = $newEmail;
    $this->date = $date;
  }

  // Getters
  public function getIdRoleEmailHistorical(): int
  {
    return $this->idRoleEmailHistorical;
  }

  public function getIdRole(): int
  {
    return $this->idRole;
  }

  public function getActualEmail(): ?string
  {
    return $this->actualEmail;
  }

  public function getNewEmail(): string
  {
    return $this->newEmail;
  }

  public function getDate(): string
  {
    return $this->date;
  }

  // Setters
  public function setIdRoleEmailHistorical(int $idRoleEmailHistorical): void
  {
    $this->idRoleEmailHistorical = $idRoleEmailHistorical;
  }

  public function setIdRole(int $idRole): void
  {
    $this->idRole = $idRole;
  }

  public function setActualEmail(?string $actualEmail): void
  {
    $this->actualEmail = $actualEmail;
  }

  public function setNewEmail(string $newEmail): void
  {
    $this->newEmail = $newEmail;
  }

  public function setDate(string $date): void
  {
    $this->date = $date;
  }
}