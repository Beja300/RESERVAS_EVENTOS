<?php

require_once __DIR__ . '/Role.php';

/**
 * ENTIDAD: Client
 * Mismo patrón que Admin: hereda de Role, agrega lo propio de tbroleclient.
 */
class Client extends Role
{
  private int $idClient;
  private bool $isClientActive;
  private int $idRol;
  private string $imageClient;
  public function __construct(
    int $id,
    string $name,
    string $email,
    string $password,
    bool $isActive,
    int $idClient,
    bool $isClientActive,
    int $idRol,
    string $imageClient,
    ?string $phoneNumber = null
  ) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idClient = $idClient;
    $this->isClientActive = $isClientActive;
    $this->idRol = $idRol;
    $this->imageClient = $imageClient;
  }

  // Getters
  public function getIdClient(): int
  {
    return $this->idClient;
  }

  public function getIsClientActive(): bool
  {
    return $this->isClientActive;
  }

  public function getIdRol(): int
  {
    return $this->idRol;
  }

  public function getImageClient(): string
  {
    return $this->imageClient;
  }

  // Setters
  public function setIdClient(int $idClient): void
  {
    $this->idClient = $idClient;
  }

  public function setIsClientActive(bool $isClientActive): void
  {
    $this->isClientActive = $isClientActive;
  }

  public function setIdRol(int $idRol): void
  {
    $this->idRol = $idRol;
  }

  public function setImageClient(string $imageClient): void
  {
    $this->imageClient = $imageClient;
  }
}
