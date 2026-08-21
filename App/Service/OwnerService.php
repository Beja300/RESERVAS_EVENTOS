<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Model/VenueRepository.php';

class OwnerService
{
    private VenueRepository $venueRepo;

    public function __construct()
    {
        $this->venueRepo = new VenueRepository();
    }

    public function hasActiveVenue(int $ownerPk): bool
    {
        foreach ($this->venueRepo->findByOwner($ownerPk) as $venue) {
            if ($venue->isActive()) {
                return true;
            }
        }
        return false;
    }

    public function assertOwnsVenue(int $ownerPk, int $venuePk): void
    {
        $venue = $this->venueRepo->findById($venuePk);
        if ($venue === null || $venue->getOwnerFk() !== $ownerPk) {
            throw new BusinessRuleException("No tienes permiso sobre este local.");
        }
    }
}