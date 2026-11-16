<?php

/**
 * ENTIDAD: ServiceGroup — agrupa las copias de un mismo servicio en uno o
 * varios locales del propietario. Permite administrar en qué locales se
 * ofrece sin perder el historial ni los datos propios de cada copia.
 */
class ServiceGroup
{
  private int $idGroup;
  private int $idOwner;
  private string $name;
  private string $createdAt;
  private bool $isActive;

  public function __construct(
    int $idGroup = 0,
    int $idOwner = 0,
    string $name = '',
    string $createdAt = '',
    bool $isActive = true
  ) {
    $this->idGroup = $idGroup;
    $this->idOwner = $idOwner;
    $this->name = $name;
    $this->createdAt = $createdAt;
    $this->isActive = $isActive;
  }

  public function getIdGroup(): int { return $this->idGroup; }
  public function getIdOwner(): int { return $this->idOwner; }
  public function getName(): string { return $this->name; }
  public function getCreatedAt(): string { return $this->createdAt; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdGroup(int $idGroup): void { $this->idGroup = $idGroup; }
  public function setIdOwner(int $idOwner): void { $this->idOwner = $idOwner; }
  public function setName(string $name): void { $this->name = $name; }
  public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
