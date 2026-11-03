<?php

/**
 * ENTIDAD: ServiceRating — calificación que deja un usuario sobre un servicio.
 * Cualquier usuario autenticado puede calificar; el propietario ve el
 * detalle y el público solo el promedio.
 */
class ServiceRating
{
  private int $idServiceRating;
  private int $idService;
  private int $idRole;
  private int $stars;
  private string $comment;
  private bool $isActive;

  public function __construct(
    int $idServiceRating = 0,
    int $idService = 0,
    int $idRole = 0,
    int $stars = 0,
    string $comment = '',
    bool $isActive = true
  ) {
    $this->idServiceRating = $idServiceRating;
    $this->idService = $idService;
    $this->idRole = $idRole;
    $this->stars = $stars;
    $this->comment = $comment;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdServiceRating(): int
  {
    return $this->idServiceRating;
  }

  public function getIdService(): int
  {
    return $this->idService;
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
  public function setIdServiceRating(int $idServiceRating): void
  {
    $this->idServiceRating = $idServiceRating;
  }

  public function setIdService(int $idService): void
  {
    $this->idService = $idService;
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
