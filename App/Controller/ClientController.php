<?php

require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/AuthService.php';
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

    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

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

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if (strtolower($email) !== strtolower($client->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      $client->setName($name);
      $client->setEmail($email);
      $client->setPhoneNumber($phoneNumber);

      $this->roleRepo->update($client);

      $image = $this->resolveProfileImage($client->getIdClient(), $client->getImageClient());

      $province = trim($_POST['province'] ?? '');
      $canton = trim($_POST['canton'] ?? '');
      $district = trim($_POST['district'] ?? '');
      $town = trim($_POST['town'] ?? '') ?: null;
      $description = trim($_POST['description'] ?? '') ?: null;

      $locationId = $client->getLocationId();
      if ($province !== '' || $canton !== '' || $district !== '') {
        $locationId = $this->locationService->validateAndCreate(
          $province,
          $canton,
          $district,
          $town,
          $description
        );
      }

      $this->clientRepo->updateProfile($client->getIdClient(), $image, $locationId);
      $client->setImageClient($image);
      $client->setLocationId($locationId);

      $_SESSION['user'] = $client;

      header('Location: ../../Public/index.php?controller=client&action=profile');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $location = null;
      if ($client->getLocationId() !== null) {
        $location = $this->locationRepo->findById($client->getLocationId());
      }

      require_once __DIR__ . '/../View/Client/Profile.php';
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

    header('Location: ../../Public/index.php?controller=auth&action=showLogin');
    exit;
  }
}
