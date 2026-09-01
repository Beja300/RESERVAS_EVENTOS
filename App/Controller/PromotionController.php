<?php

require_once __DIR__ . '/../Service/PromotionService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class PromotionController
{
  private PromotionService $promotionService;
  private OwnerService $ownerService;
  private ServiceService $serviceService;
  private VenueRepository $venueRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->promotionService = new PromotionService($connection);
    $this->ownerService = new OwnerService($connection);
    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->venueRepo = new VenueRepository($connection);
  }

  // =========================================================
  // LISTA DE PROMOCIONES DE UN LOCAL (del owner logueado)
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $promotions = $this->promotionService->getByVenue($idVenue);
      $servicesByPromotion = [];
      foreach ($promotions as $promo) {
        $servicesByPromotion[$promo->getIdPromotion()] = $this->promotionService->getServices($promo->getIdPromotion());
      }

      $availableServices = $this->serviceService->findByLocal($idVenue);

      require_once __DIR__ . '/../View/Promotion/List.php';
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $promotions = [];
      $servicesByPromotion = [];
      $availableServices = [];

      require_once __DIR__ . '/../View/Promotion/List.php';
    }
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE CREACIÓN
  // =========================================================
  public function showForm(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

    $promotion = null;
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    require_once __DIR__ . '/../View/Promotion/Form.php';
  }

  // =========================================================
  // CREAR PROMOCIÓN
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
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $label = trim($_POST['label'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $startDate = trim($_POST['startDate'] ?? '') ?: null;
    $endDate = trim($_POST['endDate'] ?? '') ?: null;
    $minServices = (int) ($_POST['minServices'] ?? 1);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $this->promotionService->create(
        $idVenue,
        $label,
        $description,
        $startDate,
        $endDate,
        $minServices
      );

      header('Location: ../../Public/index.php?controller=promotion&action=list&venueId=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $owner = $_SESSION['user'];
      $venues = $this->venueRepo->findByOwner($owner->getIdOwner());
      $promotion = null;

      require_once __DIR__ . '/../View/Promotion/Form.php';
    }
  }

  // =========================================================
  // AGREGAR SERVICIO A UNA PROMOCIÓN
  // =========================================================
  public function addService(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->list();
      return;
    }

    $owner = $_SESSION['user'];
    $idPromotion = (int) ($_POST['promotionId'] ?? 0);
    $idService = (int) ($_POST['serviceId'] ?? 0);
    $idVenue = (int) ($_POST['venueId'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $this->promotionService->addService($idPromotion, $idService);

      header('Location: ../../Public/index.php?controller=promotion&action=list&venueId=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=promotion&action=list&venueId=' . $idVenue);
      exit;
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
