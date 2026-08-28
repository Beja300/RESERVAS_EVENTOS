<?php

/**
 * ENTIDAD: CommissionConfig — configuración de comisión e IVA.
 * La comisión es editable por el admin; al cambiarla se notifica a los
 * propietarios. El IVA se descuenta de la comisión del propietario.
 */
class CommissionConfig
{
  private int $idConfig;
  private float $percentage;
  private float $tax;
  private bool $isActive;

  public function __construct(
    int $idConfig = 0,
    float $percentage = 5.00,
    float $tax = 13.00,
    bool $isActive = true
  ) {
    $this->idConfig = $idConfig;
    $this->percentage = $percentage;
    $this->tax = $tax;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdConfig(): int
  {
    return $this->idConfig;
  }

  public function getPercentage(): float
  {
    return $this->percentage;
  }

  public function getTax(): float
  {
    return $this->tax;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdConfig(int $idConfig): void
  {
    $this->idConfig = $idConfig;
  }

  public function setPercentage(float $percentage): void
  {
    $this->percentage = $percentage;
  }

  public function setTax(float $tax): void
  {
    $this->tax = $tax;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
