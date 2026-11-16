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
  private PDO $connection;

  public function __construct()
  {
    $connection = DataBase::getConnection();
    $this->connection = $connection;

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

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['id'] ?? 0);
    $venue = $idVenue > 0 ? $this->venueService->findById($idVenue) : null;

    if ($venue !== null) {
      try {
        $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);
      } catch (BusinessRuleException $e) {
        redirect_to('venue', 'list');
      }
    }
    $location = null;
    $images = [];
    $blocks = [];
    if ($venue !== null && $venue->getIdLocation() > 0) {
      $location = $this->locationService->findById($venue->getIdLocation());
    }
    if ($venue !== null) {
      $images = $this->venueService->getImages($venue->getIdVenue());
      $blocks = $this->venueService->getRentalBlocks($venue->getIdVenue());
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
      if ($description === null) {
        throw new BusinessRuleException('Las otras señas de la ubicación son obligatorias.');
      }

      $image = $this->resolveVenueImage($owner->getIdOwner(), '');

      if ($image === '') {
        throw new BusinessRuleException("Debes subir al menos una foto del local.");
      }

      $this->connection->beginTransaction();

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
        $image,
        $latitude,
        $longitude,
        $locationType,
        $approximate,
        $this->venueExtras()
      );

      $this->venueService->addUploadedGallery($idVenue);
      $this->saveRentalBlocks($idVenue);
      $this->connection->commit();

      respond_or_redirect(
        ['ok' => true, 'message' => 'Local creado correctamente.'],
        'venue',
        'list'
      );
    } catch (BusinessRuleException $e) {
      if ($this->connection->inTransaction()) {
        $this->connection->rollBack();
      }

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $venue = null;
      $images = [];
      $blocks = [];
      require_once __DIR__ . '/../View/Venue/Form.php';
    } catch (\Throwable $e) {
      if ($this->connection->inTransaction()) {
        $this->connection->rollBack();
      }
      throw $e;
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
      if ($description === null) {
        throw new BusinessRuleException('Las otras señas de la ubicación son obligatorias.');
      }

      $venue = $this->venueService->findById($idVenue);

      if ($venue === null) {
        throw new BusinessRuleException("El local que intentas editar no existe.");
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $image = $this->resolveVenueImage($owner->getIdOwner(), $venue->getImageVenue());
      $gallery = $this->venueService->getImages($idVenue);

      $this->connection->beginTransaction();

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
        $idLocation,
        $this->venueExtras()
      );

      $this->venueService->addUploadedGallery($idVenue);
      $this->removeSelectedGallery($gallery, fn(int $idImage) => $this->venueService->removeImage($idImage));
      $this->saveRentalBlocks($idVenue);
      $this->connection->commit();

      respond_or_redirect(
        ['ok' => true, 'message' => 'Local actualizado correctamente.'],
        'venue',
        'list'
      );
    } catch (BusinessRuleException $e) {
      if ($this->connection->inTransaction()) {
        $this->connection->rollBack();
      }

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $location = null;
      $images = [];
      $blocks = [];
      if ($venue !== null && $venue->getIdLocation() > 0) {
        $location = $this->locationService->findById($venue->getIdLocation());
      }
      if ($venue !== null) {
        $images = $this->venueService->getImages($venue->getIdVenue());
        $blocks = $this->venueService->getRentalBlocks($venue->getIdVenue());
      }

      require_once __DIR__ . '/../View/Venue/Form.php';
    } catch (\Throwable $e) {
      if ($this->connection->inTransaction()) {
        $this->connection->rollBack();
      }
      throw $e;
    }
  }

  private function venueExtras(): array
  {
    return [
      'venueDescription' => trim($_POST['venueDescription'] ?? ''),
      'size' => trim($_POST['size'] ?? ''),
      'bathrooms' => $_POST['bathrooms'] ?? null,
      'parking' => isset($_POST['parking']),
      'parkingCapacity' => $_POST['parkingCapacity'] ?? null,
      'minCapacity' => $_POST['minCapacity'] ?? null,
      'cleaningFee' => (float) ($_POST['cleaningFee'] ?? 0),
    ];
  }

  private function removeSelectedGallery(array $images, callable $remove): void
  {
    $selected = $_POST['removeGallery'] ?? [];

    if (!is_array($selected)) {
      return;
    }

    $allowed = array_map(static fn($image): int => (int) $image->getIdImage(), $images);

    foreach ($selected as $idImage) {
      $idImage = (int) $idImage;
      if ($idImage > 0 && in_array($idImage, $allowed, true)) {
        $remove($idImage);
      }
    }
  }

  private function saveRentalBlocks(int $idVenue): void
  {
    $ids = $_POST['blockId'] ?? [];
    $names = $_POST['blockName'] ?? [];
    $starts = $_POST['blockStart'] ?? [];
    $ends = $_POST['blockEnd'] ?? [];
    $prices = $_POST['blockPrice'] ?? [];

    if (!is_array($names)) {
      return;
    }

    $this->venueService->saveRentalBlocks(
      $idVenue,
      $names,
      is_array($starts) ? $starts : [],
      is_array($ends) ? $ends : [],
      is_array($prices) ? $prices : [],
      is_array($ids) ? $ids : []
    );
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
}