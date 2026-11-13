<?php

require_once __DIR__ . '/../Service/VenueService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/ImageStorageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerVenueController
{
  private VenueService $venueService;
  private OwnerService $ownerService;
  private LocationService $locationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->venueService = new VenueService($connection);
    $this->ownerService = new OwnerService($connection);
    $this->locationService = new LocationService(new LocationRepository($connection));
  }

  // =========================================================
  // PANEL DEL OWNER (sus propios locales)
  // =========================================================
  public function list(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $venues = $this->venueService->findByOwner($owner->getIdOwner());

    require_once __DIR__ . '/../View/Venue/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (crear/editar)
  // =========================================================
  public function showForm(): void
  {
    require_role('owner');

    $idVenue = (int) ($_GET['id'] ?? 0);
    $venue = $idVenue > 0 ? $this->venueService->findById($idVenue) : null;
    $location = null;
    if ($venue !== null && $venue->getIdLocation() > 0) {
      $location = $this->locationService->findById($venue->getIdLocation());
    }

    require_once __DIR__ . '/../View/Venue/Form.php';
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

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $approximate = trim($_POST['approximate'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $locationType = strtolower(trim($_POST['locationType'] ?? 'exacta')) === 'aproximada' ? 'aproximada' : 'exacta';
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0.0;

    try {
      $this->requireCoordinates($latitude, $longitude);

      $image = $this->resolveVenueImage($owner->getIdOwner(), '');

      if ($image === '') {
        throw new BusinessRuleException("Debes subir al menos una foto del local.");
      }

      $this->venueService->validateAndCreate(
        $owner->getIdOwner(),
        $province,
        $canton,
        $district,
        $town,
        $description,
        $name,
        $type,
        $capacity,
        $price,
        $image,
        $latitude,
        $longitude,
        $locationType,
        $approximate
      );

      respond_or_redirect(
        ['ok' => true, 'message' => 'Local creado correctamente.'],
        'venue',
        'list'
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $venue = null;
      require_once __DIR__ . '/../View/Venue/Form.php';
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
    $idVenue = (int) ($_POST['idVenue'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0.0;
    $active = isset($_POST['active']);

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $approximate = trim($_POST['approximate'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $locationType = strtolower(trim($_POST['locationType'] ?? 'exacta')) === 'aproximada' ? 'aproximada' : 'exacta';

    $venue = null;

    try {
      $this->requireCoordinates($latitude, $longitude);

      $venue = $this->venueService->findById($idVenue);

      if ($venue === null) {
        throw new BusinessRuleException("El local que intentas editar no existe.");
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $image = $this->resolveVenueImage($owner->getIdOwner(), $venue->getImageVenue());

      if ($locationType === 'aproximada') {
        $idLocation = $this->locationService->saveApproximate(
          $venue->getIdLocation() !== null && $venue->getIdLocation() > 0 ? $venue->getIdLocation() : null,
          (string) $town,
          (string) $approximate,
          $description
        );
      } else {
        if ($province === '' || $canton === '' || $district === '') {
          throw new BusinessRuleException('Debes completar provincia, cantón y distrito.');
        }

        $idLocation = $this->locationService->findOrCreateByParts($province, $canton, $district, $town, $description, $latitude, $longitude);

        if ($idLocation === null) {
          throw new BusinessRuleException('No se pudo guardar la ubicación exacta.');
        }
      }

      $this->venueService->validateAndUpdate(
        $venue,
        $name,
        $type,
        $capacity,
        $price,
        $image,
        $active,
        $idLocation
      );

      respond_or_redirect(
        ['ok' => true, 'message' => 'Local actualizado correctamente.'],
        'venue',
        'list'
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $location = null;
      if ($venue !== null && $venue->getIdLocation() > 0) {
        $location = $this->locationService->findById($venue->getIdLocation());
      }

      require_once __DIR__ . '/../View/Venue/Form.php';
    }
  }

  // =========================================================
  // FOTO DEL LOCAL: prioriza el archivo subido, si no conserva la actual.
  // =========================================================
  private function resolveVenueImage(int $ownerId, string $current): string
  {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      return ImageStorageService::store(
        $_FILES['image'],
        'venues',
        'venue_' . $ownerId
      );
    }

    return $current;
  }

  // =========================================================
  // COORDENADAS OBLIGATORIAS: el local debe marcarse en el mapa.
  // =========================================================
  private function requireCoordinates(?float $latitude, ?float $longitude): void
  {
    if ($latitude === null || $longitude === null) {
      throw new BusinessRuleException("Debes marcar la ubicación exacta del local en el mapa.");
    }
  }
}