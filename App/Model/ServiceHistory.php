<?php

/**
 * ENTIDAD: ServiceHistory — historial de precios de un servicio.
 * Congela el precio de cada momento para poder calcular ganancias
 * anuales con los valores vigentes en esa fecha.
 */
class ServiceHistory
{
  private int $idServiceHistory;
  private int $idService;
  private float $price;
  private string $validFrom;
  private bool $isActive;

  public function __construct(
    int $idServiceHistory = 0,
    int $idService = 0,
    float $price = 0.0,
    string $validFrom = '',
    bool $isActive = true
  ) {
    $this->idServiceHistory = $idServiceHistory;
    $this->idService = $idService;
    $this->price = $price;
    $this->validFrom = $validFrom;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdServiceHistory(): int
  {
    return $this->idServiceHistory;
  }

  public function getIdService(): int
  {
    return $this->idService;
  }

  public function getPrice(): float
  {
    return $this->price;
  }

  public function getValidFrom(): string
  {
    return $this->validFrom;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdServiceHistory(int $idServiceHistory): void
  {
    $this->idServiceHistory = $idServiceHistory;
  }

  public function setIdService(int $idService): void
  {
    $this->idService = $idService;
  }

  public function setPrice(float $price): void
  {
    $this->price = $price;
  }

  public function setValidFrom(string $validFrom): void
  {
    $this->validFrom = $validFrom;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
