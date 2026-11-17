<?php

require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/VenueRatingService.php';
require_once __DIR__ . '/../Service/GeoService.php';
require_once __DIR__ . '/../Repository/HistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BlockedClientRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class ClientDashboardController
{
  private HistoryService $historyService;
  private HistoryRepository $historyRepo;
  private BookingRepository $bookingRepo;
  private LocationRepository $locationRepo;
  private VenueRepository $venueRepo;
  private VenueRatingService $venueRatingService;
  private BlockedClientRepository $blockedClientRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->historyService = new HistoryService($connection);
    $this->historyRepo = new HistoryRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->locationRepo = new LocationRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->venueRatingService = new VenueRatingService($connection);
    $this->blockedClientRepo = new BlockedClientRepository($connection);
  }

  // =========================================================
  // DASHBOARD / PANEL (Inicio) — locales alquilados + frecuentes
  // =========================================================
  public function dashboard(): void
  {
    require_role('client');

    $client = $_SESSION['user'];

    // Div 1 — locales alquilados (mis reservas).
    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    $venueNames = [];
    foreach ($bookings as $b) {
      if (isset($venueNames[$b->getIdLocal()])) {
        continue;
      }
      $venue = $this->venueRepo->findById($b->getIdLocal());
      $venueNames[$b->getIdLocal()] = $venue !== null
        ? $venue->getNameVenue()
        : 'Local #' . $b->getIdLocal();
    }

    // Div 2 — locales más frecuentes (los que más ha visto al detalle).
    $visitCountByVenue = $this->historyRepo->mostViewedVenueIdsByRole((int) $client->getIdRol(), 5);

    $frequentVenues = [];
    $visibleVenues = $this->filterBlockedVenues(
      $this->venueRepo->findByIds(array_keys($visitCountByVenue), false),
      (int) $client->getIdClient()
    );
    $visibleById = [];
    foreach ($visibleVenues as $visibleVenue) {
      $visibleById[$visibleVenue->getIdVenue()] = $visibleVenue;
    }

    $locationByVenue = [];
    $locationCache = [];
    foreach (array_keys($visitCountByVenue) as $id) {
      $venue = $visibleById[$id] ?? null;
      if ($venue === null) {
        continue;
      }
      $frequentVenues[] = $venue;

      $locId = $venue->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationRepo->findById($locId);
        }
        $locationByVenue[$venue->getIdVenue()] = $locationCache[$locId];
      }
    }

    $hasValidLocation = $client->getLocationId() !== null
      && $this->locationRepo->findById($client->getLocationId()) !== null;

    $hasLocation = $hasValidLocation;

    require_once __DIR__ . '/../View/Client/Dashboard.php';
  }

  // =========================================================
  // RECOMENDACIONES (recomendados + locales cerca de ti)
  // =========================================================
  public function recommendations(): void
  {
    require_role('client');

    $client = $_SESSION['user'];

    $recommendations = $this->filterBlockedVenues(
      $this->historyService->recommendForUser(
        (int) $client->getIdRol(),
        (int) $client->getLocationId(), // puede ser 0 → null equivalente
        5
      ),
      (int) $client->getIdClient()
    );

    $hasValidLocation = $client->getLocationId() !== null
      && $this->locationRepo->findById($client->getLocationId()) !== null;

    $nearbyVenues = $hasValidLocation
      ? $this->filterBlockedVenues(
          $this->historyService->recommendNear($client->getLocationId(), 5),
          (int) $client->getIdClient()
        )
      : [];

    $hasLocation = $hasValidLocation;

    $allVenues = array_merge($recommendations, $nearbyVenues);
    $locationByVenue = [];
    $locationCache = [];
    foreach ($allVenues as $v) {
      $locId = $v->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationRepo->findById($locId);
        }
        $locationByVenue[$v->getIdVenue()] = $locationCache[$locId];
      }
    }

    $clientLocation = $hasValidLocation
      ? $this->locationRepo->findById((int) $client->getLocationId())
      : null;

    $distanceLabelByVenue = [];
    if ($clientLocation !== null) {
      foreach ($allVenues as $v) {
        $loc = $locationByVenue[$v->getIdVenue()] ?? null;
        if ($loc !== null) {
          $label = GeoService::distanceLabel($clientLocation, $loc);
          if ($label !== null) {
            $distanceLabelByVenue[$v->getIdVenue()] = $label;
          }
        }
      }
    }

    require_once __DIR__ . '/../View/Client/Recommendations.php';
  }

  // =========================================================
  // FAVORITOS (locales que el cliente marcó con ❤️)
  // =========================================================
  public function favorites(): void
  {
    require_role('client');

    $client = $_SESSION['user'];

    $favoriteIds = $this->historyService->favoriteVenueIdsByRole((int) $client->getIdRol());
    $favoriteVenues = $this->filterBlockedVenues(
      $this->venueRepo->findActiveByIds($favoriteIds),
      (int) $client->getIdClient()
    );

    $locationByVenue = [];
    $ratingsByVenue = [];
    $locationCache = [];
    foreach ($favoriteVenues as $v) {
      $locId = $v->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationRepo->findById($locId);
        }
        $locationByVenue[$v->getIdVenue()] = $locationCache[$locId];
      }

      $avg = $this->venueRatingService->getAverage($v->getIdVenue());
      if ($avg !== null) {
        $ratingsByVenue[$v->getIdVenue()] = round($avg, 1);
      }
    }

    require_once __DIR__ . '/../View/Client/Favorites.php';
  }

  private function filterBlockedVenues(array $venues, int $clientId): array
  {
    return array_values(array_filter(
      $venues,
      fn($venue): bool => !$this->blockedClientRepo->isBlocked($venue->getIdOwner(), $clientId)
    ));
  }
}