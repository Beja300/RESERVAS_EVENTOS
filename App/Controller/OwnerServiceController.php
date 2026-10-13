<?php

require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerServiceController
{
  private ServiceService $serviceService;
  private OwnerService $ownerService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->serviceService = new ServiceService(new ServiceRepository($connection), new ServiceHistoryRepository($connection));
    $this->ownerService = new OwnerService($connection);
  }

  // =========================================================
  // PANEL DEL OWNER (servicios de un local suyo)
  // =========================================================
  public function list(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $services = [];
    $error = '';

    try {
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);
      $services = $this->serviceService->findByLocal($idVenue);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
    }

    require_once __DIR__ . '/../View/Service/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (crear/editar)
  // =========================================================
  public function showForm(): void
  {
    require_role('owner');

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
    require_role('owner');

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

      $this->serviceService->validateAndCreate($idVenue, $name, $price, $type);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Servicio creado correctamente.'],
        'service',
        'list',
        ['venueId' => $idVenue]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      $service = null;

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Service/Form.php';
    } catch (\Throwable $e) {
      $error = 'Error al guardar el servicio: ' . $e->getMessage();
      $service = null;

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 500);
      }

      require_once __DIR__ . '/../View/Service/Form.php';
    }
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(): void
  {
    require_role('owner');

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

      $this->serviceService->validateAndUpdate($service, $name, $type, $price, $active);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Servicio actualizado correctamente.'],
        'service',
        'list',
        ['venueId' => $service->getIdLocal()]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Service/Form.php';
    }
  }
}