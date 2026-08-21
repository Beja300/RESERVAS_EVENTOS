<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Model/Service.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';


class ServiceService
{
    private ServiceRepository $serviceRepo;

    public function __construct()
    {
        $this->serviceRepo = new ServiceRepository(DataBase::getConnection());
    }

    public function assertCanBeBooked(int $servicePk): void
    {
        $service = $this->serviceRepo->findById($servicePk);
        if ($service === null || !$service->getIsActive()) {
            throw new BusinessRuleException("Este servicio ya no está disponible.");
        }
        if ($service->getStateService() !== 'approved') {
            throw new BusinessRuleException("Este servicio todavía no ha sido aprobado.");
        }
    }

    // Solo el Admin puede llamar approve()/reject() -- el Controller ya valida $_SESSION['type'].
    public function approve(int $servicePk): void
    {
        $this->serviceRepo->updateState($servicePk, 'approved');
    }

    public function reject(int $servicePk): void
    {
        $this->serviceRepo->updateState($servicePk, 'rejected');
    }

    public function validateAndCreate(int $venueFk, string $name, float $price, ?string $type = null): int
    {
        if ($price <= 0) {
            throw new BusinessRuleException("El precio del servicio debe ser mayor a 0.");
        }
        if (trim($name) === '') {
            throw new BusinessRuleException("El nombre del servicio es obligatorio.");
        }
        // Todo servicio nuevo entra en 'pending' -- solo aparece en el
        // catálogo del Cliente después de que el Admin lo aprueba.
        $service = new Service($venueFk, $name, $price, $type, status: 'pending');
        return $this->serviceRepo->save($service);
    }

    public function validateAndUpdate(Service $service, string $name, ?string $type, float $price, bool $active): void
    {
        if ($price <= 0) {
            throw new BusinessRuleException("El precio del servicio debe ser mayor a 0.");
        }
        $service->setNameService($name);
        $service->setTypeService($type);
        $service->setPriceService($price);
        $service->setIsActive($active);
        $this->serviceRepo->update($service);
    }
}