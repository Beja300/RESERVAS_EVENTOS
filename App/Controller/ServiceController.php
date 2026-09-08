<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../Model/Admin.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ServiceController extends BaseController
{
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private VenueService $venueService;
  private AdminService $adminService;
  private NotificationService $notificationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->ownerService = new OwnerService($connection);
    $this->venueService = new VenueService($connection);
    $this->adminService = new AdminService();
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
  }

  // =========================================================
  // PANEL DEL OWNER (servicios de un local suyo)
  // =========================================================
  public function list(): void
  {
    $this->requireOwner();

    $owner = $this->currentUser();
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
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $owner = $this->currentUser();
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
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $owner = $this->currentUser();
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
    $this->requireAdmin();

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $admin = $this->currentUser();
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
    $this->requireAdmin();

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $admin = $this->currentUser();
    $approvedByRoleId = $admin instanceof Admin && method_exists($admin, 'getIdRol') ? $admin->getIdRol() : 0;

    try {

      $this->serviceService->reject($idService, $approvedByRoleId > 0 ? $approvedByRoleId : null);

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
    $this->requireAdmin();

    $idService = (int) ($_GET['id'] ?? 0);
    $service = $this->serviceService->findById($idService);

    if ($service === null) {
      $this->redirect('service', 'pending');
    }

    $venue = $this->venueService->findById($service->getIdLocal());
    $owner = $venue !== null ? $this->ownerService->getOwner($venue->getIdOwner()) : null;
    $approvedBy = $service->getApprovedBy() !== null ? $this->adminService->findUserByRoleId('admin', $service->getApprovedBy()) : null;

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

    $venue = $this->venueService->findById($service->getIdLocal());

    if ($venue === null) {
      return;
    }

    $owner = $this->ownerService->getOwner($venue->getIdOwner());

    if ($owner === null) {
      return;
    }

    $this->notificationService->notifyServiceReviewed(
      (int) $owner->getIdRol(),
      $approved,
      $this->notificationService->serviceListUrl((int) $venue->getIdVenue())
    );
  }
}