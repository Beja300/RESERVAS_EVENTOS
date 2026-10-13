<?php

require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class LocationController
{
  private LocationService $locationService;
  private LocationRepository $locationRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->locationService = new LocationService(new LocationRepository($connection));
    $this->locationRepo = new LocationRepository($connection);
  }

  // =========================================================
  // LISTAR UBICACIONES
  // =========================================================
  public function list(): void
  {
    require_role('admin');

    $locations = $this->locationRepo->findAll();

    require_once __DIR__ . '/../View/Location/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO
  // =========================================================
  public function showForm(): void
  {
    require_role('admin');

    require_once __DIR__ . '/../View/Location/Form.php';
  }

  // =========================================================
  // CREAR UBICACIÓN
  // =========================================================
  public function create(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;

    try {

      $this->locationService->validateAndCreate($province, $canton, $district, $town, $description);

      redirect_to('location', 'list');
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Location/Form.php';
    }
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE EDICIÓN
  // =========================================================
  public function edit(): void
  {
    require_role('admin');

    $idLocation = (int) ($_GET['id'] ?? 0);
    $location = $this->locationRepo->findById($idLocation);

    if ($location === null) {
      redirect_to('location', 'list');
    }

    require_once __DIR__ . '/../View/Location/Form.php';
  }

  // =========================================================
  // ACTUALIZAR UBICACIÓN
  // =========================================================
  public function update(): void
  {
    require_role('admin');
    require_post();

    $idLocation = (int) ($_POST['id'] ?? 0);
    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;

    try {

      $this->locationService->validateAndUpdate($idLocation, $province, $canton, $district, $town, $description);

      redirect_to('location', 'list', ['updated' => 1]);
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $location = $this->locationRepo->findById($idLocation);

      require_once __DIR__ . '/../View/Location/Form.php';
    }
  }
}
