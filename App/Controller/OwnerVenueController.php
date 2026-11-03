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
    $description = trim($_POST['description'] ?? '') ?: null;
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '') ?: null;
    $capacity = $this->parseCapacity($_POST['capacity'] ?? null);
    $price = $this->parsePrice($_POST['price'] ?? '');

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
        $longitude
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
    $capacity = $this->parseCapacity($_POST['capacity'] ?? null);
    $price = $this->parsePrice($_POST['price'] ?? '');
    $active = isset($_POST['active']);

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $town = trim($_POST['town'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    $venue = null;

    try {
      $this->requireCoordinates($latitude, $longitude);

      $venue = $this->venueService->findById($idVenue);

      if ($venue === null) {
        throw new BusinessRuleException("El local que intentas editar no existe.");
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $image = $this->resolveVenueImage($owner->getIdOwner(), $venue->getImageVenue());

      $idLocation = $this->locationService->findOrCreateByParts($province, $canton, $district, $town, $description, $latitude, $longitude);

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
  // PRECIO: acepta el valor con formato de colones (₡, comas y puntos)
  // y lo normaliza a un decimal compatible con la base de datos.
  // =========================================================
  private function parsePrice($raw): float
  {
    if (!is_scalar($raw)) {
      return 0.0;
    }

    $value = str_replace(["\u{20A1}", "\u{00A0}", ' '], '', trim((string) $raw));
    $value = preg_replace('/[^\d.,-]/u', '', $value);

    if ($value === '' || $value === '-') {
      return 0.0;
    }

    $negative = str_starts_with($value, '-');
    if ($negative) {
      $value = substr($value, 1);
    }

    $lastComma = strrpos($value, ',');
    $lastDot = strrpos($value, '.');
    $decimalSep = null;

    if ($lastComma !== false && $lastDot !== false) {
      $decimalSep = $lastComma > $lastDot ? ',' : '.';
    } elseif ($lastComma !== false) {
      $parts = explode(',', $value);
      if (count($parts) === 2 && strlen($parts[1]) <= 2) {
        $decimalSep = ',';
      }
    } elseif ($lastDot !== false) {
      $parts = explode('.', $value);
      if (count($parts) === 2 && strlen($parts[1]) <= 2) {
        $decimalSep = '.';
      }
    }

    if ($decimalSep !== null) {
      $idx = strrpos($value, $decimalSep);
      $intPart = str_replace([',', '.'], '', substr($value, 0, $idx));
      $decPart = preg_replace('/\D/', '', substr($value, $idx + 1));
      $value = $intPart . '.' . $decPart;
    } else {
      $value = str_replace([',', '.'], '', $value);
    }

    if (!is_numeric($value)) {
      return 0.0;
    }

    return ($negative ? -1 : 1) * (float) $value;
  }

  // =========================================================
  // CAPACIDAD: acepta solo dígitos y descarta cualquier separador.
  // =========================================================
  private function parseCapacity($raw): ?int
  {
    if (!is_scalar($raw) || trim((string) $raw) === '') {
      return null;
    }

    $digits = preg_replace('/\D/', '', (string) $raw);

    return $digits === '' ? null : (int) $digits;
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