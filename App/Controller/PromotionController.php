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

      header('Location: ' . base_url('promotion', 'list', ['venueId' => $idVenue]));
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
  // MOSTRAR FORMULARIO DE EDICIÓN
  // =========================================================
  public function edit(): void
  {
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $idPromotion = (int) ($_GET['id'] ?? 0);

    try {

      $promotion = $this->promotionService->find($idPromotion);

      if ($promotion === null) {
        throw new BusinessRuleException('La promoción no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $promotion->getIdVenue());

      $idVenue = $promotion->getIdVenue();
      $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

      require_once __DIR__ . '/../View/Promotion/Form.php';
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $this->list();
    }
  }

  // =========================================================
  // ACTUALIZAR PROMOCIÓN
  // =========================================================
  public function update(): void
  {
    $this->requireOwner();
    require_post();

    $owner = $_SESSION['user'];
    $idPromotion = (int) ($_POST['id'] ?? 0);
    $label = trim($_POST['label'] ?? '');
    $description = trim($_POST['description'] ?? '') ?: null;
    $startDate = trim($_POST['startDate'] ?? '') ?: null;
    $endDate = trim($_POST['endDate'] ?? '') ?: null;
    $minServices = (int) ($_POST['minServices'] ?? 1);
    $isActive = isset($_POST['active']);

    try {

      $promotion = $this->promotionService->find($idPromotion);

      if ($promotion === null) {
        throw new BusinessRuleException('La promoción no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $promotion->getIdVenue());

      $this->promotionService->update(
        $idPromotion,
        $label,
        $description,
        $startDate,
        $endDate,
        $minServices,
        $isActive
      );

      redirect_to('promotion', 'list', ['venueId' => $promotion->getIdVenue(), 'updated' => 1]);
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $promotion = $this->promotionService->find($idPromotion);
      $idVenue = $promotion !== null ? $promotion->getIdVenue() : 0;
      $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

      require_once __DIR__ . '/../View/Promotion/Form.php';
    }
  }

  // =========================================================
  // AGREGAR SERVICIO A UNA PROMOCIÓN
  // =========================================================
  public function addService(): void
  {
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

      header('Location: ' . base_url('promotion', 'list', ['venueId' => $idVenue]));
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ' . base_url('promotion', 'list', ['venueId' => $idVenue]));
      exit;
    }
  }

  // =========================================================
  // GUARDIA: SOLO OWNER AUTENTICADO
  // =========================================================
  private function requireOwner(): void
  {
    require_role('owner');
  }
}
