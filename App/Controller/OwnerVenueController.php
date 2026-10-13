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
  private const MAX_VENUE_IMAGES = 6;

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
    $venueImages = $venue !== null ? $this->venueService->galleryByVenue($venue->getIdVenue()) : [];
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
    $capacity = isset($_POST['capacity']) && $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0.0;

    try {
      $this->requireCoordinates($latitude, $longitude);

      $incoming = $this->incomingFileCount();

      if ($incoming === 0) {
        throw new BusinessRuleException("Debes subir al menos una foto del local.");
      }

      if ($incoming > self::MAX_VENUE_IMAGES) {
        throw new BusinessRuleException("El local puede tener máximo 6 fotos.");
      }

      $images = $this->resolveVenueImages($owner->getIdOwner());

      $idVenue = $this->venueService->validateAndCreate(
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
        $images[0],
        $latitude,
        $longitude
      );

      $this->venueService->replaceGallery($idVenue, array_slice($images, 1));

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
      $venueImages = [];
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

      $keptImages = $this->sanitizeKeptImages($_POST['existingImages'] ?? []);

      if ($this->incomingFileCount() + count($keptImages) > self::MAX_VENUE_IMAGES) {
        throw new BusinessRuleException("El local puede tener máximo 6 fotos.");
      }

      $newImages = $this->resolveVenueImages($owner->getIdOwner());

      $currentImages = $this->currentImages($venue);
      $finalSet = array_values(array_unique(array_merge($keptImages, $newImages)));

      if (count($finalSet) === 0) {
        throw new BusinessRuleException("El local debe conservar al menos una foto.");
      }

      if (count($finalSet) > self::MAX_VENUE_IMAGES) {
        throw new BusinessRuleException("El local puede tener máximo 6 fotos.");
      }

      $removed = array_diff($currentImages, $finalSet);
      foreach ($removed as $path) {
        ImageStorageService::deleteLocal($path);
      }

      $idLocation = $this->locationService->findOrCreateByParts($province, $canton, $district, $town, $description, $latitude, $longitude);

      $this->venueService->validateAndUpdate(
        $venue,
        $name,
        $type,
        $capacity,
        $price,
        $finalSet[0],
        $active,
        $idLocation
      );

      $this->venueService->replaceGallery($idVenue, array_slice($finalSet, 1));

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

      $venueImages = $venue !== null ? $this->venueService->galleryByVenue($venue->getIdVenue()) : [];

      require_once __DIR__ . '/../View/Venue/Form.php';
    }
  }

  // =========================================================
  // FOTOS DEL LOCAL (múltiples): sube todos los archivos de
  // $_FILES['image'] y devuelve sus rutas, en orden de selección.
  // Si una foto falla, borra las ya guardadas y relanza (evita huérfanos).
  // =========================================================
  private function resolveVenueImages(int $ownerId): array
  {
    if (!isset($_FILES['image'])) {
      return [];
    }

    $files = $_FILES['image'];
    $paths = [];
    $single = !is_array($files['name']) ? $files : null;

    $queue = $single !== null
      ? [$single]
      : array_map(static fn ($i) => [
          'name'     => $files['name'][$i],
          'type'     => $files['type'][$i] ?? '',
          'tmp_name' => $files['tmp_name'][$i],
          'error'    => $files['error'][$i],
          'size'     => $files['size'][$i],
        ], array_keys($files['name']));

    try {
      foreach ($queue as $one) {
        if (($one['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          continue;
        }

        $paths[] = ImageStorageService::store($one, 'venues', 'venue_' . $ownerId);
      }
    } catch (\Throwable $e) {
      foreach ($paths as $path) {
        ImageStorageService::deleteLocal($path);
      }
      throw $e;
    }

    return $paths;
  }

  // =========================================================
  // CUENTA los archivos válidos entrantes de $_FILES['image']
  // (antes de guardarlos, para validar el máximo sin huérfanos).
  // =========================================================
  private function incomingFileCount(): int
  {
    if (!isset($_FILES['image'])) {
      return 0;
    }

    $files = $_FILES['image'];

    if (!is_array($files['name'])) {
      return $files['error'] === UPLOAD_ERR_OK ? 1 : 0;
    }

    $count = 0;
    foreach ($files['error'] as $error) {
      if ($error === UPLOAD_ERR_OK) {
        $count++;
      }
    }

    return $count;
  }

  // =========================================================
  // CONSERVA SOLO RUTAS LOCALES DE FOTOS DE LOCALES (evita inyección
  // de rutas arbitrarias en el POST de edición).
  // =========================================================
  private function sanitizeKeptImages(array $paths): array
  {
    $clean = [];

    foreach ($paths as $path) {
      $path = trim((string) $path);
      if ($path !== '' && str_starts_with($path, 'resource/venues/')) {
        $clean[] = $path;
      }
    }

    return array_values(array_unique($clean));
  }

  // =========================================================
  // CONJUNTO ACTUAL DE FOTOS (portada + galería) de un local.
  // =========================================================
  private function currentImages(Venue $venue): array
  {
    $paths = [$venue->getImageVenue()];

    foreach ($this->venueService->galleryByVenue($venue->getIdVenue()) as $image) {
      $paths[] = $image->getImagePath();
    }

    return array_values(array_filter(array_unique($paths), static fn ($p) => $p !== ''));
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