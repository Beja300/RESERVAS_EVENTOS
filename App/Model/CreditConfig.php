<?php

/**
 * ENTIDAD: CreditConfig — política de crédito de un propietario.
 * Define si acepta crédito, el plazo (días) y cuántas reservas debe
 * tener un cliente para considerarse "frecuente" y poder financiar.
 */
class CreditConfig
{
  private int $idCreditConfig;
  private int $idOwner;
  private bool $enabled;
  private int $days;
  private int $frequentMin;
  private bool $isActive;

  public function __construct(
    int $idCreditConfig = 0,
    int $idOwner = 0,
    bool $enabled = false,
    int $days = 30,
    int $frequentMin = 3,
    bool $isActive = true
  ) {
    $this->idCreditConfig = $idCreditConfig;
    $this->idOwner = $idOwner;
    $this->enabled = $enabled;
    $this->days = $days;
    $this->frequentMin = $frequentMin;
    $this->isActive = $isActive;
  }

  public function getIdCreditConfig(): int { return $this->idCreditConfig; }
  public function getIdOwner(): int { return $this->idOwner; }
  public function isEnabled(): bool { return $this->enabled; }
  public function getDays(): int { return $this->days; }
  public function getFrequentMin(): int { return $this->frequentMin; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdCreditConfig(int $idCreditConfig): void { $this->idCreditConfig = $idCreditConfig; }
  public function setIdOwner(int $idOwner): void { $this->idOwner = $idOwner; }
  public function setEnabled(bool $enabled): void { $this->enabled = $enabled; }
  public function setDays(int $days): void { $this->days = $days; }
  public function setFrequentMin(int $frequentMin): void { $this->frequentMin = $frequentMin; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
