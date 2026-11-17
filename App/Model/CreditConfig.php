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
  private float $minimumPayment;
  private bool $isActive;
  private int $installments;
  private string $frequency;
  private int $graceDays;
  private int $reminderDays;

  public function __construct(
    int $idCreditConfig = 0,
    int $idOwner = 0,
    bool $enabled = false,
    int $days = 30,
    int $frequentMin = 3,
    float $minimumPayment = 6.0,
    bool $isActive = true,
    int $installments = 1,
    string $frequency = 'mensual',
    int $graceDays = 0,
    int $reminderDays = 3
  ) {
    $this->idCreditConfig = $idCreditConfig;
    $this->idOwner = $idOwner;
    $this->enabled = $enabled;
    $this->days = $days;
    $this->frequentMin = $frequentMin;
    $this->minimumPayment = $minimumPayment;
    $this->isActive = $isActive;
    $this->installments = $installments;
    $this->frequency = $frequency;
    $this->graceDays = $graceDays;
    $this->reminderDays = $reminderDays;
  }

  public function getIdCreditConfig(): int { return $this->idCreditConfig; }
  public function getIdOwner(): int { return $this->idOwner; }
  public function isEnabled(): bool { return $this->enabled; }
  public function getDays(): int { return $this->days; }
  public function getFrequentMin(): int { return $this->frequentMin; }
  public function getMinimumPayment(): float { return $this->minimumPayment; }
  public function getIsActive(): bool { return $this->isActive; }
  public function getInstallments(): int { return $this->installments; }
  public function getFrequency(): string { return $this->frequency; }
  public function getGraceDays(): int { return $this->graceDays; }
  public function getReminderDays(): int { return $this->reminderDays; }

  public function setIdCreditConfig(int $idCreditConfig): void { $this->idCreditConfig = $idCreditConfig; }
  public function setIdOwner(int $idOwner): void { $this->idOwner = $idOwner; }
  public function setEnabled(bool $enabled): void { $this->enabled = $enabled; }
  public function setDays(int $days): void { $this->days = $days; }
  public function setFrequentMin(int $frequentMin): void { $this->frequentMin = $frequentMin; }
  public function setMinimumPayment(float $minimumPayment): void { $this->minimumPayment = $minimumPayment; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
  public function setInstallments(int $installments): void { $this->installments = $installments; }
  public function setFrequency(string $frequency): void { $this->frequency = $frequency; }
  public function setGraceDays(int $graceDays): void { $this->graceDays = $graceDays; }
  public function setReminderDays(int $reminderDays): void { $this->reminderDays = $reminderDays; }
}
