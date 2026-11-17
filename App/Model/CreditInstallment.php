<?php

/**
 * ENTIDAD: CreditInstallment — una cuota del calendario de un crédito.
 */
class CreditInstallment
{
  private int $idInstallment;
  private int $idCredit;
  private int $number;
  private float $amount;
  private string $dueDate;
  private ?string $paidDate;
  private string $state;
  private bool $isActive;

  public function __construct(
    int $idInstallment = 0,
    int $idCredit = 0,
    int $number = 1,
    float $amount = 0.0,
    string $dueDate = '',
    ?string $paidDate = null,
    string $state = 'pendiente',
    bool $isActive = true
  ) {
    $this->idInstallment = $idInstallment;
    $this->idCredit = $idCredit;
    $this->number = $number;
    $this->amount = $amount;
    $this->dueDate = $dueDate;
    $this->paidDate = $paidDate;
    $this->state = $state;
    $this->isActive = $isActive;
  }

  public function getIdInstallment(): int { return $this->idInstallment; }
  public function getIdCredit(): int { return $this->idCredit; }
  public function getNumber(): int { return $this->number; }
  public function getAmount(): float { return $this->amount; }
  public function getDueDate(): string { return $this->dueDate; }
  public function getPaidDate(): ?string { return $this->paidDate; }
  public function getState(): string { return $this->state; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdInstallment(int $idInstallment): void { $this->idInstallment = $idInstallment; }
  public function setIdCredit(int $idCredit): void { $this->idCredit = $idCredit; }
  public function setNumber(int $number): void { $this->number = $number; }
  public function setAmount(float $amount): void { $this->amount = $amount; }
  public function setDueDate(string $dueDate): void { $this->dueDate = $dueDate; }
  public function setPaidDate(?string $paidDate): void { $this->paidDate = $paidDate; }
  public function setState(string $state): void { $this->state = $state; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
