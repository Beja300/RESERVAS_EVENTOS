<?php

require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ServiceController
{
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private OwnerRepository $ownerRepo;
  private VenueRepository $venueRepo;
  private AdminRepository $adminRepo;
  private NotificationService $notificationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->ownerService = new OwnerService($connection);
    $this->ownerRepo = new OwnerRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->adminRepo = new AdminRepository($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
  }

  // =========================================================
  // PANEL DEL OWNER (servicios de un local suyo)
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $services = $this->serviceService->findByLocal($idVenue);

      require_once __DIR__ . '/../View/Service/List.php';
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Service/List.php';
    }
  }

  // =========================================================
  // MOSTRAR FORMULARIO (crear/editar)
  // =========================================================
  public function showForm(): void
  {
    session_start();
    $this->requireOwner();

    $idService = (int) ($_GET['id'] ?? 0);
    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $service = $idService > 0 ? $this->serviceService->findById($idService) : null;

    require_once __DIR__ . '/../View/Service/Form.php';
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
    $idVenue = (int) ($_POST['venueId'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $price = (float) ($_POST['price'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $this->serviceService->validateAndCreate(
        $idVenue,
        $name,
        $price,
        $type
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio creado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=service&action=list&venueId=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $service = null;

      require_once __DIR__ . '/../View/Service/Form.php';
    } catch (\Throwable $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => 'Error al guardar el servicio: ' . $e->getMessage()], 500);
      }

      $error = 'Error al guardar el servicio: ' . $e->getMessage();
      $service = null;
      require_once __DIR__ . '/../View/Service/Form.php';
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
    $idService = (int) ($_POST['idService'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $price = (float) ($_POST['price'] ?? 0);
    $active = isset($_POST['active']);

    try {

      $service = $this->serviceService->findById($idService);

      if ($service === null) {
        throw new BusinessRuleException("El servicio que intentas editar no existe.");
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $service->getIdLocal());

      $this->serviceService->validateAndUpdate(
        $service,
        $name,
        $type,
        $price,
        $active
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio actualizado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=service&action=list&venueId=' . $service->getIdLocal());
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Service/Form.php';
    }
  }

  // =========================================================
  // PANEL DEL ADMIN (servicios pendientes de aprobación +
  // historial de aprobados/rechazados)
  // =========================================================
  public function pending(): void
  {
    session_start();
    $this->requireAdmin();

    $services = $this->serviceService->findPending();
    $history  = $this->serviceService->findHistory();

    require_once __DIR__ . '/../View/Admin/PendingServices.php';
  }

  // =========================================================
  // APROBAR (registra qué administrador lo aprobó)
  // =========================================================
  public function approve(): void
  {
    session_start();
    $this->requireAdmin();

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $admin = $_SESSION['user'] ?? null;
    $approvedByRoleId = $admin instanceof Admin && method_exists($admin, 'getIdRol') ? $admin->getIdRol() : 0;

    try {

      $this->serviceService->approve($idService, $approvedByRoleId > 0 ? $approvedByRoleId : null);

      $historyService = new HistoryService(DataBase::getConnection());
      $historyService->logAction($approvedByRoleId, 'APPROVE', 'Service', $idService);

      $this->notifyOwnerOfServiceReview($idService, true);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio aprobado.']);
      }
    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }
    }

    header('Location: ../../Public/index.php?controller=service&action=pending');
    exit;
  }

  // =========================================================
  // RECHAZAR
  // =========================================================
  public function reject(): void
  {
    session_start();
    $this->requireAdmin();

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->serviceService->reject($idService);

      $this->notifyOwnerOfServiceReview($idService, false);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio rechazado.']);
      }
    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }
    }

    header('Location: ../../Public/index.php?controller=service&action=pending');
    exit;
  }

  // =========================================================
  // DETALLE DE UN SERVICIO (Admin)
  // Muestra toda la info del servicio, del propietario que lo
  // solicita y del local al que pertenece, en cualquier estado.
  // =========================================================
  public function detail(): void
  {
    session_start();
    $this->requireAdmin();

    $idService = (int) ($_GET['id'] ?? 0);
    $service = $this->serviceService->findById($idService);

    if ($service === null) {
      header('Location: ../../Public/index.php?controller=service&action=pending');
      exit;
    }

    $venue = $this->venueRepo->findById($service->getIdLocal());
    $owner = $venue !== null ? $this->ownerRepo->findByOwnerPk($venue->getIdOwner()) : null;
    $approvedBy = $service->getApprovedBy() !== null ? $this->adminRepo->findByRoleId($service->getApprovedBy()) : null;

    require_once __DIR__ . '/../View/Service/AdminDetail.php';
  }

  // =========================================================
  // NOTIFICAR AL PROPIETARIO LA REVISIÓN DE UN SERVICIO SUYO
  // =========================================================
  private function notifyOwnerOfServiceReview(int $idService, bool $approved): void
  {
    $service = $this->serviceService->findById($idService);

    if ($service === null) {
      return;
    }

    $venue = $this->venueRepo->findById($service->getIdLocal());

    if ($venue === null) {
      return;
    }

    $owner = $this->ownerRepo->findByOwnerPk($venue->getIdOwner());

    if ($owner === null) {
      return;
    }

    $this->notificationService->notifyServiceReviewed(
      (int) $owner->getIdRol(),
      $approved,
      $this->notificationService->serviceListUrl((int) $venue->getIdVenue())
    );
  }

  // =========================================================
  // GUARDIAS
  // =========================================================
  private function requireOwner(): void
  {
    if (($_SESSION['type'] ?? null) !== 'owner') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }

  private function requireAdmin(): void
  {
    if (($_SESSION['type'] ?? null) !== 'admin') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
