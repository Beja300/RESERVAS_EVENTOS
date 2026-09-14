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

    public function validateAndCreate(string $province, string $canton, string $district, ?string $town = null, ?string $description = null, ?float $latitude = null, ?float $longitude = null): int
    {
        $province = trim($province);
        $canton = trim($canton);
        $district = trim($district);
        $town = $town !== null ? trim($town) : null;
        $description = $description !== null ? trim($description) : null;

        if ($province === '' || $canton === '' || $district === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }

        self::assertValidCoordinates($latitude, $longitude);

        $newLocation = new Location(
            idLocation: 0,
            provinceLocation: $province,
            cantonLocation: $canton,
            districtLocation: $district,
            townLocation: $town,
            descriptionLocation: $description,
            latitudeLocation: $latitude,
            longitudeLocation: $longitude
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
     * Si la fila ya existe pero recibe coordenadas nuevas, las actualiza.
     */
    public function findOrCreateByParts(string $province, string $canton, string $district, ?string $town = null, ?string $description = null, ?float $latitude = null, ?float $longitude = null): int
    {
        $province = trim($province);
        $canton = trim($canton);
        $district = trim($district);

        if ($province === '' || $canton === '' || $district === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }

        self::assertValidCoordinates($latitude, $longitude);

        $existing = $this->locationRepo->findIdByParts($province, $canton, $district);

        if ($existing === null) {
            $existing = $this->locationRepo->findIdByCanton($province, $canton);
        }

        if ($existing !== null) {
            $current = $this->locationRepo->findById($existing);
            if ($current !== null
                && $latitude !== null
                && $longitude !== null
                && ($current->getLatitudeLocation() !== $latitude || $current->getLongitudeLocation() !== $longitude)
            ) {
                $this->locationRepo->updateCoordinates($existing, $latitude, $longitude);
            }

            return $existing;
        }

        return $this->validateAndCreate($province, $canton, $district, $town, $description, $latitude, $longitude);
    }

    /**
     * Valida que las coordenadas, si se proveen, estén dentro de los rangos
     * válidos de latitud (-90..90) y longitud (-180..180).
     */
    private static function assertValidCoordinates(?float $latitude, ?float $longitude): void
    {
        if ($latitude !== null && ($latitude < -90.0 || $latitude > 90.0)) {
            throw new BusinessRuleException("La latitud debe estar entre -90 y 90.");
        }

        if ($longitude !== null && ($longitude < -180.0 || $longitude > 180.0)) {
            throw new BusinessRuleException("La longitud debe estar entre -180 y 180.");
        }

        if (($latitude === null) !== ($longitude === null)) {
            throw new BusinessRuleException("Debes indicar latitud y longitud juntas (o ninguna).");
        }
    }
}