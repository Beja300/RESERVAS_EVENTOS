<?php

/**
 * ENTIDAD: BookingHistory — auditoría de modificaciones de una reserva
 * (qué administrador/cliente la modificó, qué acción y cuándo).
 */
class BookingHistory
{

  private int $id;
  private int $idBooking;
  private ?int $roleId;      // tbroleid del responsable (admin/cliente)
  private string $action;    // CANCELAR | REPROGRAMAR | CAMBIAR_LOCAL | SOLICITUD_REEMBOLSO | REEMBOLSO_APROBADO | REEMBOLSO_RECHAZADO | ...
  private ?string $detail;
  private ?string $date;
  private bool $isActive;

  public function __construct(
    int $id,
    int $idBooking,
    ?int $roleId,
    string $action,
    ?string $detail = null,
    ?string $date = null,
    bool $isActive = true
  ) {
    $this->id = $id;
    $this->idBooking = $idBooking;
    $this->roleId = $roleId;
    $this->action = $action;
    $this->detail = $detail;
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

  public function getRoleId(): ?int
  {
    return $this->roleId;
  }

  public function getAction(): string
  {
    return $this->action;
  }

  public function getDetail(): ?string
  {
    return $this->detail;
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