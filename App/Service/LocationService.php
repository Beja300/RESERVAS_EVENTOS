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

        // Provincia/cantón/distrito no son únicos: varios locales pueden
        // coexistir en la misma zona. Solo se reutiliza la fila si el detalle
        // (pueblo + señas) coincide exactamente; si no, se crea una nueva.
        $existingId = $this->locationRepo->findIdByPartsAndDetail($province, $canton, $district, $town, $description);
        if ($existingId !== null) {
            $this->syncCoordinates($existingId, $latitude, $longitude);
            return $existingId;
        }

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

        try {
            return $this->locationRepo->save($newLocation);
        } catch (PDOException $e) {
            // Solo un duplicado exacto (mismo detalle) se reutiliza. No se
            // fusionan locales distintos que compartan provincia/cantón/distrito.
            $isDuplicate = $e->getCode() === '23000' || str_contains($e->getMessage(), '1062');
            if ($isDuplicate) {
                $existingId = $this->locationRepo->findIdByPartsAndDetail($province, $canton, $district, $town, $description);
                if ($existingId !== null) {
                    $this->syncCoordinates($existingId, $latitude, $longitude);
                    return $existingId;
                }
            }
            throw $e;
        }
    }

    public function findById(int $idLocation): ?Location
    {
        return $this->locationRepo->findById($idLocation);
    }

    public function validateAndUpdate(int $idLocation, string $province, string $canton, string $district, ?string $town = null, ?string $description = null, ?float $latitude = null, ?float $longitude = null): void
    {
        $current = $this->locationRepo->findById($idLocation);

        if ($current === null) {
            throw new BusinessRuleException('La ubicación no existe.');
        }

        $province = trim($province);
        $canton = trim($canton);
        $district = trim($district);
        $town = $town !== null ? trim($town) : null;
        $description = $description !== null ? trim($description) : null;

        if ($province === '' || $canton === '' || $district === '') {
            throw new BusinessRuleException('Provincia, cantón y distrito son obligatorios.');
        }

        self::assertValidCoordinates($latitude, $longitude);

        // Integridad lógica: no permitir un duplicado exacto (misma provincia,
        // cantón, distrito y detalle) controlada desde PHP. Un mismo distrito
        // puede contener varios locales con direcciones diferentes.
        $duplicateId = $this->locationRepo->findIdByPartsAndDetail($province, $canton, $district, $town, $description);

        if ($duplicateId !== null && $duplicateId !== $idLocation) {
            throw new BusinessRuleException('Ya existe una ubicación con esa provincia, cantón, distrito y detalle.');
        }

        $location = new Location(
            idLocation: $idLocation,
            provinceLocation: $province,
            cantonLocation: $canton,
            districtLocation: $district,
            townLocation: $town,
            descriptionLocation: $description,
            latitudeLocation: $latitude,
            longitudeLocation: $longitude
        );

        $this->locationRepo->update($location);
    }

    /**
     * Devuelve el id de una ubicación con el mismo detalle geográfico
     * (provincia, cantón, distrito, pueblo y señas). No reutiliza por
     * cantón/distrito solo, para no mezclar locales distintos de la misma zona.
     * Si la fila ya existe y llegan coordenadas nuevas, las actualiza.
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

        $town = $town !== null ? trim($town) : null;
        $description = $description !== null ? trim($description) : null;

        $existing = $this->locationRepo->findIdByPartsAndDetail($province, $canton, $district, $town, $description);

        if ($existing !== null) {
            $this->syncCoordinates($existing, $latitude, $longitude);
            return $existing;
        }

        return $this->validateAndCreate($province, $canton, $district, $town, $description, $latitude, $longitude);
    }

    private function syncCoordinates(int $idLocation, ?float $latitude, ?float $longitude): void
    {
        if ($latitude === null || $longitude === null) {
            return;
        }

        $current = $this->locationRepo->findById($idLocation);
        if ($current === null) {
            return;
        }

        if ($current->getLatitudeLocation() !== $latitude || $current->getLongitudeLocation() !== $longitude) {
            $this->locationRepo->updateCoordinates($idLocation, $latitude, $longitude);
        }
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