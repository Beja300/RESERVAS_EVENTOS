<?php

require_once __DIR__ . '/../Service/ProfileService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/LocationService.php';
require_once __DIR__ . '/../Service/ImageStorageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class ClientProfileController
{
  private ProfileService $profileService;
  private RoleSecurityService $roleSecurityService;
  private LocationService $locationService;
  private LocationRepository $locationRepo;
  private RoleRepository $roleRepo;
  private ClientRepository $clientRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->profileService = new ProfileService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->locationService = new LocationService(new LocationRepository($connection));
    $this->locationRepo = new LocationRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    require_role('client');

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
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $currentEmail = $client->getEmail();
    $currentPhone = $client->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    try {
      $this->profileService->validateUniqueEmail($currentEmail, $email);
      $this->profileService->validatePhone($phoneNumber);
      $this->profileService->changePasswordIfRequested($client, $currentPassword, $newPassword);

      $client->setName($name);
      $client->setEmail($email);
      $client->setPhoneNumber($phoneNumber);

      $this->profileService->recordCredentialChanges($client, $currentPhone, $phoneNumber, $currentEmail, $email);

      $image = $this->resolveProfileImage($client->getIdClient(), $client->getImageClient());

      $province = trim($_POST['province'] ?? '');
      $canton = trim($_POST['canton'] ?? '');
      $district = trim($_POST['district'] ?? '');
      $town = trim($_POST['town'] ?? '') ?: null;
      $description = trim($_POST['description'] ?? '') ?: null;

      $locationId = $client->getLocationId();

      if ($province !== '' || $canton !== '' || $district !== '') {
        // Solo crea/vincula una ubicación si realmente cambió respecto a la actual.
        $currentLocation = $locationId !== null ? $this->locationRepo->findById($locationId) : null;
        $changed = $currentLocation === null
          || $currentLocation->getProvinceLocation() !== $province
          || $currentLocation->getCantonLocation() !== $canton
          || $currentLocation->getDistrictLocation() !== $district
          || $currentLocation->getTownLocation() !== $town
          || $currentLocation->getDescriptionLocation() !== $description;

        if ($changed) {
          $locationId = $this->locationService->validateAndCreate($province, $canton, $district, $town, $description);
        }
      }

      $this->roleRepo->update($client);
      $this->clientRepo->updateProfile($client->getIdClient(), $image, $locationId);
      $client->setImageClient($image);
      $client->setLocationId($locationId);

      $_SESSION['user'] = $client;

      respond_or_redirect(
        ['ok' => true, 'message' => 'Perfil actualizado correctamente.'],
        'client',
        'profile'
      );
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
    require_role('client');

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
      }

      $locationId = $this->locationService->findOrCreateByParts($province, $canton, $district);

      $this->clientRepo->updateProfile($client->getIdClient(), $client->getImageClient(), $locationId);
      $client->setLocationId($locationId);

      $_SESSION['user'] = $client;

      respond_json([
        'ok'       => true,
        'saved'    => true,
        'message'  => 'Ubicación detectada: ' . $province . ' · ' . $canton . ' · ' . $district,
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
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    ImageStorageService::deleteLocal($client->getImageClient());
    $client->setImageClient('');

    $this->clientRepo->updateProfile($client->getIdClient(), '', $client->getLocationId());

    $_SESSION['user'] = $client;

    respond_or_redirect(
      ['ok' => true, 'message' => 'Foto de perfil eliminada.'],
      'client',
      'profile',
      ['removed' => 1]
    );
  }

  // =========================================================
  // DESACTIVAR MI CUENTA (borrado lógico: tbroleactive=false)
  // Nunca se elimina el registro: se conserva el historial.
  // =========================================================
  public function deactivateAccount(): void
  {
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $this->roleRepo->setActive($client->getIdRol(), false);

    session_unset();
    session_destroy();

    respond_or_redirect(
      ['ok' => true, 'message' => 'Tu cuenta fue desactivada.'],
      'auth',
      'showLogin'
    );
  }

  // =========================================================
  // FOTO DE PERFIL: prioriza el archivo subido, luego la URL.
  // =========================================================
  private function resolveProfileImage(int $idClient, string $currentImage): string
  {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      return ImageStorageService::store(
        $_FILES['image'],
        'clients',
        'client_' . $idClient
      );
    }

    $url = trim($_POST['imageUrl'] ?? '');

    if ($url !== '') {
      if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new BusinessRuleException("URL de imagen no válida.");
      }

      return $url;
    }

    return $currentImage;
  }
}