<?php

/**
 * ENTIDAD: Earning — repartición de ganancias de una reserva pagada.
 * Registra el total, la comisión de la plataforma, el IVA retenido y
 * el monto neto que recibe el propietario.
 */
class Earning
{
  private int $idEarning;
  private int $idBooking;
  private float $total;
  private float $commission;
  private float $tax;
  private float $ownerAmount;
  private ?int $reviewedByRole;
  private string $earningDate;
  private bool $isActive;

  public function __construct(
    int $idEarning = 0,
    int $idBooking = 0,
    float $total = 0.0,
    float $commission = 0.0,
    float $tax = 0.0,
    float $ownerAmount = 0.0,
    ?int $reviewedByRole = null,
    string $earningDate = '',
    bool $isActive = true
  ) {
    $this->idEarning = $idEarning;
    $this->idBooking = $idBooking;
    $this->total = $total;
    $this->commission = $commission;
    $this->tax = $tax;
    $this->ownerAmount = $ownerAmount;
    $this->reviewedByRole = $reviewedByRole;
    $this->earningDate = $earningDate;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdEarning(): int
  {
    return $this->idEarning;
  }

  public function getIdBooking(): int
  {
    return $this->idBooking;
  }

  public function getTotal(): float
  {
    return $this->total;
  }

  public function getCommission(): float
  {
    return $this->commission;
  }

  public function getTax(): float
  {
    return $this->tax;
  }

  public function getOwnerAmount(): float
  {
    return $this->ownerAmount;
  }

  public function getReviewedByRole(): ?int
  {
    return $this->reviewedByRole;
  }

  public function getEarningDate(): string
  {
    return $this->earningDate;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdEarning(int $idEarning): void
  {
    $this->idEarning = $idEarning;
  }

  public function setIdBooking(int $idBooking): void
  {
    $this->idBooking = $idBooking;
  }

  public function setTotal(float $total): void
  {
    $this->total = $total;
  }

  public function setCommission(float $commission): void
  {
    $this->commission = $commission;
  }

  public function setTax(float $tax): void
  {
    $this->tax = $tax;
  }

  public function setOwnerAmount(float $ownerAmount): void
  {
    $this->ownerAmount = $ownerAmount;
  }

  public function setReviewedByRole(?int $reviewedByRole): void
  {
    $this->reviewedByRole = $reviewedByRole;
  }

  public function setEarningDate(string $earningDate): void
  {
    $this->earningDate = $earningDate;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
