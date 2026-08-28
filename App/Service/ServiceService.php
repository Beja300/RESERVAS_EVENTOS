<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Model/Service.php';

class ServiceService
{
    private ServiceRepository $serviceRepo;

    public function __construct(ServiceRepository $serviceRepo)
    {
        $this->serviceRepo = $serviceRepo;
    }

    public function assertCanBeBooked(int $servicePk): void
    {
        $service = $this->serviceRepo->findById($servicePk);

        if ($service === null || !$service->getIsActive()) {
            throw new BusinessRuleException("Este servicio ya no está disponible.");
        }

        if ($service->getStateService() !== 'aprobado') {
            throw new BusinessRuleException("Este servicio todavía no ha sido aprobado.");
        }
    }

    // Solo el Admin puede llamar approve()/reject() -- el Controller ya valida $_SESSION['type'].
    public function approve(int $servicePk): void
    {
        $this->serviceRepo->updateState($servicePk, 'aprobado');
    }

    public function reject(int $servicePk): void
    {
        $this->serviceRepo->updateState($servicePk, 'rechazado');
    }

    public function validateAndCreate(int $venueFk, string $name, float $price, ?string $type = null): int
    {
        if ($price <= 0) {
            throw new BusinessRuleException("El precio del servicio debe ser mayor a 0.");
        }

        if (trim($name) === '') {
            throw new BusinessRuleException("El nombre del servicio es obligatorio.");
        }

        // Todo servicio nuevo entra en 'solicitado' -- solo aparece en el
        // catálogo del Cliente después de que el Admin lo aprueba.
        $service = new Service(
            idService: 0,
            idLocal: $venueFk,
            nameService: $name,
            typeService: $type,
            priceService: $price,
            stateService: 'solicitado',
            isActive: true
        );

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

    // =========================================================
    // BUSCAR POR ID
    // =========================================================
    public function findById(int $servicePk): ?Service
    {
        return $this->serviceRepo->findById($servicePk);
    }

    // =========================================================
    // TODOS LOS SERVICIOS DE UN LOCAL (panel del Owner)
    // =========================================================
    public function findByLocal(int $venueFk): array
    {
        return $this->serviceRepo->findByLocal($venueFk);
    }

    // =========================================================
    // SERVICIOS DISPONIBLES DE UN LOCAL (catálogo del Cliente)
    // =========================================================
    public function findAvailableByLocal(int $venueFk): array
    {
        return $this->serviceRepo->findAvailableByLocal($venueFk);
    }

    // =========================================================
    // SERVICIOS PENDIENTES DE APROBACIÓN (panel del Admin)
    // =========================================================
    public function findPending(): array
    {
        return $this->serviceRepo->findPending();
    }
}
