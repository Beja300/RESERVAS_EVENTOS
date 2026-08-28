<?php

/**
 * ENTIDAD: Venue — el "negocio/local" de un propietario.
 * Guarda las FK como simples enteros (idPropietario,idUbicacion):
 * esta clase no necesita saber nada de Owner ni Location como
 * objetos, solo su id -- son el Repositorio y el Service quienes
 * combinan varias entidades cuando hace falta.
 */
class Venue
{
  private int $idVenue;
  private int $idOwner;
  private int $idLocation;
  private string $nameVenue;
  private string $typeVenue;
  private int $capacityVenue;
  private string $imageVenue;
  private bool $isActive;

  public function __construct(
    int $idVenue,
    int $idOwner,
    int $idLocation,
    string $nameVenue,
    string $typeVenue,
    int $capacityVenue,
    string $imageVenue,
    bool $isActive
  ) {
    $this->idVenue = $idVenue;
    $this->idOwner = $idOwner;
    $this->idLocation = $idLocation;
    $this->nameVenue = $nameVenue;
    $this->typeVenue = $typeVenue;
    $this->capacityVenue = $capacityVenue;
    $this->imageVenue = $imageVenue;
    $this->isActive = $isActive;
  }

  // Getters

  public function getIdVenue(): int
  {
    return $this->idVenue;
  }

  public function getIdOwner(): int
  {
    return $this->idOwner;
  }

  public function getIdLocation(): int
  {
    return $this->idLocation;
  }

  public function getNameVenue(): string
  {
    return $this->nameVenue;
  }

  public function getTypeVenue(): string
  {
    return $this->typeVenue;
  }

  public function getCapacityVenue(): int
  {
    return $this->capacityVenue;
  }

  public function getImageVenue(): string
  {
    return $this->imageVenue;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters

  public function setIdVenue(int $idVenue): void
  {
    $this->idVenue = $idVenue;
  }

  public function setIdOwner(int $idOwner): void
  {
    $this->idOwner = $idOwner;
  }

  public function setIdLocation(int $idLocation): void
  {
    $this->idLocation = $idLocation;
  }

  public function setNameVenue(string $nameVenue): void
  {
    $this->nameVenue = $nameVenue;
  }

  public function setTypeVenue(string $typeVenue): void
  {
    $this->typeVenue = $typeVenue;
  }

  public function setCapacityVenue(int $capacityVenue): void
  {
    $this->capacityVenue = $capacityVenue;
  }

  public function setImageVenue(string $imageVenue): void
  {
    $this->imageVenue = $imageVenue;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
