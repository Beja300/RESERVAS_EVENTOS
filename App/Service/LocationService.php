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

    /**
     * Devuelve el id de una ubicación que coincida con las partes dadas,
     * reutilizando filas existentes (por partes exactas o solo por cantón)
     * para no duplicar registros; solo crea una nueva si no existe ninguna.
     */
    public function findOrCreateByParts(string $province, string $canton, string $district, ?string $town = null, ?string $description = null): int
    {
        $province = trim($province);
        $canton = trim($canton);
        $district = trim($district);

        if ($province === '' || $canton === '' || $district === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }

        $existing = $this->locationRepo->findIdByParts($province, $canton, $district);

        if ($existing === null) {
            $existing = $this->locationRepo->findIdByCanton($province, $canton);
        }

        if ($existing !== null) {
            return $existing;
        }

        return $this->validateAndCreate($province, $canton, $district, $town, $description);
    }
}
