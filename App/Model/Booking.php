<?php

/**
 * ENTIDAD: Booking — la reserva hecha por un cliente en un local.
 */
class Booking
{
  private int $idBooking;
  private int $idClient;
  private int $idLocal;
  private string $bookingDate;
  private ?string $bookingEndDate;
  private string $bookingState;
  private bool $isBookingActive;
  private ?string $eventType;
  private ?string $eventDescription;

  public function __construct(
    int $idBooking,
    int $idClient,
    int $idLocal,
    string $bookingDate,
    string $bookingState,
    bool $isBookingActive,
    ?string $bookingEndDate = null,
    ?string $eventType = null,
    ?string $eventDescription = null
  ) {
    $this->idBooking = $idBooking;
    $this->idClient = $idClient;
    $this->idLocal = $idLocal;
    $this->bookingDate = $bookingDate;
    $this->bookingEndDate = $bookingEndDate;
    $this->bookingState = $bookingState;
    $this->isBookingActive = $isBookingActive;
    $this->eventType = $eventType;
    $this->eventDescription = $eventDescription;
  }

  // Getters
  public function getIdBooking(): int
  {
    return $this->idBooking;
  }

  public function getIdClient(): int
  {
    return $this->idClient;
  }

  public function getIdLocal(): int
  {
    return $this->idLocal;
  }

  public function getBookingDate(): string
  {
    return $this->bookingDate;
  }

  public function getBookingEndDate(): ?string
  {
    return $this->bookingEndDate;
  }

  public function getBookingState(): string
  {
    return $this->bookingState;
  }

  public function getIsBookingActive(): bool
  {
    return $this->isBookingActive;
  }

  public function getEventType(): ?string
  {
    return $this->eventType;
  }

  public function getEventDescription(): ?string
  {
    return $this->eventDescription;
  }

  // Setters
  public function setIdBooking(int $idBooking): void
  {
    $this->idBooking = $idBooking;
  }

  public function setIdClient(int $idClient): void
  {
    $this->idClient = $idClient;
  }

  public function setIdLocal(int $idLocal): void
  {
    $this->idLocal = $idLocal;
  }

  public function setBookingDate(string $bookingDate): void
  {
    $this->bookingDate = $bookingDate;
  }

  public function setBookingEndDate(?string $bookingEndDate): void
  {
    $this->bookingEndDate = $bookingEndDate;
  }

  public function setBookingState(string $bookingState): void
  {
    $this->bookingState = $bookingState;
  }

  public function setIsBookingActive(bool $isBookingActive): void
  {
    $this->isBookingActive = $isBookingActive;
  }

  public function setEventType(?string $eventType): void
  {
    $this->eventType = $eventType;
  }

  public function setEventDescription(?string $eventDescription): void
  {
    $this->eventDescription = $eventDescription;
  }
}
