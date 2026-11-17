<?php

require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Repository/ServiceGroupRepository.php';
require_once __DIR__ . '/../Repository/ServiceImageRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerServiceController
{
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private VenueRepository $venueRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->venueRepo = new VenueRepository($connection);
    $this->serviceService = new ServiceService(
      new ServiceRepository($connection),
      new ServiceHistoryRepository($connection),
      new ServiceGroupRepository($connection),
      new ServiceImageRepository($connection),
      $this->venueRepo
    );
    $this->ownerService = new OwnerService($connection);
  }

  // =========================================================
  // SERVICIOS DE UN LOCAL
  // =========================================================
  public function list(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $services = [];
    $error = '';
    $venue = null;

    try {
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);
      $venue = $this->venueRepo->findById($idVenue);
      $services = $this->serviceService->findByLocal($idVenue);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
    }

    require_once __DIR__ . '/../View/Service/List.php';
  }

  // =========================================================
  // SERVICIOS EXISTENTES AGRUPADOS (todos los locales)
  // =========================================================
  public function groups(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $groups = $this->serviceService->listGroupsForOwner($owner->getIdOwner());
    $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

    require_once __DIR__ . '/../View/Service/Manage.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (crear/editar)
  // =========================================================
  public function showForm(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $idService = (int) ($_GET['id'] ?? 0);
    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $service = null;
    $images = [];
    $error = '';

    try {
      if ($idService > 0) {
        $service = $this->serviceService->findById($idService);
        if ($service === null) {
          throw new BusinessRuleException('El servicio que intentas editar no existe.');
        }
        $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $service->getIdLocal());
        $idVenue = $service->getIdLocal();
        $images = $this->serviceService->getImages($idService);
      } elseif ($idVenue > 0) {
        $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);
      }

      $venues = $this->venueRepo->findActiveByOwner($owner->getIdOwner());
      $venue = $idVenue > 0 ? $this->venueRepo->findById($idVenue) : null;
      $types = $this->serviceService->getDistinctTypes();
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      $venues = [];
      $venue = null;
      $types = [];
    }

    require_once __DIR__ . '/../View/Service/Form.php';
  }

  // =========================================================
  // GUARDAR (crear en uno o varios locales)
  // =========================================================
  public function create(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $owner = $_SESSION['user'];
    $ownerId = $owner->getIdOwner();
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $allVenues = isset($_POST['allVenues']);
    $venueIds = $_POST['venueIds'] ?? [];

    if (!is_array($venueIds)) {
      $venueIds = [];
    }

    if ($allVenues) {
      $venueIds = array_map(
        static fn($venue): int => $venue->getIdVenue(),
        $this->venueRepo->findActiveByOwner($ownerId)
      );
    }

    if ($idVenue > 0) {
      $venueIds = [$idVenue];
    }

    $data = [
      'name' => trim($_POST['name'] ?? ''),
      'type' => trim($_POST['type'] ?? ''),
      'price' => (float) ($_POST['price'] ?? 0),
      'description' => trim($_POST['description'] ?? ''),
      'unit' => trim($_POST['unit'] ?? 'unidad'),
      'minQuantity' => (int) ($_POST['minQuantity'] ?? 1),
      'maxQuantity' => $_POST['maxQuantity'] ?? null,
      'includedQuantity' => (int) ($_POST['includedQuantity'] ?? 0),
    ];

    try {
      if ($venueIds === []) {
        throw new BusinessRuleException('No tienes locales activos o no seleccionaste ninguno.');
      }

      if (!$this->hasUploadedGallery()) {
        throw new BusinessRuleException('Debes subir al menos una imagen del servicio.');
      }

      $result = $this->serviceService->createForVenues($ownerId, $venueIds, $data);

      if (isset($_FILES['gallery']) && ($_FILES['gallery']['name'][0] ?? '') !== '') {
        $created = $result['created'];
        if ($created === [] && count($venueIds) === 1) {
          $existing = $this->serviceService->findByLocal($venueIds[0]);
          $created = array_map(static fn($s): int => $s->getIdService(), $existing);
        }
        foreach ($created as $venueId) {
          $services = $this->serviceService->findByLocal($venueId);
          foreach ($services as $service) {
            if ($service->getNameService() === $data['name']) {
              $this->uploadGallery($service->getIdService());
              break;
            }
          }
        }
      }

      $message = $this->buildCreateMessage($result);
      $target = $idVenue > 0 ? 'list' : 'groups';
      $params = $idVenue > 0 ? ['venueId' => $idVenue] : [];

      respond_or_redirect(['ok' => true, 'message' => $message], 'service', $target, $params);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $service = null;
      $images = [];
      $venues = $this->venueRepo->findActiveByOwner($ownerId);
      $venue = $idVenue > 0 ? $this->venueRepo->findById($idVenue) : null;
      $types = $this->serviceService->getDistinctTypes();
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
    $service = null;
    $images = [];

    try {
      $service = $this->serviceService->findById($idService);

      if ($service === null) {
        throw new BusinessRuleException('El servicio que intentas editar no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $service->getIdLocal());

      $existingImages = $this->serviceService->getImages($idService);
      $removeIds = array_map('intval', (array) ($_POST['removeGallery'] ?? []));
      $remaining = count(array_filter(
        $existingImages,
        static fn($img): bool => !in_array((int) $img->getIdImage(), $removeIds, true)
      ));
      if ($remaining === 0 && !$this->hasUploadedGallery()) {
        throw new BusinessRuleException('Debes mantener o subir al menos una imagen del servicio.');
      }

      $this->serviceService->validateAndUpdate(
        $service,
        trim($_POST['name'] ?? ''),
        trim($_POST['type'] ?? '') ?: null,
        (float) ($_POST['price'] ?? 0),
        ($_POST['active'] ?? '1') === '1',
        [
          'description' => trim($_POST['description'] ?? ''),
          'unit' => trim($_POST['unit'] ?? 'unidad'),
          'minQuantity' => (int) ($_POST['minQuantity'] ?? 1),
          'maxQuantity' => $_POST['maxQuantity'] ?? null,
          'includedQuantity' => (int) ($_POST['includedQuantity'] ?? 0),
        ]
      );

      $gallery = $this->serviceService->getImages($idService);
      $this->uploadGallery($idService);
      $this->removeSelectedGallery($gallery, fn(int $idImage) => $this->serviceService->removeImage($idImage));

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

      $idService = $service?->getIdService() ?? $idService;
      $images = $idService > 0 ? $this->serviceService->getImages($idService) : [];
      $venues = $this->venueRepo->findActiveByOwner($owner->getIdOwner());
      $venue = $service !== null ? $this->venueRepo->findById($service->getIdLocal()) : null;
      $types = $this->serviceService->getDistinctTypes();
      require_once __DIR__ . '/../View/Service/Form.php';
    }
  }

  // =========================================================
  // LOCALES DONDE SE OFRECE UN SERVICIO
  // =========================================================
  public function locations(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $groupId = (int) ($_GET['groupId'] ?? 0);
    $context = $this->serviceService->getGroupForOwner($owner->getIdOwner(), $groupId);
    $error = '';

    if ($context === null) {
      $error = 'El servicio no existe o no te pertenece.';
      $groups = $this->serviceService->listGroupsForOwner($owner->getIdOwner());
      require_once __DIR__ . '/../View/Service/Manage.php';
      return;
    }

    require_once __DIR__ . '/../View/Service/Locations.php';
  }

  public function updateLocations(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->locations();
      return;
    }

    $owner = $_SESSION['user'];
    $groupId = (int) ($_POST['groupId'] ?? 0);
    $venueIds = $_POST['venueIds'] ?? [];

    try {
      $result = $this->serviceService->updateGroupLocations(
        $owner->getIdOwner(),
        $groupId,
        is_array($venueIds) ? $venueIds : []
      );

      $parts = [];
      if (count($result['added']) > 0) {
        $parts[] = count($result['added']) . ' agregado(s)';
      }
      if (count($result['reactivated']) > 0) {
        $parts[] = count($result['reactivated']) . ' reactivado(s)';
      }
      if (count($result['removed']) > 0) {
        $parts[] = count($result['removed']) . ' quitado(s)';
      }
      if (count($result['skipped']) > 0) {
        $parts[] = count($result['skipped']) . ' omitido(s) por nombre duplicado';
      }

      respond_or_redirect(
        ['ok' => true, 'message' => 'Locales actualizados: ' . (implode(', ', $parts) ?: 'sin cambios') . '.'],
        'service',
        'groups'
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      $context = $this->serviceService->getGroupForOwner($owner->getIdOwner(), $groupId);

      if ($context === null) {
        $groups = $this->serviceService->listGroupsForOwner($owner->getIdOwner());
        require_once __DIR__ . '/../View/Service/Manage.php';
        return;
      }

      require_once __DIR__ . '/../View/Service/Locations.php';
    }
  }

  // =========================================================
  // TRASLADAR / DUPLICAR A OTRO LOCAL
  // =========================================================
  public function transfer(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $idService = (int) ($_GET['id'] ?? 0);
    $service = $this->serviceService->findById($idService);
    $currentVenue = null;
    $venues = [];
    $error = '';

    try {
      if ($service === null) {
        throw new BusinessRuleException('El servicio no existe.');
      }
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $service->getIdLocal());
      $currentVenue = $this->venueRepo->findById($service->getIdLocal());
      $venues = array_values(array_filter(
        $this->venueRepo->findActiveByOwner($owner->getIdOwner()),
        static fn($venue): bool => $venue->getIdVenue() !== $service->getIdLocal()
      ));
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
    }

    require_once __DIR__ . '/../View/Service/Transfer.php';
  }

  public function moveOrDuplicate(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->transfer();
      return;
    }

    $owner = $_SESSION['user'];
    $idService = (int) ($_POST['idService'] ?? 0);
    $targetVenueId = (int) ($_POST['targetVenueId'] ?? 0);
    $mode = trim($_POST['mode'] ?? 'duplicate');

    try {
      $service = $this->serviceService->findById($idService);
      if ($service === null) {
        throw new BusinessRuleException('El servicio no existe.');
      }
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $service->getIdLocal());

      $this->serviceService->moveOrDuplicate($owner->getIdOwner(), $idService, $targetVenueId, $mode);

      respond_or_redirect(
        [
          'ok' => true,
          'message' => $mode === 'move'
            ? 'Servicio trasladado al local seleccionado.'
            : 'Servicio duplicado en el local seleccionado.',
        ],
        'service',
        'groups'
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $service = $this->serviceService->findById($idService);
      $venues = array_values(array_filter(
        $this->venueRepo->findActiveByOwner($owner->getIdOwner()),
        static fn($venue): bool => $service === null || $venue->getIdVenue() !== $service->getIdLocal()
      ));
      require_once __DIR__ . '/../View/Service/Transfer.php';
    }
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

  private function uploadGallery(int $idService): void
  {
    if (!isset($_FILES['gallery']) || !is_array($_FILES['gallery']['name'] ?? null)) {
      return;
    }

    $files = $_FILES['gallery'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
      if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        continue;
      }

      $this->serviceService->addImage($idService, [
        'name' => $files['name'][$i],
        'type' => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error' => $files['error'][$i],
        'size' => $files['size'][$i],
      ]);
    }
  }

  private function hasUploadedGallery(): bool
  {
    if (!isset($_FILES['gallery']) || !is_array($_FILES['gallery']['error'] ?? null)) {
      return false;
    }

    foreach ($_FILES['gallery']['error'] as $error) {
      if ((int) $error === UPLOAD_ERR_OK) {
        return true;
      }
    }

    return false;
  }

  private function buildCreateMessage(array $result): string
  {
    $parts = [];
    if (count($result['created']) > 0) {
      $parts[] = 'Creado en ' . count($result['created']) . ' local(es)';
    }
    if (count($result['reactivated']) > 0) {
      $parts[] = 'Reactivado en ' . count($result['reactivated']) . ' local(es)';
    }
    if (count($result['skipped']) > 0) {
      $parts[] = count($result['skipped']) . ' local(es) omitido(s)';
    }

    return 'Servicio guardado. ' . implode('. ', $parts) . '.';
  }
}
