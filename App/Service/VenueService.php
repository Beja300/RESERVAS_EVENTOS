<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/LocationService.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/Venue.php';

class VenueService {
    private VenueRepository $venueRepo;
    private LocationService $locationService;

    public function __construct() {
        $this->venueRepo = new VenueRepository();
        $this->locationService = new LocationService();
    }

    public function validateAndCreate(
        int $ownerPk,
        string $province,
        string $canton,
        string $district,
        ?string $locationDetail,
        string $name,
        ?string $type,
        ?int $capacity,
        ?string $image
    ): int {
        if ($capacity !== null && $capacity <= 0) {
            throw new BusinessRuleException("La capacidad del local debe ser mayor a 0.");
        }
        if (trim($name) === '') {
            throw new BusinessRuleException("El nombre del local es obligatorio.");
        }

        $locationPk = $this->locationService->validateAndCreate($province, $canton, $district, $locationDetail);

        $venue = new Venue($ownerPk, $locationPk, $name, $type, $capacity, $image);
        return $this->venueRepo->save($venue);
    }

    public function validateAndUpdate(Venue $venue, string $name, ?string $type, ?int $capacity, ?string $image, bool $active): void {
        if ($capacity !== null && $capacity <= 0) {
            throw new BusinessRuleException("La capacidad del local debe ser mayor a 0.");
        }
        $venue->setName($name);
        $venue->setType($type);
        $venue->setCapacity($capacity);
        $venue->setImage($image);
        $venue->setActive($active);
        $this->venueRepo->update($venue);
    }
}
