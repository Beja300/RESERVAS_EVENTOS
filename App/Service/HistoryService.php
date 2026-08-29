<?php

require_once __DIR__ . '/../Repository/HistoryRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Model/History.php';
require_once __DIR__ . '/../Model/HistoryAction.php';
require_once __DIR__ . '/VenueService.php';

class HistoryService
{
  private const MIN_HISTORY_FOR_PERSONAL = 3;
  private const ENTITY_VENUE = 'Venue';

  private const POPULARITY_ACTIONS = [
    HistoryAction::VIEW,
    HistoryAction::BOOKING,
    HistoryAction::PURCHASE
  ];

  private HistoryRepository $historyRepo;
  private VenueService $venueService;
  private LocationRepository $locationRepo;

  public function __construct(PDO $connection)
  {
    $this->historyRepo = new HistoryRepository();
    $this->venueService = new VenueService($connection);
    $this->locationRepo = new LocationRepository($connection);
  }

  // =========================================================
  // GUARDAR ACCIÓN
  // =========================================================
  public function logAction(
    int $roleId,
    string $action,
    ?string $entity = null,
    ?int $entityId = null
  ): void {

    $history = new History(
      roleId: $roleId,
      action: $action,
      entity: $entity,
      entityId: $entityId
    );

    $this->historyRepo->save($history);
  }

  // =========================================================
  // GUARDAR VISUALIZACIÓN DE VENUE
  // =========================================================
  public function logVenueView(
    int $roleId,
    int $venueId
  ): void {

    $this->logAction(
      $roleId,
      HistoryAction::VIEW,
      self::ENTITY_VENUE,
      $venueId
    );
  }

  // =========================================================
  // GUARDAR RESERVA DE VENUE
  // =========================================================
  public function logVenueBooking(
    int $roleId,
    int $venueId
  ): void {

    $this->logAction(
      $roleId,
      HistoryAction::BOOKING,
      self::ENTITY_VENUE,
      $venueId
    );
  }

  // =========================================================
  // GUARDAR COMPRA DE VENUE
  // =========================================================
  public function logVenuePurchase(
    int $roleId,
    int $venueId
  ): void {

    $this->logAction(
      $roleId,
      HistoryAction::PURCHASE,
      self::ENTITY_VENUE,
      $venueId
    );
  }

  // =========================================================
  // GUARDAR BÚSQUEDA (por ubicación y/o tipo)
  // =========================================================
  public function logVenueSearch(int $roleId, array $filters = [], ?string $type = null): void
  {
    $locationId = null;

    if (!empty($filters['province']) && !empty($filters['canton']) && !empty($filters['district'])) {
      $locationId = $this->locationRepo->findIdByParts(
        $filters['province'],
        $filters['canton'],
        $filters['district']
      );
    }

    $this->logAction(
      $roleId,
      HistoryAction::SEARCH,
      self::ENTITY_VENUE,
      $locationId
    );
  }

  // =========================================================
  // LISTAR HISTORIAL
  // =========================================================
  public function listByRole(int $roleId): array
  {
    return $this->historyRepo->listByRole($roleId);
  }

  // =========================================================
  // RECOMENDAR VENUES
  // =========================================================
  public function recommendVenues(
    int $roleId,
    int $limit = 5
  ): array {

    $history = $this->historyRepo->listByRole($roleId);

    $venueHistory = [];

    foreach ($history as $item) {

      if (
        $item->getEntity() === self::ENTITY_VENUE &&
        $item->getEntityId() !== null
      ) {
        $venueHistory[] = $item;
      }
    }

    // Usuario nuevo o con poco historial
    if (count($venueHistory) < self::MIN_HISTORY_FOR_PERSONAL) {

      return $this->getPopularVenues($limit);
    }

    // Usuario con suficiente historial
    $personalRecommendations = $this->getPersonalRecommendations(
      $venueHistory,
      $limit
    );

    // Ya tenemos suficientes recomendaciones
    if (count($personalRecommendations) >= $limit) {

      return array_slice(
        $personalRecommendations,
        0,
        $limit
      );
    }

    // Faltan recomendaciones
    $excludeIds = [];

    foreach ($personalRecommendations as $venue) {
      $excludeIds[] = $venue->getIdVenue();
    }

    $missing = $limit - count($personalRecommendations);

    $popularRecommendations = $this->getPopularVenues(
      $missing,
      $excludeIds
    );

    foreach ($popularRecommendations as $venue) {
      $excludeIds[] = $venue->getIdVenue();
    }

    $merged = array_merge(
      $personalRecommendations,
      $popularRecommendations
    );

    // Si aún faltan, priorizar búsquedas recientes por ubicación
    if (count($merged) < $limit) {
      $locationMatches = $this->getLocationRecommendations(
        $roleId,
        $excludeIds,
        $limit - count($merged)
      );

      $merged = array_merge($merged, $locationMatches);
    }

    return $merged;
  }

  // =========================================================
  // RECOMENDACIONES POR UBICACIÓN (provincias buscadas recientemente)
  // =========================================================
  private function getLocationRecommendations(
    int $roleId,
    array $excludeIds,
    int $limit
  ): array {

    $history = $this->historyRepo->listByRole($roleId);

    // Provincias asociadas a búsquedas recientes (SEARCH con ubicación)
    $provinces = [];

    foreach ($history as $item) {

      if (
        $item->getAction() !== HistoryAction::SEARCH ||
        $item->getEntity() !== self::ENTITY_VENUE ||
        $item->getEntityId() === null ||
        $item->getEntityId() <= 0
      ) {
        continue;
      }

      $location = $this->locationRepo->findById($item->getEntityId());

      if ($location !== null) {
        $provinces[$location->getProvinceLocation()] = true;
      }
    }

    if (empty($provinces)) {
      return [];
    }

    $recommendations = [];

    foreach ($this->venueService->findActive() as $venue) {

      if (in_array($venue->getIdVenue(), $excludeIds, true)) {
        continue;
      }

      $venueLocation = $this->locationRepo->findById($venue->getIdLocation());

      if ($venueLocation === null || !isset($provinces[$venueLocation->getProvinceLocation()])) {
        continue;
      }

      $recommendations[] = $venue;
      $excludeIds[] = $venue->getIdVenue();

      if (count($recommendations) >= $limit) {
        break;
      }
    }

    return $recommendations;
  }

  // =========================================================
  // RECOMENDACIONES PERSONALIZADAS
  // =========================================================
  private function getPersonalRecommendations(
    array $venueHistory,
    int $limit
  ): array {

    $typeCounts = [];
    $convertedIds = [];

    foreach ($venueHistory as $history) {

      $venueId = $history->getEntityId();

      // Si reservó o compró este Venue,
      // no queremos recomendárselo nuevamente.
      if (
        $history->getAction() === HistoryAction::BOOKING ||
        $history->getAction() === HistoryAction::PURCHASE
      ) {
        $convertedIds[$venueId] = true;
      }

      $venue = $this->venueService->findById($venueId);

      if ($venue !== null) {

        $type = $venue->getTypeVenue();

        if ($type !== '') {

          if (!isset($typeCounts[$type])) {
            $typeCounts[$type] = 0;
          }

          $typeCounts[$type]++;
        }
      }
    }

    // No encontramos ningún tipo
    if (empty($typeCounts)) {
      return [];
    }

    // Ordenamos los tipos de mayor a menor
    arsort($typeCounts);

    // Obtenemos el tipo más interactuado
    $topType = array_key_first($typeCounts);

    // Obtenemos todos los venues activos
    $venues = $this->venueService->findActive();

    $recommendations = [];

    foreach ($venues as $venue) {

      // Debe ser del mismo tipo
      if ($venue->getTypeVenue() !== $topType) {
        continue;
      }

      // No recomendar uno que ya reservó o compró
      if (isset($convertedIds[$venue->getIdVenue()])) {
        continue;
      }

      $recommendations[] = $venue;

      if (count($recommendations) >= $limit) {
        break;
      }
    }

    return $recommendations;
  }

  // =========================================================
  // VENUES MÁS POPULARES
  // =========================================================
  private function getPopularVenues(
    int $limit,
    array $excludeIds = []
  ): array {

    $popularIds = $this->historyRepo->mostInteractedEntityIds(
      self::ENTITY_VENUE,
      self::POPULARITY_ACTIONS,
      $limit + count($excludeIds) + 5
    );

    $recommendations = [];

    foreach ($popularIds as $venueId) {

      // No incluir venues excluidos
      if (in_array($venueId, $excludeIds, true)) {
        continue;
      }

      $venue = $this->venueService->findById($venueId);

      // Solo venues activos
      if ($venue !== null && $venue->getIsActive()) {

        $recommendations[] = $venue;
      }

      if (count($recommendations) >= $limit) {
        break;
      }
    }

    return $recommendations;
  }
}
