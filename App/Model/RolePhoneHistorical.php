<?php

/**
 * ENTIDAD: RolePhoneHistorical — mini-tabla de solo registro del
 * historial de teléfonos de una identidad Rol. Sirve para emitir la
 * "advertencia por cambio excesivo de teléfono" (posible ataque).
 */
class RolePhoneHistorical
{
  private int $idRolePhoneHistorical;
  private int $idRole;
  private ?string $actualPhone;
  private string $newPhone;
  private string $date;

  public function __construct(
    int $idRolePhoneHistorical = 0,
    int $idRole = 0,
    ?string $actualPhone = null,
    string $newPhone = '',
    string $date = ''
  ) {
    $this->idRolePhoneHistorical = $idRolePhoneHistorical;
    $this->idRole = $idRole;
    $this->actualPhone = $actualPhone;
    $this->newPhone = $newPhone;
    $this->date = $date;
  }

  // Getters
  public function getIdRolePhoneHistorical(): int
  {
    return $this->idRolePhoneHistorical;
  }

  public function getIdRole(): int
  {
    return $this->idRole;
  }

  public function getActualPhone(): ?string
  {
    return $this->actualPhone;
  }

  public function getNewPhone(): string
  {
    return $this->newPhone;
  }

  public function getDate(): string
  {
    return $this->date;
  }

  // Setters
  public function setIdRolePhoneHistorical(int $idRolePhoneHistorical): void
  {
    $this->idRolePhoneHistorical = $idRolePhoneHistorical;
  }

  public function setIdRole(int $idRole): void
  {
    $this->idRole = $idRole;
  }

  public function setActualPhone(?string $actualPhone): void
  {
    $this->actualPhone = $actualPhone;
  }

  public function setNewPhone(string $newPhone): void
  {
    $this->newPhone = $newPhone;
  }

  public function setDate(string $date): void
  {
    $this->date = $date;
  }
}