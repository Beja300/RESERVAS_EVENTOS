<?php

require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ClientController
{
  private HistoryService $historyService;
  private AuthService $authService;
  private RoleSecurityService $roleSecurityService;
  private BookingRepository $bookingRepo;
  private RoleRepository $roleRepo;
  private ClientRepository $clientRepo;
  private DetailRepository $detailRepo;
  private LocationService $locationService;
  private LocationRepository $locationRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->historyService = new HistoryService($connection);
    $this->authService = new AuthService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->bookingRepo = new BookingRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->locationService = new LocationService(new LocationRepository($connection));
    $this->locationRepo = new LocationRepository($connection);
  }

  // =========================================================
  // DASHBOARD (recomendaciones + mis reservas)
  // =========================================================
  public function dashboard(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];

    $recommendations = $this->historyService->recommendVenues(
      $client->getIdRol(),
      5
    );

    $clientLocationId = $client->getLocationId();

    $hasValidLocation = $clientLocationId !== null
      && $this->locationRepo->findById($clientLocationId) !== null;

    $nearbyVenues = $hasValidLocation
      ? $this->historyService->recommendVenuesByLocation($clientLocationId, 5)
      : [];

    $hasLocation = $hasValidLocation;

    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    $allVenues = array_merge($recommendations, $nearbyVenues);
    $locationByVenue = [];
    $locationCache = [];
    foreach ($allVenues as $v) {
      $locId = $v->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationRepo->findById($locId);
        }
        $locationByVenue[$v->getIdVenue()] = $locationCache[$locId];
      }
    }

    require_once __DIR__ . '/../View/Client/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];

    $suspicious = $this->roleSecurityService->getSuspiciousCounts($client->getIdRol());

    $location = null;
    if ($client->getLocationId() !== null) {
      $location = $this->locationRepo->findById($client->getLocationId());
    }

    require_once __DIR__ . '/../View/Client/Profile.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (nombre, correo, teléfono, foto, ubicación)
  // =========================================================
  public function updateProfile(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $currentEmail = $client->getEmail();
    $currentPhone = $client->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if (strtolower($email) !== strtolower($client->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      // Cambio de contraseña: solo con confirmación de la contraseña actual.
      $hasCurrent = trim($currentPassword) !== '';
      $hasNew = trim($newPassword) !== '';

      if ($hasCurrent || $hasNew) {
        if (!$hasCurrent || !$hasNew) {
          throw new BusinessRuleException('Para cambiar tu contraseña debes escribir la contraseña actual y la nueva.');
        }

        if (!password_verify($currentPassword, $client->getPassword())) {
          throw new BusinessRuleException('La contraseña actual no es correcta.');
        }

        $this->roleSecurityService->changePassword($client->getIdRol(), $newPassword);
      }

      $client->setName($name);
      $client->setEmail($email);
      $client->setPhoneNumber($phoneNumber);

      $this->roleRepo->update($client);

      // Seguridad: auditar y alertar ante cambios de credenciales.
      $this->roleSecurityService->recordPhoneChange(
        $client->getIdRol(),
        $currentPhone,
        $phoneNumber
      );
      $this->roleSecurityService->recordEmailChange(
        $client->getIdRol(),
        $currentEmail,
        $email
      );

      $image = $this->resolveProfileImage($client->getIdClient(), $client->getImageClient());

      $province = trim($_POST['province'] ?? '');
      $canton = trim($_POST['canton'] ?? '');
      $district = trim($_POST['district'] ?? '');
      $town = trim($_POST['town'] ?? '') ?: null;
      $description = trim($_POST['description'] ?? '') ?: null;

      $locationId = $client->getLocationId();

      if ($province !== '' || $canton !== '' || $district !== '') {
        // Solo crea una ubicación nueva si realmente cambió respecto a la actual.
        // Así no se rompe al guardar de nuevo la misma dirección (idempotente).
        $currentLocation = $locationId !== null ? $this->locationRepo->findById($locationId) : null;
        $changed = $currentLocation === null
          || $currentLocation->getProvinceLocation() !== $province
          || $currentLocation->getCantonLocation() !== $canton
          || $currentLocation->getDistrictLocation() !== $district
          || $currentLocation->getTownLocation() !== $town
          || $currentLocation->getDescriptionLocation() !== $description;

        if ($changed) {
          $locationId = $this->locationService->validateAndCreate(
            $province,
            $canton,
            $district,
            $town,
            $description
          );
        }
      }

      $this->clientRepo->updateProfile($client->getIdClient(), $image, $locationId);
      $client->setImageClient($image);
      $client->setLocationId($locationId);

      $_SESSION['user'] = $client;

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Perfil actualizado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=client&action=profile');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $location = null;
      if ($client->getLocationId() !== null) {
        $location = $this->locationRepo->findById($client->getLocationId());
      }

      require_once __DIR__ . '/../View/Client/Profile.php';
    }
  }

  // =========================================================
  // GUARDAR UBICACIÓN DETECTADA AL INICIAR SESIÓN
  // Solo la aplica si el cliente aún no tiene una configurada.
  // =========================================================
  public function updateLocation(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      respond_json(['ok' => false, 'message' => 'Método no permitido.'], 405);
      return;
    }

    $client = $_SESSION['user'];

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');

    try {

      if ($province === '' || $canton === '' || $district === '') {
        throw new BusinessRuleException('Datos de ubicación incompletos.');
      }

      $currentLocationId = $client->getLocationId();
      $hasValidLocation = $currentLocationId !== null
        && $this->locationRepo->findById($currentLocationId) !== null;

      if ($hasValidLocation) {
        respond_json([
          'ok'      => true,
          'saved'   => false,
          'message' => 'Ya tienes una ubicación configurada en tu perfil.',
        ]);
        return;
      }

      // Reutiliza una ubicación existente (por partes o solo por cantón)
      // para no duplicar filas; solo crea si no existe ninguna.
      $locationId = $this->locationRepo->findIdByParts($province, $canton, $district);

      if ($locationId === null) {
        $locationId = $this->locationRepo->findIdByCanton($province, $canton);
      }

      if ($locationId === null) {
        $locationId = $this->locationService->validateAndCreate($province, $canton, $district);
      }

      $this->clientRepo->updateProfile($client->getIdClient(), $client->getImageClient(), $locationId);
      $client->setLocationId($locationId);

      $_SESSION['user'] = $client;

      respond_json([
        'ok'       => true,
        'saved'    => true,
        'message'  => 'Ubicación detectada: ' . $canton . ', ' . $province,
        'location' => [
          'province' => $province,
          'canton'   => $canton,
          'district' => $district,
        ],
      ]);
    } catch (BusinessRuleException $e) {

      respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
    }
  }

  // =========================================================
  // FOTO DE PERFIL: prioriza el archivo subido, luego la URL.
  // =========================================================
  private function resolveProfileImage(int $idClient, string $currentImage): string
  {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $file = $_FILES['image'];
      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

      if (!in_array($extension, $allowed, true)) {
        throw new BusinessRuleException("Formato de imagen no válido (usa jpg, png, webp o gif).");
      }

      if ($file['size'] > 2 * 1024 * 1024) {
        throw new BusinessRuleException("La imagen no puede superar los 2 MB.");
      }

      $dir = __DIR__ . '/../../Public/resource/clients/';
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }

      $filename = 'client_' . $idClient . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

      if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return 'resource/clients/' . $filename;
      }

      throw new BusinessRuleException("No se pudo guardar la imagen.");
    }

    $url = trim($_POST['imageUrl'] ?? '');
    if ($url !== '') {
      return $url;
    }

    return $currentImage;
  }

  // =========================================================
  // GUARDIA: SOLO CLIENTE AUTENTICADO
  // =========================================================
  private function requireClient(): void
  {
    if (($_SESSION['type'] ?? null) !== 'client') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }

  // =========================================================
  // ELIMINAR FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $this->deleteClientImageFile($client->getImageClient());
    $client->setImageClient('');

    $this->clientRepo->updateProfile($client->getIdClient(), '', $client->getLocationId());

    $_SESSION['user'] = $client;

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Foto de perfil eliminada.']);
    }

    header('Location: ../../Public/index.php?controller=client&action=profile&removed=1');
    exit;
  }

  // =========================================================
  // BORRAR EL ARCHIVO LOCAL (nunca URLs externas)
  // =========================================================
  private const CLIENT_IMAGE_DIR = 'resource/clients/';

  private function deleteClientImageFile(string $storedPath): void
  {
    if (str_starts_with($storedPath, self::CLIENT_IMAGE_DIR)) {
      $file = __DIR__ . '/../../Public/' . $storedPath;
      if (is_file($file)) {
        @unlink($file);
      }
    }
  }

  // =========================================================
  // DESACTIVAR MI CUENTA (borrado lógico: tbroleactive=false)
  // Nunca se elimina el registro: se conserva el historial.
  // =========================================================
  public function deactivateAccount(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $this->roleRepo->setActive($client->getIdRol(), false);

    session_unset();
    session_destroy();

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Tu cuenta fue desactivada.']);
    }

    header('Location: ../../Public/index.php?controller=auth&action=showLogin');
    exit;
  }
}
