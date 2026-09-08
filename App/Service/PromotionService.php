<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/PromotionRepository.php';
require_once __DIR__ . '/../Repository/PromotionServiceRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Model/Promotion.php';
require_once __DIR__ . '/../Model/PromotionService.php';

class PromotionService
{
  private PromotionRepository $promotionRepo;
  private PromotionServiceRepository $promotionServiceRepo;
  private VenueRepository $venueRepo;
  private ServiceRepository $serviceRepo;

  public function __construct(PDO $connection)
  {
    $this->promotionRepo = new PromotionRepository($connection);
    $this->promotionServiceRepo = new PromotionServiceRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->serviceRepo = new ServiceRepository($connection);
  }

  public function assertOwnsVenue(int $ownerPk, int $venuePk): void
  {
    $venue = $this->venueRepo->findById($venuePk);

    if ($venue === null || $venue->getIdOwner() !== $ownerPk) {
      throw new BusinessRuleException('No tienes permiso sobre este local.');
    }
  }

  // =========================================================
  // CREAR PROMOCIÓN
  // =========================================================
  public function create(
    int $venuePk,
    string $label,
    ?string $description,
    ?string $startDate,
    ?string $endDate,
    int $minServices = 1
  ): int {
    if (trim($label) === '') {
      throw new BusinessRuleException('La etiqueta de la promoción es obligatoria.');
    }

    if ($minServices < 1) {
      throw new BusinessRuleException('El mínimo de servicios debe ser al menos 1.');
    }

    return $this->promotionRepo->save(
      new Promotion(
        idPromotion: 0,
        idVenue: $venuePk,
        description: $description ?? '',
        label: $label,
        startDate: $startDate,
        endDate: $endDate,
        minServices: $minServices
      )
    );
  }

  // =========================================================
  // AGREGAR SERVICIO A UNA PROMOCIÓN
  // =========================================================
  public function addService(int $promotionPk, int $servicePk): int
  {
    if ($this->promotionRepo->findById($promotionPk) === null) {
      throw new BusinessRuleException('La promoción no existe.');
    }

    if ($this->serviceRepo->findById($servicePk) === null) {
      throw new BusinessRuleException('El servicio a incluir no existe.');
    }

    return $this->promotionServiceRepo->save(
      new PromotionServiceLink(
        idPromotionService: 0,
        idPromotion: $promotionPk,
        idService: $servicePk
      )
    );
  }

  // =========================================================
  // SERVICIOS INCLUIDOS EN UNA PROMOCIÓN
  // =========================================================
  public function getServices(int $promotionPk): array
  {
    return $this->promotionServiceRepo->findByPromotion($promotionPk);
  }

  // =========================================================
  // PROMOCIONES ACTIVAS DE UN LOCAL (etiqueta en catálogo)
  // =========================================================
  public function getActiveByVenue(int $venuePk, ?string $date = null): array
  {
    return $this->promotionRepo->findActiveByVenue($venuePk, $date ?? date('Y-m-d'));
  }

  // =========================================================
  // TODAS LAS PROMOCIONES DE UN LOCAL (panel del propietario)
  // =========================================================
  public function getByVenue(int $venuePk): array
  {
    return $this->promotionRepo->findByVenue($venuePk);
  }
}
