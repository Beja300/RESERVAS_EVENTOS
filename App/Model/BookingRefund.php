<?php

/**
 * ENTIDAD: BookingRefund — solicitud de reembolso de una reserva.
 * La crea el cliente con un motivo; el administrador la valida.
 */
class BookingRefund
{

  private int $id;
  private int $idBooking;
  private int $clientRoleId;   // tbroleid del cliente que solicita
  private string $detail;      // motivo del reembolso
  private string $state;       // 'pendiente' | 'aprobado' | 'rechazado'
  private ?string $date;
  private bool $isActive;

  public function __construct(
    int $id,
    int $idBooking,
    int $clientRoleId,
    string $detail,
    string $state = 'pendiente',
    ?string $date = null,
    bool $isActive = true
  ) {
    $this->id = $id;
    $this->idBooking = $idBooking;
    $this->clientRoleId = $clientRoleId;
    $this->detail = $detail;
    $this->state = $state;
    $this->date = $date;
    $this->isActive = $isActive;
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function getIdBooking(): int
  {
    return $this->idBooking;
  }

  public function getClientRoleId(): int
  {
    return $this->clientRoleId;
  }

  public function getDetail(): string
  {
    return $this->detail;
  }

  public function getState(): string
  {
    return $this->state;
  }

  public function getDate(): ?string
  {
    return $this->date;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }
}