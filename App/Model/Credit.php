<?php

/**
 * ENTIDAD: Credit — cuenta por cobrar de una reserva financiada.
 * Guarda el monto total y el saldo pendiente; el estado se deriva
 * del saldo y del vencimiento (pendiente | parcial | pagado | vencido).
 */
class Credit
{
  private int $idCredit;
  private int $idBooking;
  private int $idClient;
  private int $idOwner;
  private float $total;
  private float $balance;
  private string $dueDate;
  private string $state;
  private string $creditDate;
  private bool $isActive;

  public function __construct(
    int $idCredit = 0,
    int $idBooking = 0,
    int $idClient = 0,
    int $idOwner = 0,
    float $total = 0.0,
    float $balance = 0.0,
    string $dueDate = '',
    string $state = 'pendiente',
    string $creditDate = '',
    bool $isActive = true
  ) {
    $this->idCredit = $idCredit;
    $this->idBooking = $idBooking;
    $this->idClient = $idClient;
    $this->idOwner = $idOwner;
    $this->total = $total;
    $this->balance = $balance;
    $this->dueDate = $dueDate;
    $this->state = $state;
    $this->creditDate = $creditDate;
    $this->isActive = $isActive;
  }

  public function getIdCredit(): int { return $this->idCredit; }
  public function getIdBooking(): int { return $this->idBooking; }
  public function getIdClient(): int { return $this->idClient; }
  public function getIdOwner(): int { return $this->idOwner; }
  public function getTotal(): float { return $this->total; }
  public function getBalance(): float { return $this->balance; }
  public function getDueDate(): string { return $this->dueDate; }
  public function getState(): string { return $this->state; }
  public function getCreditDate(): string { return $this->creditDate; }
  public function getIsActive(): bool { return $this->isActive; }

  public function getIdCreditPk(): int { return $this->idCredit; }

  public function setIdCredit(int $idCredit): void { $this->idCredit = $idCredit; }
  public function setIdBooking(int $idBooking): void { $this->idBooking = $idBooking; }
  public function setIdClient(int $idClient): void { $this->idClient = $idClient; }
  public function setIdOwner(int $idOwner): void { $this->idOwner = $idOwner; }
  public function setTotal(float $total): void { $this->total = $total; }
  public function setBalance(float $balance): void { $this->balance = $balance; }
  public function setDueDate(string $dueDate): void { $this->dueDate = $dueDate; }
  public function setState(string $state): void { $this->state = $state; }
  public function setCreditDate(string $creditDate): void { $this->creditDate = $creditDate; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
