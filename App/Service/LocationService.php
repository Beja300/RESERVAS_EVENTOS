<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Model/Location.php';

class LocationService
{
    private LocationRepository $locationRepo;

    public function __construct(LocationRepository $locationRepo)
    {
        $this->locationRepo = $locationRepo;
    }

    public function validateAndCreate(string $province, string $canton, string $district, ?string $detail = null): int
    {
        if (trim($province) === '' || trim($canton) === '' || trim($district) === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }

        foreach ($this->locationRepo->findAll() as $existing) {
            if (
                $existing->getProvinceLocation() === $province &&
                $existing->getCantonLocation() === $canton &&
                $existing->getDistrictLocation() === $district &&
                $existing->getAddressLocation() === ($detail ?? '')
            ) {
                throw new BusinessRuleException("Ya existe una ubicación idéntica registrada.");
            }
        }

        $newLocation = new Location(
            idLocation: 0,
            provinceLocation: $province,
            cantonLocation: $canton,
            districtLocation: $district,
            addressLocation: $detail ?? ''
        );

        return $this->locationRepo->save($newLocation);
    }
}
