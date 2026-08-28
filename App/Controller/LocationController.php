<?php

require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

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
    $locations = $this->locationRepo->findAll();

    require_once __DIR__ . '/../View/Location/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO
  // =========================================================
  public function showForm(): void
  {
    require_once __DIR__ . '/../View/Location/Form.php';
  }

  // =========================================================
  // CREAR UBICACIÓN
  // =========================================================
  public function create(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $address = trim($_POST['address'] ?? '') ?: null;

    try {

      $this->locationService->validateAndCreate($province, $canton, $district, $address);

      header('Location: ../../Public/index.php?controller=location&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Location/Form.php';
    }
  }
}
