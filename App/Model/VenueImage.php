<?php

/**
 * ENTIDAD: VenueImage — una foto extra de la galería de un local.
 * La portada del local sigue viviendo en Venue.tbvenueimage; esta
 * entidad guarda las imágenes adicionales en orden de presentación.
 */
class VenueImage
{
  private int $idVenueImage;
  private int $idVenue;
  private string $imagePath;
  private int $sort;
  private bool $isActive;

  public function __construct(
    int $idVenueImage,
    int $idVenue,
    string $imagePath,
    int $sort,
    bool $isActive
  ) {
    $this->idVenueImage = $idVenueImage;
    $this->idVenue = $idVenue;
    $this->imagePath = $imagePath;
    $this->sort = $sort;
    $this->isActive = $isActive;
  }

  public function getIdVenueImage(): int
  {
    return $this->idVenueImage;
  }

  public function getIdVenue(): int
  {
    return $this->idVenue;
  }

  public function getImagePath(): string
  {
    return $this->imagePath;
  }

  public function getSort(): int
  {
    return $this->sort;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }
}