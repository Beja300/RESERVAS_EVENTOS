<?php

require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/VenueRatingService.php';
require_once __DIR__ . '/../Service/ServiceRatingService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/PromotionService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/OrderingService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class VenueCatalogController
{
  private VenueService $venueService;
  private VenueRatingService $venueRatingService;
  private ServiceRatingService $serviceRatingService;
  private ServiceService $serviceService;
  private PromotionService $promotionService;
  private HistoryService $historyService;
  private OwnerRepository $ownerRepository;
  private LocationService $locationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->venueService = new VenueService($connection);
    $this->venueRatingService = new VenueRatingService($connection);
    $this->serviceRatingService = new ServiceRatingService($connection);
    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->promotionService = new PromotionService($connection);
    $this->historyService = new HistoryService($connection);
    $this->ownerRepository = new OwnerRepository($connection);
    $this->locationService = new LocationService(new LocationRepository($connection));
  }

  // =========================================================
  // CATÁLOGO PÚBLICO (locales activos, con filtros y orden)
  // =========================================================
  public function catalog(): void
  {
    $filters = [
      'province' => trim($_GET['province'] ?? ''),
      'canton'   => trim($_GET['canton'] ?? ''),
      'district' => trim($_GET['district'] ?? ''),
      'type'     => trim($_GET['type'] ?? ''),
      'q'        => trim($_GET['q'] ?? ''),
    ];

    $hasFilters = implode('', $filters) !== '';
    $venues = $this->venueService->findByFilters($filters);

    if ($hasFilters && ($_SESSION['type'] ?? null) === 'client') {
      $this->historyService->logVenueSearch(
        (int) $_SESSION['user']->getIdRol(),
        $filters,
        $filters['type'] ?: null
      );
    }

    $ratingsByVenue = [];
    $promosByVenue = [];
    $locationByVenue = [];

    $locationCache = [];
    foreach ($venues as $v) {
      $avg = $this->venueRatingService->getAverage($v->getIdVenue());
      if ($avg !== null) {
        $ratingsByVenue[$v->getIdVenue()] = round($avg, 1);
      }

      $promos = $this->promotionService->getActiveByVenue($v->getIdVenue());
      if (!empty($promos)) {
        $promosByVenue[$v->getIdVenue()] = array_map(fn($p) => $p->getLabel(), $promos);
      }

      $locId = $v->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationService->findById($locId);
        }
        $locationByVenue[$v->getIdVenue()] = $locationCache[$locId];
      }
    }

    // Ubicación válida del cliente (si la tiene) para ordenar por cercanía.
    $clientLocation = null;
    if (($_SESSION['type'] ?? null) === 'client') {
      $clientLocationId = (int) $_SESSION['user']->getLocationId();
      if ($clientLocationId > 0) {
        $clientLocation = $this->locationService->findById($clientLocationId);
      }
    }

    // Orden: más cercanos → más populares (rating) → el resto.
    $venues = $this->sortCatalogVenues($venues, $clientLocation, $ratingsByVenue, $locationByVenue);

    require_once __DIR__ . '/../View/Venue/Catalog.php';
  }

  // =========================================================
  // ORDENAR CATÁLOGO POR CERCANÍA Y POPULARIDAD (rating)
  // =========================================================
  private function sortCatalogVenues(
    array $venues,
    ?Location $clientLocation,
    array $ratingsByVenue,
    array $locationByVenue
  ): array
  {
    $nearTier = static function (Venue $v) use ($clientLocation, $locationByVenue): int {
      if ($clientLocation === null) {
        return 3;
      }

      $loc = $locationByVenue[$v->getIdVenue()] ?? null;
      if ($loc === null) {
        return 3;
      }

      if ($loc->getProvinceLocation() !== $clientLocation->getProvinceLocation()) {
        return 3;
      }

      if ($loc->getCantonLocation() === $clientLocation->getCantonLocation()) {
        if ($loc->getDistrictLocation() === $clientLocation->getDistrictLocation()) {
          return 0;
        }
        return 1;
      }

      return 2;
    };

    usort($venues, static function (Venue $a, Venue $b) use ($nearTier, $ratingsByVenue, $locationByVenue): int {
      $tierDiff = $nearTier($a) <=> $nearTier($b);
      if ($tierDiff !== 0) {
        return $tierDiff;
      }

      $ratingA = $ratingsByVenue[$a->getIdVenue()] ?? -1.0;
      $ratingB = $ratingsByVenue[$b->getIdVenue()] ?? -1.0;
      if ($ratingA !== $ratingB) {
        return $ratingB <=> $ratingA;
      }

      $locationA = $locationByVenue[$a->getIdVenue()] ?? null;
      $locationB = $locationByVenue[$b->getIdVenue()] ?? null;
      if ($locationA !== null && $locationB !== null) {
        $locationDiff = OrderingService::locations($locationA, $locationB);
        if ($locationDiff !== 0) {
          return $locationDiff;
        }
      }

      return OrderingService::strings($a->getNameVenue(), $b->getNameVenue());
    });

    return $venues;
  }

  // =========================================================
  // DETALLE DE UN LOCAL
  // =========================================================
  public function detail(): void
  {
    $idVenue = (int) ($_GET['id'] ?? 0);
    $venue = $this->venueService->findById($idVenue);

    if ($venue === null) {
      redirect_to('venue', 'catalog');
    }

    $owner = $this->ownerRepository->findByOwnerPk($venue->getIdOwner());

    $avgRating = $this->venueRatingService->getAverage($idVenue);
    $promotions = $this->promotionService->getActiveByVenue($idVenue);

    $services = $this->serviceService->findAvailableByLocal($idVenue);
    $ratingByService = [];
    foreach ($services as $s) {
      $avg = $this->serviceRatingService->getAverage($s->getIdService());
      if ($avg !== null) {
        $ratingByService[$s->getIdService()] = round($avg, 1);
      }
    }

    $loggedRolePk = isset($_SESSION['user']) ? (int) $_SESSION['user']->getIdRol() : 0;

    if ($loggedRolePk > 0 && ($_SESSION['type'] ?? null) === 'client') {
      $this->historyService->logVenueView($loggedRolePk, $idVenue);
    }

    $myRatingByService = [];
    if ($loggedRolePk > 0) {
      foreach ($services as $s) {
        $mine = $this->serviceRatingService->getByServiceAndRole($s->getIdService(), $loggedRolePk);
        if ($mine !== null) {
          $myRatingByService[$s->getIdService()] = $mine;
        }
      }
    }

    $venueComments = $this->venueRatingService->getPublicComments($idVenue);
    $serviceComments = [];
    foreach ($services as $s) {
      $serviceComments[$s->getIdService()] = $this->serviceRatingService->getPublicComments($s->getIdService());
    }

    // Reserva (opinión) propia del cliente sobre ESTE local.
    $myVenueRating = $loggedRolePk > 0
      ? $this->venueRatingService->getByVenueAndRole($idVenue, $loggedRolePk)
      : null;

    $location = null;
    if ($venue->getIdLocation() > 0) {
      $location = $this->locationService->findById($venue->getIdLocation());
    }

    require_once __DIR__ . '/../View/Venue/Detail.php';
  }

  // =========================================================
  // INFORMACIÓN PÚBLICA DE UN PROPIETARIO
  // =========================================================
  public function showOwner(): void
  {
    $idOwner = (int) ($_GET['ownerId'] ?? 0);
    $returnVenueId = (int) ($_GET['venueId'] ?? 0);
    $owner = $this->ownerRepository->findByOwnerPk($idOwner);

    if ($owner === null) {
      redirect_to('venue', 'catalog');
    }

    $ownerVenues = [];
    foreach ($this->venueService->findByOwner($idOwner) as $v) {
      if ($v->getIsActive()) {
        $ownerVenues[] = $v;
      }
    }

    require_once __DIR__ . '/../View/Owner/PublicProfile.php';
  }

  // =========================================================
  // CALIFICAR UN SERVICIO (cualquier usuario autenticado)
  // =========================================================
  public function rateService(): void
  {
    require_login();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('venue', 'catalog');
    }

    $idService = (int) ($_POST['serviceId'] ?? 0);
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $stars = (int) ($_POST['stars'] ?? 0);
    $comment = trim($_POST['comment'] ?? '') ?: null;
    $rolePk = (int) ($_SESSION['user']->getIdRol() ?? 0);

    try {
      if ($this->serviceService->findById($idService) === null) {
        throw new BusinessRuleException('El servicio no existe.');
      }

      $this->serviceRatingService->rate($idService, $rolePk, $stars, $comment);

      if (is_ajax()) {
        $avg = $this->serviceRatingService->getAverage($idService);
        respond_json([
          'ok' => true,
          'message' => 'Calificación publicada.',
          'serviceId' => $idService,
          'avg' => $avg !== null ? round($avg, 1) : 0,
        ]);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    }
  }

  // =========================================================
  // CALIFICAR UN LOCAL (cualquier usuario autenticado)
  // =========================================================
  public function rate(): void
  {
    require_login();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $stars = (int) ($_POST['stars'] ?? 0);
    $comment = trim($_POST['comment'] ?? '') ?: null;
    $rolePk = (int) ($_SESSION['user']->getIdRol() ?? 0);

    try {
      if ($this->venueService->findById($idVenue) === null) {
        throw new BusinessRuleException('El local no existe.');
      }

      $commentId = $this->venueRatingService->rate($idVenue, $rolePk, $stars, $comment);

      if (is_ajax()) {
        respond_json([
          'ok' => true,
          'message' => 'Reserva publicada.',
          'commentId' => $commentId,
          'avg' => round((float) ($this->venueRatingService->getAverage($idVenue) ?? 0), 1),
          'html' => venue_comments_html($idVenue),
        ]);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    }
  }

  // =========================================================
  // EDITAR UNA RESERVA ESPECÍFICA (solo su autor)
  // =========================================================
  public function updateComment(): void
  {
    require_login();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $idVenueRating = (int) ($_POST['commentId'] ?? 0);
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $stars = (int) ($_POST['stars'] ?? 0);
    $comment = trim($_POST['comment'] ?? '') ?: null;
    $rolePk = (int) ($_SESSION['user']->getIdRol() ?? 0);

    try {
      $this->venueRatingService->updateComment($idVenueRating, $rolePk, $stars, $comment);

      if (is_ajax()) {
        respond_json([
          'ok' => true,
          'message' => 'Reserva actualizada.',
          'avg' => round((float) ($this->venueRatingService->getAverage($idVenue) ?? 0), 1),
          'html' => venue_comments_html($idVenue),
        ]);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      redirect_to('venue', 'detail', ['id' => $idVenue]);
    }
  }
}