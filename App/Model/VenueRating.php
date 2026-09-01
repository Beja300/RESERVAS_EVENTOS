<?php

/**
 * ENTIDAD: VenueRating — calificación que deja un usuario sobre un local.
 * Cualquier usuario autenticado puede calificar; el propietario ve el
 * detalle y el público solo el promedio.
 */
class VenueRating
{
  private int $idVenueRating;
  private int $idVenue;
  private int $idRole;
  private int $stars;
  private string $comment;
  private bool $isActive;

  public function __construct(
    int $idVenueRating = 0,
    int $idVenue = 0,
    int $idRole = 0,
    int $stars = 0,
    string $comment = '',
    bool $isActive = true
  ) {
    $this->idVenueRating = $idVenueRating;
    $this->idVenue = $idVenue;
    $this->idRole = $idRole;
    $this->stars = $stars;
    $this->comment = $comment;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdVenueRating(): int
  {
    return $this->idVenueRating;
  }

  public function getIdVenue(): int
  {
    return $this->idVenue;
  }

  public function getIdRole(): int
  {
    return $this->idRole;
  }

  public function getStars(): int
  {
    return $this->stars;
  }

  public function getComment(): string
  {
    return $this->comment;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdVenueRating(int $idVenueRating): void
  {
    $this->idVenueRating = $idVenueRating;
  }

  public function setIdVenue(int $idVenue): void
  {
    $this->idVenue = $idVenue;
  }

  public function setIdRole(int $idRole): void
  {
    $this->idRole = $idRole;
  }

  public function setStars(int $stars): void
  {
    $this->stars = $stars;
  }

  public function setComment(string $comment): void
  {
    $this->comment = $comment;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
