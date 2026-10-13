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

    if ($this->promotionRepo->existsByLabel($venuePk, $label)) {
      throw new BusinessRuleException('Ya existe una promoción con esa etiqueta en este local.');
    }

    if ($minServices < 1) {
      throw new BusinessRuleException('El mínimo de servicios debe ser al menos 1.');
    }

    $this->validateDates($startDate, $endDate);

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
  // BUSCAR UNA PROMOCIÓN POR ID (incluye inactivas)
  // =========================================================
  public function find(int $promotionPk): ?Promotion
  {
    return $this->promotionRepo->findById($promotionPk);
  }

  // =========================================================
  // ACTUALIZAR PROMOCIÓN (activa/desactiva incluido)
  // =========================================================
  public function update(
    int $promotionPk,
    string $label,
    ?string $description,
    ?string $startDate,
    ?string $endDate,
    int $minServices,
    bool $isActive
  ): void {
    $promotion = $this->promotionRepo->findById($promotionPk);

    if ($promotion === null) {
      throw new BusinessRuleException('La promoción no existe.');
    }

    if (trim($label) === '') {
      throw new BusinessRuleException('La etiqueta de la promoción es obligatoria.');
    }

    if ($this->promotionRepo->existsByLabel($promotion->getIdVenue(), $label, $promotionPk)) {
      throw new BusinessRuleException('Ya existe una promoción con esa etiqueta en este local.');
    }

    if ($minServices < 1) {
      throw new BusinessRuleException('El mínimo de servicios debe ser al menos 1.');
    }

    $this->validateDates($startDate, $endDate);

    # validate
    $linked = $this->promotionServiceRepo->findByPromotion($promotionPk);
    if ($minServices > count($linked)) {
      throw new BusinessRuleException('El mínimo de servicios no puede ser mayor al número de servicios vinculados a la promoción.');
    }

    $promotion->setLabel(trim($label));
    $promotion->setDescription($description ?? '');
    $promotion->setStartDate($startDate);
    $promotion->setEndDate($endDate);
    $promotion->setMinServices($minServices);
    $promotion->setIsActive($isActive);

    $this->promotionRepo->update($promotion);
  }

  // =========================================================
  // VALIDACIÓN DE FECHAS DE UNA PROMOCIÓN
  // (formato real, fin >= inicio y ninguna supera un año)
  // =========================================================
  private function validateDates(?string $startDate, ?string $endDate): void
  {
    $start = ($startDate === null || trim($startDate) === '') ? null : trim($startDate);
    $end   = ($endDate === null || trim($endDate) === '') ? null : trim($endDate);

    if ($start !== null && !$this->isRealDate($start)) {
      throw new BusinessRuleException('La fecha de inicio de la promoción no es válida.');
    }

    if ($end !== null && !$this->isRealDate($end)) {
      throw new BusinessRuleException('La fecha de fin de la promoción no es válida.');
    }

    if ($start !== null && $end !== null && $end < $start) {
      throw new BusinessRuleException('La fecha de fin no puede ser anterior a la de inicio.');
    }

    $max = date('Y-m-d', strtotime('+1 year'));

    if ($start !== null && $start > $max) {
      throw new BusinessRuleException('La fecha de inicio no puede superar un año desde hoy.');
    }

    if ($end !== null && $end > $max) {
      throw new BusinessRuleException('La fecha de fin no puede superar un año desde hoy.');
    }
  }

  private function isRealDate(string $value): bool
  {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
      return false;
    }

    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
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
