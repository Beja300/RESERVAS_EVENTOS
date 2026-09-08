<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/LocationService.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/Venue.php';

class VenueService
{
    private VenueRepository $venueRepo;
    private LocationService $locationService;


    public function __construct(PDO $connection)
    {
        $this->venueRepo = new VenueRepository($connection);
        $this->locationService = new LocationService(new LocationRepository($connection));
    }

    public function validateAndCreate(
        int $ownerPk,
        string $province,
        string $canton,
        string $district,
        ?string $town,
        ?string $description,
        string $name,
        ?string $type,
        ?int $capacity,
        float $price,
        ?string $image
    ): int {
        if ($capacity !== null && $capacity <= 0) {
            throw new BusinessRuleException("La capacidad del local debe ser mayor a 0.");
        }

        if ($price <= 0) {
            throw new BusinessRuleException("El precio de renta del local debe ser mayor a 0.");
        }

        if (trim($name) === '') {
            throw new BusinessRuleException("El nombre del local es obligatorio.");
        }

        $locationPk = $this->locationService->validateAndCreate(
            $province,
            $canton,
            $district,
            $town,
            $description
        );

        $venue = new Venue(
            idVenue: 0,
            idOwner: $ownerPk,
            idLocation: $locationPk,
            nameVenue: $name,
            typeVenue: $type ?? '',
            capacityVenue: $capacity ?? 0,
            priceVenue: $price,
            imageVenue: $image ?? '',
            isActive: true
        );

        return $this->venueRepo->save($venue);
    }

    public function validateAndUpdate(
        Venue $venue,
        string $name,
        ?string $type,
        ?int $capacity,
        float $price,
        ?string $image,
        bool $active,
        int $idLocation
    ): void {
        if ($capacity !== null && $capacity <= 0) {
            throw new BusinessRuleException("La capacidad del local debe ser mayor a 0.");
        }

        if ($price <= 0) {
            throw new BusinessRuleException("El precio de renta del local debe ser mayor a 0.");
        }

        $venue->setNameVenue($name);
        $venue->setTypeVenue($type ?? $venue->getTypeVenue());
        $venue->setCapacityVenue($capacity ?? $venue->getCapacityVenue());
        $venue->setPriceVenue($price);
        $venue->setImageVenue($image ?? $venue->getImageVenue());
        $venue->setIdLocation($idLocation);
        $venue->setIsActive($active);

        $this->venueRepo->update($venue);
    }

    // =========================================================
    // BUSCAR POR ID
    // =========================================================
    public function findById(int $idVenue): ?Venue
    {
        return $this->venueRepo->findById($idVenue);
    }

    // =========================================================
    // BUSCAR VENUES ACTIVOS
    // =========================================================
    public function findActive(): array
    {
        return $this->venueRepo->findActive();
    }

    // =========================================================
    // BUSCAR VENUES ACTIVOS POR FILTROS
    // =========================================================
    public function findByFilters(array $filters = []): array
    {
        return $this->venueRepo->findByFilters($filters);
    }

    // =========================================================
    // BUSCAR VENUES DE UN OWNER (panel del propietario)
    // =========================================================
    public function findByOwner(int $ownerPk): array
    {
        return $this->venueRepo->findByOwner($ownerPk);
    }
}
