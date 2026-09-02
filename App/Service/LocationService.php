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

    public function validateAndCreate(string $province, string $canton, string $district, ?string $town = null, ?string $description = null): int
    {
        $province = trim($province);
        $canton = trim($canton);
        $district = trim($district);
        $town = $town !== null ? trim($town) : null;
        $description = $description !== null ? trim($description) : null;

        if ($province === '' || $canton === '' || $district === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }

        $newLocation = new Location(
            idLocation: 0,
            provinceLocation: $province,
            cantonLocation: $canton,
            districtLocation: $district,
            townLocation: $town,
            descriptionLocation: $description
        );

        return $this->locationRepo->save($newLocation);
    }

    public function findById(int $idLocation): ?Location
    {
        return $this->locationRepo->findById($idLocation);
    }
}
