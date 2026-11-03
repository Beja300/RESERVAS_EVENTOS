<?php

/**
 * ENTIDAD: OwnerPayment — datos de cobro del propietario por método
 * de pago (tbownerpayment). Estos datos son los que el cliente ve al
 * seleccionar un método para pagar (titular, cuenta/teléfono, ...).
 */
class OwnerPayment
{
  private int $idOwnerPayment;
  private int $idOwner;
  private int $idPaymentMethod;
  private string $paymentMethod;
  private string $holder;
  private string $account;
  private string $instructions;
  private bool $isActive;

  public function __construct(
    int $idOwnerPayment = 0,
    int $idOwner = 0,
    int $idPaymentMethod = 0,
    string $paymentMethod = '',
    string $holder = '',
    string $account = '',
    string $instructions = '',
    bool $isActive = true
  ) {
    $this->idOwnerPayment = $idOwnerPayment;
    $this->idOwner = $idOwner;
    $this->idPaymentMethod = $idPaymentMethod;
    $this->paymentMethod = $paymentMethod;
    $this->holder = $holder;
    $this->account = $account;
    $this->instructions = $instructions;
    $this->isActive = $isActive;
  }

  public function getIdOwnerPayment(): int
  {
    return $this->idOwnerPayment;
  }

  public function getIdOwner(): int
  {
    return $this->idOwner;
  }

  public function getIdPaymentMethod(): int
  {
    return $this->idPaymentMethod;
  }

  public function getPaymentMethod(): string
  {
    return $this->paymentMethod;
  }

  public function getHolder(): string
  {
    return $this->holder;
  }

  public function getAccount(): string
  {
    return $this->account;
  }

  public function getInstructions(): string
  {
    return $this->instructions;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  public function setIdOwnerPayment(int $idOwnerPayment): void
  {
    $this->idOwnerPayment = $idOwnerPayment;
  }

  public function setIdOwner(int $idOwner): void
  {
    $this->idOwner = $idOwner;
  }

  public function setIdPaymentMethod(int $idPaymentMethod): void
  {
    $this->idPaymentMethod = $idPaymentMethod;
  }

  public function setPaymentMethod(string $paymentMethod): void
  {
    $this->paymentMethod = $paymentMethod;
  }

  public function setHolder(string $holder): void
  {
    $this->holder = $holder;
  }

  public function setAccount(string $account): void
  {
    $this->account = $account;
  }

  public function setInstructions(string $instructions): void
  {
    $this->instructions = $instructions;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
