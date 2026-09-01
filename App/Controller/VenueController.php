<?php

require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/VenueRatingService.php';
require_once __DIR__ . '/../Service/ServiceRatingService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
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
  private OwnerRepository $ownerRepository;

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
    $this->ownerRepository = new OwnerRepository($connection);
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

    session_start();
    $loggedRolePk = isset($_SESSION['user']) ? (int) $_SESSION['user']->getIdRol() : 0;

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
  // INFORMACIÓN PÚBLICA DE UN PROPIETARIO
  // (accesible desde el detalle del local que administra)
  // =========================================================
  public function showOwner(): void
  {
    $idOwner = (int) ($_GET['ownerId'] ?? 0);
    $returnVenueId = (int) ($_GET['venueId'] ?? 0);
    $owner = $this->ownerRepository->findByOwnerPk($idOwner);

    if ($owner === null) {
      header('Location: ../../Public/index.php?controller=venue&action=catalog');
      exit;
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

      if (is_ajax()) {
        $avg = $this->serviceRatingService->getAverage($idService);
        respond_json([
          'ok' => true,
          'message' => 'Calificación publicada.',
          'serviceId' => $idService,
          'avg' => $avg !== null ? round($avg, 1) : 0,
        ]);
      }

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

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

      if (is_ajax()) {
        respond_json([
          'ok' => true,
          'message' => 'Comentario publicado.',
          'avg' => round((float) ($this->venueRatingService->getAverage($idVenue) ?? 0), 1),
          'html' => $this->venueCommentsHtml($idVenue),
        ]);
      }

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    }
  }

  // =========================================================
  // EDITAR UN COMENTARIO ESPECÍFICO (solo su autor)
  // =========================================================
  public function updateComment(): void
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
          'message' => 'Comentario actualizado.',
          'avg' => round((float) ($this->venueRatingService->getAverage($idVenue) ?? 0), 1),
          'html' => $this->venueCommentsHtml($idVenue),
        ]);
      }

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=venue&action=detail&id=' . $idVenue);
      exit;
    }
  }

  // =========================================================
  // HTML DEL CONTENEDOR DE COMENTARIOS (refresco AJAX)
  // =========================================================
  private function venueCommentsHtml(int $idVenue): string
  {
    return render_partial(
      __DIR__ . '/../View/Venue/_venueComments.php',
      ['venueComments' => $this->venueRatingService->getPublicComments($idVenue)]
    );
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
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0.0;
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
        $price,
        $image
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Local creado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=venue&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

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
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0.0;
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
        $price,
        $image,
        $active
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Local actualizado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=venue&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

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
