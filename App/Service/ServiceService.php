<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ImageStorageService.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/ServiceGroupRepository.php';
require_once __DIR__ . '/../Repository/ServiceImageRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/Service.php';
require_once __DIR__ . '/../Model/ServiceGroup.php';
require_once __DIR__ . '/../Model/ServiceImage.php';

class ServiceService
{
  private ServiceRepository $serviceRepo;
  private ?ServiceHistoryRepository $historyRepo;
  private ?ServiceGroupRepository $groupRepo;
  private ?ServiceImageRepository $imageRepo;
  private ?VenueRepository $venueRepo;

  public function __construct(
    ServiceRepository $serviceRepo,
    ?ServiceHistoryRepository $historyRepo = null,
    ?ServiceGroupRepository $groupRepo = null,
    ?ServiceImageRepository $imageRepo = null,
    ?VenueRepository $venueRepo = null
  ) {
    $this->serviceRepo = $serviceRepo;
    $this->historyRepo = $historyRepo;
    $this->groupRepo = $groupRepo;
    $this->imageRepo = $imageRepo;
    $this->venueRepo = $venueRepo;
  }

  public function assertCanBeBooked(int $servicePk): void
  {
    $service = $this->serviceRepo->findById($servicePk);

    if ($service === null || !$service->getIsActive()) {
      throw new BusinessRuleException('Este servicio ya no está disponible.');
    }
  }

  /**
   * Valida que la cantidad pedida respete el mínimo y el máximo del servicio.
   * $existingQuantity es lo ya agregado a la reserva (0 al crear la reserva).
   */
  public function assertQuantityAllowed(Service $service, int $quantity, int $existingQuantity = 0): void
  {
    if ($quantity <= 0) {
      throw new BusinessRuleException('La cantidad debe ser mayor a 0.');
    }

    $max = $service->getMaxQuantity();
    if ($max !== null && $existingQuantity + $quantity > $max) {
      throw new BusinessRuleException(
        'La cantidad máxima de este servicio es ' . $max
        . ($existingQuantity > 0 ? ' y ya agregaste ' . $existingQuantity . '.' : '.')
      );
    }

    $min = $service->getMinQuantity();
    if ($existingQuantity + $quantity < $min) {
      throw new BusinessRuleException(
        'La cantidad mínima de este servicio es ' . $min
        . ($existingQuantity > 0 ? ' y ya agregaste ' . $existingQuantity . '.' : '.')
      );
    }
  }

  /**
   * Descuento equivalente a las primeras unidades incluidas del servicio.
   */
  public function lineDiscount(Service $service, int $quantity, int $existingQuantity = 0): float
  {
    $includedRemaining = max(0, $service->getIncludedQuantity() - $existingQuantity);
    $freeQuantity = min($quantity, $includedRemaining);

    return round($freeQuantity * $service->getPriceService(), 2);
  }

  /**
   * Crea un servicio. $extra admite ownerId, description, unit, minQuantity,
   * maxQuantity, includedQuantity, groupId. Si no recibe groupId y conoce al
   * propietario, crea el grupo automáticamente.
   */
  public function validateAndCreate(int $venueFk, string $name, float $price, ?string $type = null, array $extra = []): int
  {
    $this->validateData($name, $price, $type, $extra);

    if ($this->serviceRepo->existsByNameForLocal($venueFk, $name)) {
      throw new BusinessRuleException('Ya existe un servicio con ese nombre en este local.');
    }

    $ownerId = (int) ($extra['ownerId'] ?? 0);
    $groupId = isset($extra['groupId']) ? (int) $extra['groupId'] : 0;

    if ($groupId <= 0 && $ownerId > 0 && $this->groupRepo !== null) {
      $group = $this->groupRepo->findByNameForOwner($ownerId, $name);
      if ($group === null) {
        $groupId = $this->groupRepo->save(new ServiceGroup(
          idOwner: $ownerId,
          name: $name
        ));
      } else {
        $groupId = $group->getIdGroup();
      }
    }

    $service = new Service(
      idService: 0,
      idLocal: $venueFk,
      nameService: $name,
      typeService: $type,
      priceService: $price,
      stateService: 'aprobado',
      isActive: (bool) ($extra['active'] ?? true),
      descriptionService: $this->nullableString($extra['description'] ?? null),
      unitService: (string) ($extra['unit'] ?? 'unidad'),
      minQuantity: (int) ($extra['minQuantity'] ?? 1),
      maxQuantity: isset($extra['maxQuantity']) && $extra['maxQuantity'] !== '' ? (int) $extra['maxQuantity'] : null,
      includedQuantity: (int) ($extra['includedQuantity'] ?? 0),
      idGroup: $groupId > 0 ? $groupId : null
    );

    $idService = $this->serviceRepo->save($service);
    $this->snapshotPrice($idService, $price);

    return $idService;
  }

  /**
   * Crea el mismo servicio en uno o varios locales. Devuelve el resumen:
   * ['groupId' => int, 'created' => [venueId...], 'reactivated' => [...],
   *  'skipped' => [['venueId'=>int,'reason'=>string], ...]]
   */
  public function createForVenues(int $ownerId, array $venueIds, array $data): array
  {
    $name = trim((string) ($data['name'] ?? ''));
    $type = trim((string) ($data['type'] ?? '')) ?: null;
    $price = (float) ($data['price'] ?? 0);

    $venueIds = array_values(array_unique(array_filter(array_map('intval', $venueIds), fn(int $id): bool => $id > 0)));

    if ($venueIds === []) {
      throw new BusinessRuleException('Selecciona al menos un local para agregar el servicio.');
    }

    $this->validateData($name, $price, $type, $data);

    $groupId = 0;
    if ($this->groupRepo !== null) {
      $group = $this->groupRepo->findByNameForOwner($ownerId, $name);
      if ($group !== null) {
        $groupId = $group->getIdGroup();
      }
    }

    $result = [
      'groupId' => $groupId,
      'created' => [],
      'reactivated' => [],
      'skipped' => [],
    ];

    foreach ($venueIds as $venueId) {
      if ($this->venueRepo !== null) {
        $venue = $this->venueRepo->findById($venueId);
        if ($venue === null || $venue->getIdOwner() !== $ownerId || !$venue->getIsActive()) {
          $result['skipped'][] = ['venueId' => $venueId, 'reason' => 'El local no está disponible.'];
          continue;
        }
      }

      $existingInGroup = $groupId > 0
        ? $this->serviceRepo->findByGroupAndVenue($groupId, $venueId)
        : null;

      if ($existingInGroup !== null) {
        if (!$existingInGroup->getIsActive()) {
          $this->serviceRepo->setActive($existingInGroup->getIdService(), true);
          $result['reactivated'][] = $venueId;
        } else {
          $result['skipped'][] = ['venueId' => $venueId, 'reason' => 'El servicio ya se ofrece en este local.'];
        }
        continue;
      }

      if ($this->serviceRepo->existsByNameForLocal($venueId, $name)) {
        $result['skipped'][] = ['venueId' => $venueId, 'reason' => 'Ya existe un servicio con ese nombre en este local.'];
        continue;
      }

      if ($groupId === 0 && $this->groupRepo !== null) {
        $groupId = $this->groupRepo->save(new ServiceGroup(
          idOwner: $ownerId,
          name: $name
        ));
      }

      $service = new Service(
        idService: 0,
        idLocal: $venueId,
        nameService: $name,
        typeService: $type,
        priceService: $price,
        stateService: 'aprobado',
        isActive: true,
        descriptionService: $this->nullableString($data['description'] ?? null),
        unitService: (string) ($data['unit'] ?? 'unidad'),
        minQuantity: (int) ($data['minQuantity'] ?? 1),
        maxQuantity: isset($data['maxQuantity']) && $data['maxQuantity'] !== '' ? (int) $data['maxQuantity'] : null,
        includedQuantity: (int) ($data['includedQuantity'] ?? 0),
        idGroup: $groupId > 0 ? $groupId : null
      );

      $idService = $this->serviceRepo->save($service);
      $this->snapshotPrice($idService, $price);
      $result['created'][] = $venueId;
    }

    $result['groupId'] = $groupId;

    if ($result['created'] === [] && $result['reactivated'] === []) {
      $reasons = array_values(array_unique(array_map(
        static fn(array $item): string => $item['reason'],
        $result['skipped']
      )));
      throw new BusinessRuleException(
        $reasons !== []
          ? 'No se pudo agregar en ningún local: ' . implode(' ', $reasons)
          : 'No se pudo agregar el servicio en ningún local.'
      );
    }

    return $result;
  }

  public function validateAndUpdate(Service $service, string $name, ?string $type, float $price, bool $active, array $extra = []): void
  {
    $this->validateData($name, $price, $type, $extra);

    if ($this->serviceRepo->existsByNameForLocal($service->getIdLocal(), $name, $service->getIdService())) {
      throw new BusinessRuleException('Ya existe un servicio con ese nombre en este local.');
    }

    $this->syncGroupName($service, $name);

    $service->setNameService($name);
    $service->setTypeService($type);
    $service->setPriceService($price);
    $service->setIsActive($active);
    $service->setDescriptionService($this->nullableString($extra['description'] ?? null));
    $service->setUnitService((string) ($extra['unit'] ?? $service->getUnitService()));
    $service->setMinQuantity((int) ($extra['minQuantity'] ?? $service->getMinQuantity()));
    $service->setMaxQuantity(isset($extra['maxQuantity']) && $extra['maxQuantity'] !== '' ? (int) $extra['maxQuantity'] : null);
    $service->setIncludedQuantity((int) ($extra['includedQuantity'] ?? $service->getIncludedQuantity()));

    $this->serviceRepo->update($service);
    $this->snapshotPrice($service->getIdService(), $price);
  }

  private function syncGroupName(Service $service, string $name): void
  {
    $groupId = $service->getIdGroup();
    if ($groupId === null || $this->groupRepo === null) {
      return;
    }

    $group = $this->groupRepo->findById($groupId);
    if ($group === null || $group->getName() === $name) {
      return;
    }

    if ($this->groupRepo->findByNameForOwner($group->getIdOwner(), $name, $groupId) !== null) {
      throw new BusinessRuleException('Ya tienes otro servicio con ese nombre.');
    }

    foreach ($this->serviceRepo->findByGroup($groupId) as $member) {
      if ($this->serviceRepo->existsByNameForLocal($member->getIdLocal(), $name, $member->getIdService())) {
        throw new BusinessRuleException('El nombre ya está en uso en uno de los locales de este servicio.');
      }
    }

    $this->serviceRepo->renameByGroup($groupId, $name);
    $this->groupRepo->updateName($groupId, $name);
  }

  public function moveOrDuplicate(int $ownerId, int $idService, int $targetVenueId, string $mode): void
  {
    $service = $this->serviceRepo->findById($idService);
    if ($service === null) {
      throw new BusinessRuleException('El servicio no existe.');
    }

    if ($mode !== 'move' && $mode !== 'duplicate') {
      throw new BusinessRuleException('La acción solicitada no es válida.');
    }

    if ($service->getIdLocal() === $targetVenueId && $mode === 'move') {
      throw new BusinessRuleException('El servicio ya pertenece a ese local.');
    }

    if ($this->venueRepo !== null) {
      $target = $this->venueRepo->findById($targetVenueId);
      if ($target === null || $target->getIdOwner() !== $ownerId || !$target->getIsActive()) {
        throw new BusinessRuleException('El local de destino no está disponible.');
      }
    }

    $groupId = $service->getIdGroup();
    if ($groupId !== null && $this->serviceRepo->findByGroupAndVenue($groupId, $targetVenueId) !== null) {
      throw new BusinessRuleException('Este servicio ya se ofrece en el local de destino.');
    }

    if ($this->serviceRepo->existsByNameForLocal($targetVenueId, $service->getNameService())) {
      throw new BusinessRuleException('Ya existe un servicio con ese nombre en el local de destino.');
    }

    if ($mode === 'move') {
      $this->serviceRepo->setVenue($idService, $targetVenueId);
      return;
    }

    $copy = new Service(
      idService: 0,
      idLocal: $targetVenueId,
      nameService: $service->getNameService(),
      typeService: $service->getTypeService(),
      priceService: $service->getPriceService(),
      stateService: 'aprobado',
      isActive: true,
      descriptionService: $service->getDescriptionService(),
      unitService: $service->getUnitService(),
      minQuantity: $service->getMinQuantity(),
      maxQuantity: $service->getMaxQuantity(),
      includedQuantity: $service->getIncludedQuantity(),
      idGroup: $groupId
    );

    $newId = $this->serviceRepo->save($copy);
    $this->snapshotPrice($newId, $copy->getPriceService());

    if ($this->imageRepo !== null) {
      foreach ($this->imageRepo->findByService($idService) as $image) {
        $this->imageRepo->save(new ServiceImage(
          idService: $newId,
          path: $image->getPath(),
          sort: $image->getSort()
        ));
      }
    }
  }

  public function listGroupsForOwner(int $ownerId): array
  {
    if ($this->groupRepo === null) {
      return [];
    }

    $result = [];
    foreach ($this->groupRepo->findByOwner($ownerId) as $group) {
      $services = $this->serviceRepo->findByGroup($group->getIdGroup());
      $activeServices = array_values(array_filter($services, static fn(Service $s): bool => $s->getIsActive()));
      $venueNames = [];
      foreach ($activeServices as $service) {
        $venue = $this->venueRepo?->findById($service->getIdLocal());
        if ($venue !== null) {
          $venueNames[] = $venue->getNameVenue();
        }
      }

      $result[] = [
        'group' => $group,
        'services' => $services,
        'activeServices' => $activeServices,
        'activeCount' => count($activeServices),
        'venueNames' => $venueNames,
      ];
    }

    return $result;
  }

  public function getGroupForOwner(int $ownerId, int $groupId): ?array
  {
    if ($this->groupRepo === null) {
      return null;
    }

    $group = $this->groupRepo->findById($groupId);
    if ($group === null || $group->getIdOwner() !== $ownerId) {
      return null;
    }

    $services = $this->serviceRepo->findByGroup($groupId);
    $byVenue = [];
    foreach ($services as $service) {
      $byVenue[$service->getIdLocal()] = $service;
    }

    $venues = [];
    if ($this->venueRepo !== null) {
      foreach ($this->venueRepo->findByOwner($ownerId) as $venue) {
        if ($venue->getIsActive()) {
          $venues[] = $venue;
        }
      }
    }

    return [
      'group' => $group,
      'servicesByVenue' => $byVenue,
      'venues' => $venues,
    ];
  }

  public function updateGroupLocations(int $ownerId, int $groupId, array $selectedVenueIds): array
  {
    $context = $this->getGroupForOwner($ownerId, $groupId);
    if ($context === null) {
      throw new BusinessRuleException('El servicio no existe o no te pertenece.');
    }

    $selectedVenueIds = array_values(array_unique(array_filter(array_map('intval', $selectedVenueIds), fn(int $id): bool => $id > 0)));
    $activeVenueIds = array_map(static fn($venue): int => $venue->getIdVenue(), $context['venues']);

    $template = null;
    foreach ($context['servicesByVenue'] as $service) {
      if ($service->getIsActive()) {
        $template = $service;
        break;
      }
      $template = $template ?? $service;
    }

    if ($template === null) {
      throw new BusinessRuleException('No hay datos base para crear las copias del servicio.');
    }

    $result = [
      'added' => [],
      'removed' => [],
      'reactivated' => [],
      'skipped' => [],
    ];

    foreach ($activeVenueIds as $venueId) {
      $existing = $context['servicesByVenue'][$venueId] ?? null;
      $isSelected = in_array($venueId, $selectedVenueIds, true);

      if ($isSelected) {
        if ($existing !== null) {
          if (!$existing->getIsActive()) {
            $this->serviceRepo->setActive($existing->getIdService(), true);
            $result['reactivated'][] = $venueId;
          }
          continue;
        }

        if ($this->serviceRepo->existsByNameForLocal($venueId, $template->getNameService())) {
          $result['skipped'][] = $venueId;
          continue;
        }

        $copy = new Service(
          idService: 0,
          idLocal: $venueId,
          nameService: $template->getNameService(),
          typeService: $template->getTypeService(),
          priceService: $template->getPriceService(),
          stateService: 'aprobado',
          isActive: true,
          descriptionService: $template->getDescriptionService(),
          unitService: $template->getUnitService(),
          minQuantity: $template->getMinQuantity(),
          maxQuantity: $template->getMaxQuantity(),
          includedQuantity: $template->getIncludedQuantity(),
          idGroup: $groupId
        );
        $newId = $this->serviceRepo->save($copy);
        $this->snapshotPrice($newId, $copy->getPriceService());
        $result['added'][] = $venueId;
      } elseif ($existing !== null && $existing->getIsActive()) {
        $this->serviceRepo->setActive($existing->getIdService(), false);
        $result['removed'][] = $venueId;
      }
    }

    return $result;
  }

  public function findById(int $servicePk): ?Service
  {
    return $this->serviceRepo->findById($servicePk);
  }

  public function findByLocal(int $venueFk): array
  {
    return $this->serviceRepo->findByLocal($venueFk);
  }

  public function findAvailableByLocal(int $venueFk): array
  {
    return $this->serviceRepo->findAvailableByLocal($venueFk);
  }

  public function getDistinctTypes(): array
  {
    return $this->serviceRepo->distinctTypes();
  }

  public function getImages(int $idService): array
  {
    return $this->imageRepo?->findByService($idService) ?? [];
  }

  public function removeImage(int $idImage): void
  {
    $this->imageRepo?->deactivate($idImage);
  }

  public function addImage(int $idService, array $file): void
  {
    if ($this->imageRepo === null) {
      return;
    }

    $path = ImageStorageService::store($file, 'services', 'service_' . $idService);
    $this->imageRepo->save(new ServiceImage(
      idService: $idService,
      path: $path,
      sort: count($this->imageRepo->findByService($idService))
    ));
  }

  private function validateData(string $name, float $price, ?string $type, array $extra): void
  {
    if ($price <= 0) {
      throw new BusinessRuleException('El precio del servicio debe ser mayor a 0.');
    }

    if (trim($name) === '') {
      throw new BusinessRuleException('El nombre del servicio es obligatorio.');
    }

    if (trim((string) $type) === '') {
      throw new BusinessRuleException('El tipo del servicio es obligatorio.');
    }

    $units = ['persona', 'unidad', 'hora', 'evento'];
    if (isset($extra['unit']) && !in_array((string) $extra['unit'], $units, true)) {
      throw new BusinessRuleException('La unidad del servicio no es válida.');
    }

    $min = (int) ($extra['minQuantity'] ?? 1);
    $max = isset($extra['maxQuantity']) && $extra['maxQuantity'] !== '' ? (int) $extra['maxQuantity'] : null;
    $included = (int) ($extra['includedQuantity'] ?? 0);

    if ($min < 1) {
      throw new BusinessRuleException('La cantidad mínima debe ser al menos 1.');
    }

    if ($max !== null && $max < $min) {
      throw new BusinessRuleException('La cantidad máxima no puede ser menor que la mínima.');
    }

    if ($included < 0) {
      throw new BusinessRuleException('La cantidad incluida no puede ser negativa.');
    }
  }

  private function snapshotPrice(int $servicePk, float $price): void
  {
    if ($this->historyRepo !== null) {
      $this->historyRepo->save(
        new ServiceHistory(
          idServiceHistory: 0,
          idService: $servicePk,
          price: $price,
          validFrom: date('Y-m-d')
        )
      );
    }
  }

  private function nullableString(mixed $value): ?string
  {
    $value = trim((string) $value);
    return $value === '' ? null : $value;
  }
}
