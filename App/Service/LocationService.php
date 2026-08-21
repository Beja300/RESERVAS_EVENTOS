<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Model/LocationRepository.php';
require_once __DIR__ . '/../Model/Location.php';

class LocationService
{
    private LocationRepository $locationRepo;

    public function __construct()
    {
        $this->locationRepo = new LocationRepository();
    }

    public function validateAndCreate(string $province, string $canton, string $district, ?string $detail = null): int
    {
        if (trim($province) === '' || trim($canton) === '' || trim($district) === '') {
            throw new BusinessRuleException("Provincia, cantón y distrito son obligatorios.");
        }
        foreach ($this->locationRepo->findAll() as $existing) {
            if (
                $existing->getProvince() === $province && $existing->getCanton() === $canton &&
                $existing->getDistrict() === $district && ($existing->getDetail() ?? '') === ($detail ?? '')
            ) {
                throw new BusinessRuleException("Ya existe una ubicación idéntica registrada.");
            }
        }
        return $this->locationRepo->save(new Location($province, $canton, $district, $detail));
    }
}
