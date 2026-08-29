<?php

require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/VenueRatingService.php';
require_once __DIR__ . '/../Service/ServiceRatingService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Service/PromotionService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class VenueController
{
  private VenueService $venueService;
  private OwnerService $ownerService;
  private VenueRatingService $venueRatingService;
  private ServiceRatingService $serviceRatingService;
  private ServiceService $serviceService;
  private PromotionService $promotionService;
  private HistoryService $historyService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->venueService = new VenueService($connection);
    $this->ownerService = new OwnerService($connection);
    $this->venueRatingService = new VenueRatingService($connection);
    $this->serviceRatingService = new ServiceRatingService($connection);
    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->promotionService = new PromotionService($connection);
    $this->historyService = new HistoryService($connection);
  }

  // =========================================================
  // CATÁLOGO PÚBLICO (locales activos, con filtros)
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

    if ($hasFilters) {
      session_start();
      if (isset($_SESSION['user'], $_SESSION['type']) && $_SESSION['type'] === 'client') {
        $this->historyService->logVenueSearch(
          (int) $_SESSION['user']->getIdRol(),
          $filters,
          $filters['type'] ?: null
        );
      }
    }

    $ratingsByVenue = [];
    $promosByVenue = [];

    foreach ($venues as $v) {
      $avg = $this->venueRatingService->getAverage($v->getIdVenue());
      if ($avg !== null) {
        $ratingsByVenue[$v->getIdVenue()] = round($avg, 1);
      }

      $promos = $this->promotionService->getActiveByVenue($v->getIdVenue());
      if (!empty($promos)) {
        $names = array_map(fn($p) => $p->getLabel(), $promos);
        $promosByVenue[$v->getIdVenue()] = $names;
      }
    }

    require_once __DIR__ . '/../View/Venue/Catalog.php';
  }

  // =========================================================
  // DETALLE DE UN LOCAL
  // =========================================================
  public function detail(): void
  {
    $idVenue = (int) ($_GET['id'] ?? 0);
    $venue = $this->venueService->findById($idVenue);

    if ($venue === null) {
      header('Location: ../../Public/index.php?controller=venue&action=catalog');
      exit;
    }

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

    session_start();
    $loggedRolePk = isset($_SESSION['user']) ? (int) $_SESSION['user']->getIdRol() : 0;

    $myVenueRating = null;
    if ($loggedRolePk > 0) {
      $myVenueRating = $this->venueRatingService->getByVenueAndRole($idVenue, $loggedRolePk);
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

    require_once __DIR__ . '/../View/Venue/Detail.php';
  }

  // =========================================================
  // CALIFICAR UN SERVICIO (cualquier usuario autenticado)
  // =========================================================
  public function rateService(): void
  {
    session_start();

    if (($_SESSION['type'] ?? null) === null) {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=venue&action=catalog');
      exit;
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

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    }
  }

  // =========================================================
  // CALIFICAR UN LOCAL (cualquier usuario autenticado)
  // =========================================================
  public function rate(): void
  {
    session_start();

    if (($_SESSION['type'] ?? null) === null) {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }

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

      $this->venueRatingService->rate($idVenue, $rolePk, $stars, $comment);

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    }
  }

  // =========================================================
  // PANEL DEL OWNER (sus propios locales)
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $venues = $this->venueService->findByOwner($owner->getIdOwner());

    require_once __DIR__ . '/../View/Venue/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (crear/editar)
  // =========================================================
  public function showForm(): void
  {
    session_start();
    $this->requireOwner();

    $idVenue = (int) ($_GET['id'] ?? 0);
    $venue = $idVenue > 0 ? $this->venueService->findById($idVenue) : null;

    require_once __DIR__ . '/../View/Venue/Form.php';
  }

  // =========================================================
  // GUARDAR (crear)
  // =========================================================
  public function create(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $owner = $_SESSION['user'];

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $image = trim($_POST['image'] ?? '') ?: null;

    try {

      $this->venueService->validateAndCreate(
        $owner->getIdOwner(),
        $province,
        $canton,
        $district,
        $town,
        $description,
        $name,
        $type,
        $capacity,
        $image
      );

      header('Location: ../../Public/index.php?controller=venue&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $venue = null;

      require_once __DIR__ . '/../View/Venue/Form.php';
    }
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_POST['idVenue'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $image = trim($_POST['image'] ?? '') ?: null;
    $active = isset($_POST['active']);

    try {

      $venue = $this->venueService->findById($idVenue);

      if ($venue === null) {
        throw new BusinessRuleException("El local que intentas editar no existe.");
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $this->venueService->validateAndUpdate(
        $venue,
        $name,
        $type,
        $capacity,
        $image,
        $active
      );

      header('Location: ../../Public/index.php?controller=venue&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Venue/Form.php';
    }
  }

  // =========================================================
  // GUARDIA: SOLO OWNER AUTENTICADO
  // =========================================================
  private function requireOwner(): void
  {
    if (($_SESSION['type'] ?? null) !== 'owner') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
