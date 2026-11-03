<?php

class Location
{
    private int $idLocation;
    private string $provinceLocation;
    private string $cantonLocation;
    private string $districtLocation;
    private ?string $townLocation;
    private ?string $descriptionLocation;
    private ?float $latitudeLocation;
    private ?float $longitudeLocation;

    public function __construct(
        int $idLocation = 0,
        string $provinceLocation = '',
        string $cantonLocation = '',
        string $districtLocation = '',
        ?string $townLocation = null,
        ?string $descriptionLocation = null,
        ?float $latitudeLocation = null,
        ?float $longitudeLocation = null
    ) {
        $this->idLocation = $idLocation;
        $this->provinceLocation = $provinceLocation;
        $this->cantonLocation = $cantonLocation;
        $this->districtLocation = $districtLocation;
        $this->townLocation = $townLocation;
        $this->descriptionLocation = $descriptionLocation;
        $this->latitudeLocation = $latitudeLocation;
        $this->longitudeLocation = $longitudeLocation;
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

    public function getTownLocation(): ?string
    {
        return $this->townLocation;
    }

    public function getDescriptionLocation(): ?string
    {
        return $this->descriptionLocation;
    }

    public function getLatitudeLocation(): ?float
    {
        return $this->latitudeLocation;
    }

    public function getLongitudeLocation(): ?float
    {
        return $this->longitudeLocation;
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

    public function setTownLocation(?string $townLocation): void
    {
        $this->townLocation = $townLocation;
    }

    public function setDescriptionLocation(?string $descriptionLocation): void
    {
        $this->descriptionLocation = $descriptionLocation;
    }

    public function setLatitudeLocation(?float $latitudeLocation): void
    {
        $this->latitudeLocation = $latitudeLocation;
    }

    public function setLongitudeLocation(?float $longitudeLocation): void
    {
        $this->longitudeLocation = $longitudeLocation;
    }
}