<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';

class OwnerService
{
    private VenueRepository $venueRepo;

    public function __construct(PDO $connection)
    {
        $this->venueRepo = new VenueRepository($connection);
    }

    public function hasActiveVenue(int $ownerPk): bool
    {
        foreach ($this->venueRepo->findByOwner($ownerPk) as $venue) {
            if ($venue->getIsActive()) {
                return true;
            }
        }
        return false;
    }

    public function assertOwnsVenue(int $ownerPk, int $venuePk): void
    {
        $venue = $this->venueRepo->findById($venuePk);
        if ($venue === null || $venue->getIdOwner() !== $ownerPk) {
            throw new BusinessRuleException("No tienes permiso sobre este local.");
        }
    }
}
