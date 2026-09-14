<?php

require_once __DIR__ . '/../Repository/HistoryRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Model/History.php';
require_once __DIR__ . '/../Model/HistoryAction.php';
require_once __DIR__ . '/VenueService.php';
require_once __DIR__ . '/VenueRatingService.php';
require_once __DIR__ . '/Recommendation/RecommendationConfig.php';
require_once __DIR__ . '/Recommendation/RecommendationContext.php';
require_once __DIR__ . '/Recommendation/HybridEngine.php';

class HistoryService
{
  private const ENTITY_VENUE = 'Venue';

  private HistoryRepository $historyRepo;
  private VenueService $venueService;
  private LocationRepository $locationRepo;
  private VenueRatingService $venueRatingService;

  public function __construct(PDO $connection)
  {
    $this->historyRepo = new HistoryRepository();
    $this->venueService = new VenueService($connection);
    $this->locationRepo = new LocationRepository($connection);
    $this->venueRatingService = new VenueRatingService($connection);
  }

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

  public function logVenueCancel(int $roleId, int $venueId): void
  {
    $this->logAction($roleId, HistoryAction::CANCEL, self::ENTITY_VENUE, $venueId);
  }

  public function logVenueRating(int $roleId, int $venueId): void
  {
    $this->logAction($roleId, HistoryAction::RATING, self::ENTITY_VENUE, $venueId);
  }

  public function logVenueFavorite(int $roleId, int $venueId): void
  {
    $this->logAction($roleId, HistoryAction::FAVORITE, self::ENTITY_VENUE, $venueId);
  }

  public function logVenueUnfavorite(int $roleId, int $venueId): void
  {
    $this->historyRepo->deleteFavorite($roleId, $venueId);
  }

  public function isFavorite(int $roleId, int $venueId): bool
  {
    return $this->historyRepo->hasFavorite($roleId, $venueId);
  }

  public function favoriteVenueIdsByRole(int $roleId): array
  {
    return $this->historyRepo->favoriteVenueIdsByRole($roleId);
  }

  public function listByRole(int $roleId): array
  {
    return $this->historyRepo->listByRole($roleId);
  }

  // =========================================================
  // RECOMENDACIONES HÍBRIDAS (motor de App/Service/Recommendation)
  // =========================================================

  /**
   * Rankeo general para el dashboard del cliente (modo catálogo):
   * combina geo + rating + contenido + colaborativo.
   */
  public function recommendForUser(
    int $roleId,
    ?int $clientLocationId = null,
    int $limit = 5
  ): array {

    $venues = $this->venueService->findActive();

    $clientLocation = null;
    if ($clientLocationId !== null && $clientLocationId > 0) {
      $clientLocation = $this->locationRepo->findById($clientLocationId);
    }

    $context = $this->buildContext($venues, $clientLocation, $roleId);

    return (new HybridEngine())->rankVenues(
      $venues,
      $context,
      RecommendationConfig::MODE_CATALOG,
      $limit
    );
  }

  /**
   * "Locales cerca de ti" (modo nearby con pesos de geo reforzados).
   * Restringe los candidatos a la misma provincia del cliente para
   * conservar la intención de la sección. Devuelve [] sin ubicación.
   */
  public function recommendNear(
    ?int $locationId,
    int $limit = 5
  ): array {

    if ($locationId === null || $locationId <= 0 || $limit <= 0) {
      return [];
    }

    $clientLocation = $this->locationRepo->findById($locationId);

    if ($clientLocation === null) {
      return [];
    }

    $province = $clientLocation->getProvinceLocation();

    $near = [];
    foreach ($this->venueService->findActive() as $venue) {
      $venueLocation = $this->locationRepo->findById($venue->getIdLocation());
      if ($venueLocation !== null && $venueLocation->getProvinceLocation() === $province) {
        $near[] = $venue;
      }
    }

    $context = $this->buildContext($near, $clientLocation, 0);

    return (new HybridEngine())->rankVenues(
      $near,
      $context,
      RecommendationConfig::MODE_NEARBY,
      $limit
    );
  }

  /**
   * Rankeo del catálogo (modo catálogo): rankea el conjunto de venues ya
   * filtrado por el controlador. Con $limit <= 0 devuelve TODOS ordenados.
   */
  public function rankVenuesForCatalog(
    array $venues,
    ?Location $clientLocation,
    int $roleId,
    int $limit = 0
  ): array {

    $context = $this->buildContext($venues, $clientLocation, $roleId);

    if ($limit <= 0) {
      $limit = max(count($venues), 1);
    }

    return (new HybridEngine())->rankVenues(
      $venues,
      $context,
      RecommendationConfig::MODE_CATALOG,
      $limit
    );
  }

  /**
   * Construye el RecommendationContext para un conjunto de venues candidatos.
   */
  private function buildContext(
    array $venues,
    ?Location $clientLocation,
    int $roleId
  ): RecommendationContext {

    $context = new RecommendationContext();

    if ($clientLocation !== null) {
      $context->clientLocation = $clientLocation;
    }

    $venueIds = [];
    foreach ($venues as $venue) {
      $venueIds[$venue->getIdVenue()] = true;
    }
    $venueIds = array_keys($venueIds);

    $locationCache = [];
    foreach ($venueIds as $id) {
      $avg = $this->venueRatingService->getAverage($id);
      if ($avg !== null) {
        $context->ratingsByVenue[$id] = round($avg, 1);
      }
    }

    foreach ($venues as $venue) {
      $locId = $venue->getIdLocation();
      if ($locId <= 0) {
        $context->locationByVenue[$venue->getIdVenue()] = null;
        continue;
      }

      if (!isset($locationCache[$locId])) {
        $locationCache[$locId] = $this->locationRepo->findById($locId);
      }
      $context->locationByVenue[$venue->getIdVenue()] = $locationCache[$locId];
    }

    $context->collaborativeScores = $this->historyRepo->interactionWeightedScores(
      self::ENTITY_VENUE,
      RecommendationConfig::INTERACTION_WEIGHTS
    );

    if ($roleId > 0) {
      $context->venueHistory = $this->historyRepo->listByRole($roleId);
    }

    return $context;
  }
}
