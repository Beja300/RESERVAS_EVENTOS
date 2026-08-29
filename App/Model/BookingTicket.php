<?php

/**
 * ENTIDAD: BookingTicket — comprobante de pago de una reserva.
 * Al subirlo (PNG/PDF) la reserva pasa a "pendiente de verificación";
 * el propietario lo aprueba o rechaza. El método de pago lo elige el
 * CLIENTE al subir el comprobante (nunca el propietario).
 */
class BookingTicket
{
  private int $idTicket;
  private int $idBooking;
  private string $file;
  private string $type;
  private int $paymentMethodId;
  private string $state;
  private bool $isActive;

  public function __construct(
    int $idTicket = 0,
    int $idBooking = 0,
    string $file = '',
    string $type = '',
    int $paymentMethodId = 0,
    string $state = 'pendiente',
    bool $isActive = true
  ) {
    $this->idTicket = $idTicket;
    $this->idBooking = $idBooking;
    $this->file = $file;
    $this->type = $type;
    $this->paymentMethodId = $paymentMethodId;
    $this->state = $state;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdTicket(): int
  {
    return $this->idTicket;
  }

  public function getIdBooking(): int
  {
    return $this->idBooking;
  }

  public function getFile(): string
  {
    return $this->file;
  }

  public function getType(): string
  {
    return $this->type;
  }

  public function getState(): string
  {
    return $this->state;
  }

  public function getPaymentMethodId(): int
  {
    return $this->paymentMethodId;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdTicket(int $idTicket): void
  {
    $this->idTicket = $idTicket;
  }

  public function setIdBooking(int $idBooking): void
  {
    $this->idBooking = $idBooking;
  }

  public function setFile(string $file): void
  {
    $this->file = $file;
  }

  public function setType(string $type): void
  {
    $this->type = $type;
  }

  public function setState(string $state): void
  {
    $this->state = $state;
  }

  public function setPaymentMethodId(int $paymentMethodId): void
  {
    $this->paymentMethodId = $paymentMethodId;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
