<?php

/**
 * ENTIDAD: VenueImage — una foto de la galería de un local.
 */
class VenueImage
{
  private int $idImage;
  private int $idVenue;
  private string $path;
  private int $sort;
  private bool $isActive;

  public function __construct(
    int $idImage = 0,
    int $idVenue = 0,
    string $path = '',
    int $sort = 0,
    bool $isActive = true
  ) {
    $this->idImage = $idImage;
    $this->idVenue = $idVenue;
    $this->path = $path;
    $this->sort = $sort;
    $this->isActive = $isActive;
  }

  public function getIdImage(): int { return $this->idImage; }
  public function getIdVenue(): int { return $this->idVenue; }
  public function getPath(): string { return $this->path; }
  public function getSort(): int { return $this->sort; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdImage(int $idImage): void { $this->idImage = $idImage; }
  public function setIdVenue(int $idVenue): void { $this->idVenue = $idVenue; }
  public function setPath(string $path): void { $this->path = $path; }
  public function setSort(int $sort): void { $this->sort = $sort; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
