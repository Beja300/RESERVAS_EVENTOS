<?php

require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class VenueController
{
  private VenueService $venueService;
  private OwnerService $ownerService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->venueService = new VenueService($connection);
    $this->ownerService = new OwnerService($connection);
  }

  // =========================================================
  // CATÁLOGO PÚBLICO (locales activos)
  // =========================================================
  public function catalog(): void
  {
    $venues = $this->venueService->findActive();

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
      header('Location: ../Controller/VenueController.php?action=catalog');
      exit;
    }

    require_once __DIR__ . '/../View/Venue/Detail.php';
  }

  // =========================================================
  // PANEL DEL OWNER (sus propios locales)
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $venues = $this->venueService->findActive();

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
    $locationDetail = trim($_POST['locationDetail'] ?? '') ?: null;
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $image = trim($_POST['image'] ?? '') ?: null;

    try {

      $this->venueService->validateAndCreate(
        $owner->getIdOwner(),
        $province,
        $canton,
        $district,
        $locationDetail,
        $name,
        $type,
        $capacity,
        $image
      );

      header('Location: ../Controller/VenueController.php?action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
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
        $image,
        $active
      );

      header('Location: ../Controller/VenueController.php?action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Venue/Form.php';
    }
  }

  // =========================================================
  // GUARDIA: SOLO OWNER AUTENTICADO
  // =========================================================
  private function requireOwner(): void
  {
    if (($_SESSION['type'] ?? null) !== 'owner') {
      header('Location: ../View/Auth/Login.php');
      exit;
    }
  }
}
