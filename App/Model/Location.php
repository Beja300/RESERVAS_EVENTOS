<?php

/**
 * ENTIDAD: Location — sin FK, tabla independiente.
 */
class Location
{
  private int $idLocation;
  private string $provinceLocation;
  private string $cantonLocation;
  private string $districtLocation;
  private string $addressLocation; // Detalle de la dirección, ej: "Casa de Juan Pérez, frente a la iglesia"

  public function __construct(
    int $idLocation,
    string $provinceLocation,
    string $cantonLocation,
    string $districtLocation,
    string $addressLocation
  ) {
    $this->idLocation = $idLocation;
    $this->provinceLocation = $provinceLocation;
    $this->cantonLocation = $cantonLocation;
    $this->districtLocation = $districtLocation;
    $this->addressLocation = $addressLocation;
  }

  // Getters
  public function getIdLocation(): int
  {
    return $this->idLocation;
  }

  public function getProvinceLocation(): string
  {
    return $this->provinceLocation;
  }

  public function getCantonLocation(): string
  {
    return $this->cantonLocation;
  }

  public function getDistrictLocation(): string
  {
    return $this->districtLocation;
  }

  public function getAddressLocation(): string
  {
    return $this->addressLocation;
  }

  // Setters
  public function setIdLocation(int $idLocation): void
  {
    $this->idLocation = $idLocation;
  }

  public function setProvinceLocation(string $provinceLocation): void
  {
    $this->provinceLocation = $provinceLocation;
  }

  public function setCantonLocation(string $cantonLocation): void
  {
    $this->cantonLocation = $cantonLocation;
  }

  public function setDistrictLocation(string $districtLocation): void
  {
    $this->districtLocation = $districtLocation;
  }

  public function setAddressLocation(string $addressLocation): void
  {
    $this->addressLocation = $addressLocation;
  }
}
