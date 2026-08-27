<?php

require_once __DIR__ . '/Role.php';

/**
 * ENTIDAD: Owner
 * Hereda de Role (correo/contraseña/login son los mismos para cualquier
 * rol), y agrega los datos propios de tbroleowner -- que incluyen su
 * PROPIO nombre/apellido, distintos del "nombre" genérico de role.
 */
class Owner extends Role
{
  private int $idOwner;
  private string $firstNameOwner;//Quitar 
  private string $lastNameOwner;
  private string $aliasOwner;
  private string $identificationNumberOwner;
  private bool $isOwnerActive;
  private int $idRol;
  private string $imageOwner;

  public function __construct(
    int $id,
    string $name,
    string $email,
    string $password,
    bool $isActive,
    int $idOwner,
    string $firstName,//quitar
    string $lastName,
    string $alias,
    string $identificationNumber,
    bool $isOwnerActive,
    int $idRol,
    string $imageOwner,
    ?string $phoneNumber = null
  ) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idOwner = $idOwner;
    $this->firstNameOwner = $firstName;//quitar
    $this->lastNameOwner = $lastName;
    $this->aliasOwner = $alias;
    $this->identificationNumberOwner = $identificationNumber;
    $this->isOwnerActive = $isOwnerActive;
    $this->idRol = $idRol;
    $this->imageOwner = $imageOwner;
  }

  // Getters
  public function getIdOwner(): int
  {
    return $this->idOwner;
  }

  public function getFirstNameOwner(): string//quitar
  {
    return $this->firstNameOwner;
  }

  public function getLastNameOwner(): string
  {
    return $this->lastNameOwner;
  }

  public function getAliasOwner(): string
  {
    return $this->aliasOwner;
  }

  public function getIdentificationNumberOwner(): string
  {
    return $this->identificationNumberOwner;
  }

  public function getIsOwnerActive(): bool
  {
    return $this->isOwnerActive;
  }

  public function getIdRol(): int
  {
    return $this->idRol;
  }

  public function getImageOwner(): string
  {
    return $this->imageOwner;
  }

  // Setters
  public function setIdOwner(int $idOwner): void
  {
    $this->idOwner = $idOwner;
  }

  public function setFirstNameOwner(string $firstNameOwner): void//quitar
  {
    $this->firstNameOwner = $firstNameOwner;
  }

  public function setLastNameOwner(string $lastNameOwner): void
  {
    $this->lastNameOwner = $lastNameOwner;
  }

  public function setAliasOwner(string $aliasOwner): void
  {
    $this->aliasOwner = $aliasOwner;
  }

  public function setIdentificationNumberOwner(string $identificationNumberOwner): void
  {
    $this->identificationNumberOwner = $identificationNumberOwner;
  }

  public function setIsOwnerActive(bool $isOwnerActive): void
  {
    $this->isOwnerActive = $isOwnerActive;
  }

  public function setIdRol(int $idRol): void
  {
    $this->idRol = $idRol;
  }

  public function setImageOwner(string $imageOwner): void
  {
    $this->imageOwner = $imageOwner;
  }
}
