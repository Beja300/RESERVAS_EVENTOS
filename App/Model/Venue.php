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
  private float $priceVenue;
  private string $imageVenue;
  private bool $isActive;
  private ?string $descriptionVenue;
  private ?string $sizeVenue;
  private ?int $bathroomsVenue;
  private bool $hasParking;
  private ?int $parkingCapacity;
  private ?int $minCapacity;
  private float $cleaningFee;
  private ?string $checkInTime;
  private ?string $checkOutTime;

  public function __construct(
    int $idVenue,
    int $idOwner,
    int $idLocation,
    string $nameVenue,
    string $typeVenue,
    int $capacityVenue,
    float $priceVenue,
    string $imageVenue,
    bool $isActive,
    ?string $descriptionVenue = null,
    ?string $sizeVenue = null,
    ?int $bathroomsVenue = null,
    bool $hasParking = false,
    ?int $parkingCapacity = null,
    ?int $minCapacity = null,
    float $cleaningFee = 0.0,
    ?string $checkInTime = null,
    ?string $checkOutTime = null
  ) {
    $this->idVenue = $idVenue;
    $this->idOwner = $idOwner;
    $this->idLocation = $idLocation;
    $this->nameVenue = $nameVenue;
    $this->typeVenue = $typeVenue;
    $this->capacityVenue = $capacityVenue;
    $this->priceVenue = $priceVenue;
    $this->imageVenue = $imageVenue;
    $this->isActive = $isActive;
    $this->descriptionVenue = $descriptionVenue;
    $this->sizeVenue = $sizeVenue;
    $this->bathroomsVenue = $bathroomsVenue;
    $this->hasParking = $hasParking;
    $this->parkingCapacity = $parkingCapacity;
    $this->minCapacity = $minCapacity;
    $this->cleaningFee = $cleaningFee;
    $this->checkInTime = $checkInTime;
    $this->checkOutTime = $checkOutTime;
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

  public function getPriceVenue(): float
  {
    return $this->priceVenue;
  }

  public function getImageVenue(): string
  {
    return $this->imageVenue;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  public function getDescriptionVenue(): ?string
  {
    return $this->descriptionVenue;
  }

  public function getSizeVenue(): ?string
  {
    return $this->sizeVenue;
  }

  public function getBathroomsVenue(): ?int
  {
    return $this->bathroomsVenue;
  }

  public function getHasParking(): bool
  {
    return $this->hasParking;
  }

  public function getParkingCapacity(): ?int
  {
    return $this->parkingCapacity;
  }

  public function getMinCapacity(): ?int
  {
    return $this->minCapacity;
  }

  public function getCleaningFee(): float
  {
    return $this->cleaningFee;
  }

  public function getCheckInTime(): ?string { return $this->checkInTime; }
  public function getCheckOutTime(): ?string { return $this->checkOutTime; }

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

  public function setPriceVenue(float $priceVenue): void
  {
    $this->priceVenue = $priceVenue;
  }

  public function setImageVenue(string $imageVenue): void
  {
    $this->imageVenue = $imageVenue;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }

  public function setDescriptionVenue(?string $descriptionVenue): void
  {
    $this->descriptionVenue = $descriptionVenue;
  }

  public function setSizeVenue(?string $sizeVenue): void
  {
    $this->sizeVenue = $sizeVenue;
  }

  public function setBathroomsVenue(?int $bathroomsVenue): void
  {
    $this->bathroomsVenue = $bathroomsVenue;
  }

  public function setHasParking(bool $hasParking): void
  {
    $this->hasParking = $hasParking;
  }

  public function setParkingCapacity(?int $parkingCapacity): void
  {
    $this->parkingCapacity = $parkingCapacity;
  }

  public function setMinCapacity(?int $minCapacity): void
  {
    $this->minCapacity = $minCapacity;
  }

  public function setCleaningFee(float $cleaningFee): void
  {
    $this->cleaningFee = $cleaningFee;
  }

  public function setCheckInTime(?string $time): void { $this->checkInTime = $time; }
  public function setCheckOutTime(?string $time): void { $this->checkOutTime = $time; }
}
