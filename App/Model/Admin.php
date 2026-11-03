<?php

require_once __DIR__ . '/Role.php';

/**
 * ENTIDAD: Admin
 *
 * "extends Role" -- hereda pk, nombre, correo, contraseña, teléfono y
 * activo de la clase Role, y agrega SOLO lo propio de tbroleadmin.
 */
class Admin extends Role
{
  private int $idAdmin;
  private bool $isAdminActive;
  private int $idRol;
  private string $imageAdmin;

  public function __construct(
    int $id,
    string $name,
    string $email,
    string $password,
    bool $isActive,
    int $idAdmin,
    bool $isAdminActive,
    int $idRol,
    string $imageAdmin,
    ?string $phoneNumber = null
  ) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idAdmin = $idAdmin;
    $this->isAdminActive = $isAdminActive;
    $this->idRol = $idRol;
    $this->imageAdmin = $imageAdmin;
  }

  // Getters
  public function getIdAdmin(): int
  {
    return $this->idAdmin;
  }

  public function getIsAdminActive(): bool
  {
    return $this->isAdminActive;
  }

  public function getIdRol(): int
  {
    return $this->idRol;
  }

  public function getImageAdmin(): string
  {
    return $this->imageAdmin;
  }

  // Setters
  public function setIdAdmin(int $idAdmin): void
  {
    $this->idAdmin = $idAdmin;
  }

  public function setIsAdminActive(bool $isAdminActive): void
  {
    $this->isAdminActive = $isAdminActive;
  }

  public function setIdRol(int $idRol): void
  {
    $this->idRol = $idRol;
  }

  public function setImageAdmin(string $imageAdmin): void
  {
    $this->imageAdmin = $imageAdmin;
  }
}
