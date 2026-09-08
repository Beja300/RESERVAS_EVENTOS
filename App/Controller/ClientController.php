<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/ClientService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/ProfileImageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Model/Client.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ClientController extends BaseController
{
  private ClientService $clientService;
  private HistoryService $historyService;
  private AuthService $authService;
  private RoleSecurityService $roleSecurityService;
  private LocationService $locationService;
  private ProfileImageService $profileImageService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->clientService = new ClientService($connection);
    $this->historyService = new HistoryService($connection);
    $this->authService = new AuthService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->locationService = new LocationService(new LocationRepository($connection));
    $this->profileImageService = new ProfileImageService();
  }

  // =========================================================
  // DASHBOARD (recomendaciones + mis reservas)
  // =========================================================
  public function dashboard(): void
  {
    $this->requireClient();

    $client = $this->currentUser();

    $recommendations = $this->historyService->recommendVenues(
      $client->getIdRol(),
      5
    );

    $clientLocationId = $client->getLocationId();

    $hasLocation = $clientLocationId !== null
      && $this->clientService->isValidLocation($clientLocationId);

    $nearbyVenues = $hasLocation
      ? $this->historyService->recommendVenuesByLocation($clientLocationId, 5)
      : [];

    $bookings = $this->clientService->getClientBookings($client->getIdClient());

    $allVenues = array_merge($recommendations, $nearbyVenues);
    $locationByVenue = [];
    foreach ($allVenues as $v) {
      $locId = $v->getIdLocation();
      if ($locId > 0) {
        $locationByVenue[$v->getIdVenue()] = $this->clientService->getLocation($locId);
      }
    }

    require_once __DIR__ . '/../View/Client/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    $this->requireClient();

    $client = $this->currentUser();

    $suspicious = $this->roleSecurityService->getSuspiciousCounts($client->getIdRol());

    $location = $this->clientService->getLocation($client->getLocationId());

    require_once __DIR__ . '/../View/Client/Profile.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (nombre, correo, teléfono, foto, ubicación)
  // =========================================================
  public function updateProfile(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $this->currentUser();

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

      $image = $this->profileImageService->resolveAndPersist(
        $client->getImageClient(),
        $_POST,
        $_FILES,
        'resource/clients/',
        'client_' . $client->getIdClient() . '_'
      );

      $province = trim($_POST['province'] ?? '');
      $canton = trim($_POST['canton'] ?? '');
      $district = trim($_POST['district'] ?? '');
      $town = trim($_POST['town'] ?? '') ?: null;
      $description = trim($_POST['description'] ?? '') ?: null;

      $locationId = $client->getLocationId();

      if ($province !== '' || $canton !== '' || $district !== '') {
        // Solo crea una ubicación nueva si realmente cambió respecto a la actual.
        // Así no se rompe al guardar de nuevo la misma dirección (idempotente).
        $currentLocation = $locationId !== null ? $this->clientService->getLocation($locationId) : null;
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

      $this->clientService->saveProfile($client, $image, $locationId);

      $_SESSION['user'] = $client;

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Perfil actualizado correctamente.']);
      }

      $this->redirect('client', 'profile');
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      $location = $this->clientService->getLocation($client->getLocationId());

      require_once __DIR__ . '/../View/Client/Profile.php';
    }
  }

  // =========================================================
  // GUARDAR UBICACIÓN DETECTADA AL INICIAR SESIÓN
  // Solo la aplica si el cliente aún no tiene una configurada.
  // =========================================================
  public function updateLocation(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      respond_json(['ok' => false, 'message' => 'Método no permitido.'], 405);
      return;
    }

    $client = $this->currentUser();

    $province = trim($_POST['province'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $district = trim($_POST['district'] ?? '');

    try {

      if ($province === '' || $canton === '' || $district === '') {
        throw new BusinessRuleException('Datos de ubicación incompletos.');
      }

      $currentLocationId = $client->getLocationId();
      $hasValidLocation = $currentLocationId !== null
        && $this->clientService->isValidLocation($currentLocationId);

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
      $locationId = $this->clientService->findLocationIdByParts($province, $canton, $district);

      if ($locationId === null) {
        $locationId = $this->clientService->findLocationIdByCanton($province, $canton);
      }

      if ($locationId === null) {
        $locationId = $this->locationService->validateAndCreate($province, $canton, $district);
      }

      $this->clientService->saveProfile($client, $client->getImageClient(), $locationId);

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
  // ELIMINAR FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $this->currentUser();

    $this->profileImageService->deleteStoredFile($client->getImageClient(), 'resource/clients/');
    $this->clientService->saveProfile($client, '', $client->getLocationId());

    $_SESSION['user'] = $client;

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Foto de perfil eliminada.']);
    }

    $this->redirect('client', 'profile', ['removed' => 1]);
  }

  // =========================================================
  // DESACTIVAR MI CUENTA (borrado lógico: tbroleactive=false)
  // Nunca se elimina el registro: se conserva el historial.
  // =========================================================
  public function deactivateAccount(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $this->currentUser();

    $this->clientService->deactivateAccount($client->getIdRol());

    session_unset();
    session_destroy();

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Tu cuenta fue desactivada.']);
    }

    header('Location: ../../Public/index.php?controller=auth&action=showLogin');
    exit;
  }
}