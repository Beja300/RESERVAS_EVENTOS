<?php

require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ServiceController
{
  private ServiceService $serviceService;
  private OwnerService $ownerService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->serviceService = new ServiceService(new ServiceRepository($connection));
    $this->ownerService = new OwnerService($connection);
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

      header('Location: ../../Public/index.php?controller=service&action=list&venueId=' . $idVenue);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
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

      header('Location: ../../Public/index.php?controller=service&action=list&venueId=' . $service->getIdLocal());
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Service/Form.php';
    }
  }

  // =========================================================
  // PANEL DEL ADMIN (servicios pendientes de aprobación)
  // =========================================================
  public function pending(): void
  {
    session_start();
    $this->requireAdmin();

    $services = $this->serviceService->findPending();

    require_once __DIR__ . '/../View/Admin/PendingServices.php';
  }

  // =========================================================
  // APROBAR
  // =========================================================
  public function approve(): void
  {
    session_start();
    $this->requireAdmin();

    $idService = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    $this->serviceService->approve($idService);

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

    $this->serviceService->reject($idService);

    header('Location: ../../Public/index.php?controller=service&action=pending');
    exit;
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
