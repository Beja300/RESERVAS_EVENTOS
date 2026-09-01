<?php

class Location
{
    private int $idLocation;
    private string $provinceLocation;
    private string $cantonLocation;
    private string $districtLocation;
    private ?string $townLocation;
    private ?string $descriptionLocation;

    public function __construct(
        int $idLocation = 0,
        string $provinceLocation = '',
        string $cantonLocation = '',
        string $districtLocation = '',
        ?string $townLocation = null,
        ?string $descriptionLocation = null
    ) {
        $this->idLocation = $idLocation;
        $this->provinceLocation = $provinceLocation;
        $this->cantonLocation = $cantonLocation;
        $this->districtLocation = $districtLocation;
        $this->townLocation = $townLocation;
        $this->descriptionLocation = $descriptionLocation;
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
}