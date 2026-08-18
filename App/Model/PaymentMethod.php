<?php

/**
 * ENTIDAD: PaymentMethod — catálogo de métodos de pago, sin FK.
 */
class PaymentMethod
{

  private int $idPaymentMethod;
  private string $paymentMethod;
  private bool $isActive;


  public function __construct(int $idPaymentMethod, string $paymentMethod, bool $isActive)
  {
    $this->idPaymentMethod = $idPaymentMethod;
    $this->paymentMethod = $paymentMethod;
    $this->isActive = $isActive;
  }

  // Getters

  public function getIdPaymentMethod(): int
  {
    return $this->idPaymentMethod;
  }

  public function getPaymentMethod(): string
  {
    return $this->paymentMethod;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters

  public function setIdPaymentMethod(int $idPaymentMethod): void
  {
    $this->idPaymentMethod = $idPaymentMethod;
  }

  public function setPaymentMethod(string $paymentMethod): void
  {
    $this->paymentMethod = $paymentMethod;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
