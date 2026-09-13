<?php

require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Admin.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminServiceController
{
  private ServiceService $serviceService;
  private HistoryService $historyService;
  private NotificationService $notificationService;
  private OwnerRepository $ownerRepo;
  private VenueRepository $venueRepo;
  private AdminRepository $adminRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->historyService = new HistoryService($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
    $this->ownerRepo = new OwnerRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->adminRepo = new AdminRepository($connection);
  }

  // =========================================================
  // PANEL DEL ADMIN (servicios pendientes de aprobación +
  // historial de aprobados/rechazados)
  // =========================================================
  public function pending(): void
  {
    require_role('admin');

    $services = $this->serviceService->findPending();
    $history  = $this->serviceService->findHistory();

    require_once __DIR__ . '/../View/Admin/PendingServices.php';
  }

  // =========================================================
  // APROBAR (registra qué administrador lo aprobó)
  // =========================================================
  public function approve(): void
  {
    require_role('admin');

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $admin = $_SESSION['user'] ?? null;
    $approvedByRoleId = $admin instanceof Admin && method_exists($admin, 'getIdRol') ? $admin->getIdRol() : 0;

    try {
      $this->serviceService->approve($idService, $approvedByRoleId > 0 ? $approvedByRoleId : null);

      $this->historyService->logAction($approvedByRoleId, 'APPROVE', 'Service', $idService);

      $this->notifyOwnerOfServiceReview($idService, true);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio aprobado.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }
    }

    redirect_to('service', 'pending');
  }

  // =========================================================
  // RECHAZAR
  // =========================================================
  public function reject(): void
  {
    require_role('admin');

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $admin = $_SESSION['user'] ?? null;
    $approvedByRoleId = $admin instanceof Admin && method_exists($admin, 'getIdRol') ? $admin->getIdRol() : 0;

    try {
      $this->serviceService->reject($idService, $approvedByRoleId > 0 ? $approvedByRoleId : null);

      $this->historyService->logAction($approvedByRoleId, 'REJECT', 'Service', $idService);

      $this->notifyOwnerOfServiceReview($idService, false);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio rechazado.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }
    }

    redirect_to('service', 'pending');
  }

  // =========================================================
  // DETALLE DE UN SERVICIO (Admin)
  // Muestra toda la info del servicio, del propietario que lo
  // solicita y del local al que pertenece, en cualquier estado.
  // =========================================================
  public function detail(): void
  {
    require_role('admin');

    $idService = (int) ($_GET['id'] ?? 0);
    $service = $this->serviceService->findById($idService);

    if ($service === null) {
      redirect_to('service', 'pending');
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
}