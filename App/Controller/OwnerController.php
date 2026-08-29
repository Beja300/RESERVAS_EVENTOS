<?php

require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class OwnerController
{
  private AuthService $authService;
  private HistoryService $historyService;
  private VenueRepository $venueRepo;
  private BookingRepository $bookingRepo;
  private RoleRepository $roleRepo;
  private OwnerRepository $ownerRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->authService = new AuthService();
    $this->historyService = new HistoryService($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->ownerRepo = new OwnerRepository($connection);
  }

  // =========================================================
  // DASHBOARD (sus locales + sus reservas)
  // =========================================================
  public function dashboard(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];

    $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

    $bookings = [];
    foreach ($venues as $venue) {
      $bookings[$venue->getIdVenue()] = $this->bookingRepo->findByVenue($venue->getIdVenue());
    }

    require_once __DIR__ . '/../View/Owner/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];

    require_once __DIR__ . '/../View/Owner/Form.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (todos los datos del propietario).
  // Para cambiar la contraseña se exige confirmar la actual.
  // =========================================================
  public function updateProfile(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $ownerLastName = trim($_POST['ownerLastName'] ?? '');
    $ownerAlias = trim($_POST['ownerAlias'] ?? '');
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if ($name === '') {
        $name = $owner->getName();
      }

      if (strtolower($email) !== strtolower($owner->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      if ($ownerIdentification !== ''
          && strtolower($ownerIdentification) !== strtolower($owner->getIdentificationNumberOwner())) {
        $this->authService->validateIdentificationIsUnique($ownerIdentification);
      }

      // Cambio de contraseña: solo con confirmación de la contraseña actual.
      $hasCurrent = trim($currentPassword) !== '';
      $hasNew = trim($newPassword) !== '';

      if ($hasCurrent || $hasNew) {
        if (!$hasCurrent || !$hasNew) {
          throw new BusinessRuleException('Para cambiar tu contraseña debes escribir la contraseña actual y la nueva.');
        }

        if (!password_verify($currentPassword, $owner->getPassword())) {
          throw new BusinessRuleException('La contraseña actual no es correcta.');
        }

        $this->authService->validatePasswordStrength($newPassword);
      }

      $owner->setName($name);
      $owner->setEmail($email);
      $owner->setPhoneNumber($phoneNumber);
      $owner->setLastNameOwner($ownerLastName);
      $owner->setAliasOwner($ownerAlias);
      $owner->setIdentificationNumberOwner($ownerIdentification);

      $this->resolveOwnerProfileImage($owner);

      $this->roleRepo->update($owner);
      $this->ownerRepo->updateProfile($owner);

      if ($hasCurrent && $hasNew) {
        $this->roleRepo->updatePassword($owner->getIdRol(), $newPassword);
      }

      $_SESSION['user'] = $owner;

      header('Location: ../../Public/index.php?controller=owner&action=profile&updated=1');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Owner/Form.php';
    }
  }

  // =========================================================
  // ELIMINAR FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    $this->deleteOwnerImageFile($owner->getImageOwner());
    $owner->setImageOwner('');

    $this->ownerRepo->updateProfile($owner);

    $_SESSION['user'] = $owner;

    header('Location: ../../Public/index.php?controller=owner&action=profile&removed=1');
    exit;
  }

  // =========================================================
  // FOTO DE PERFIL: prioriza borrar, luego archivo, luego URL.
  // =========================================================
  private const OWNER_IMAGE_DIR = 'resource/owners/';

  private function resolveOwnerProfileImage(Owner $owner): void
  {
    $current = $owner->getImageOwner();
    $newImage = $current;

    if (isset($_POST['removePhoto'])) {
      $newImage = '';
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $file = $_FILES['image'];
      $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

      if (!in_array($extension, $allowed, true)) {
        throw new BusinessRuleException("Formato de imagen no válido (usa jpg, png, webp o gif).");
      }

      if ($file['size'] > 2 * 1024 * 1024) {
        throw new BusinessRuleException("La imagen no puede superar los 2 MB.");
      }

      $dir = __DIR__ . '/../../Public/resource/owners/';
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }

      $filename = 'owner_' . $owner->getIdOwner() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

      if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new BusinessRuleException("No se pudo guardar la imagen.");
      }

      $newImage = self::OWNER_IMAGE_DIR . $filename;
    } else {
      $url = trim($_POST['imageUrl'] ?? '');

      if ($url !== '') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
          throw new BusinessRuleException("URL de imagen no válida.");
        }

        $newImage = $url;
      }
    }

    if ($newImage !== $current) {
      $this->deleteOwnerImageFile($current);
      $owner->setImageOwner($newImage);
    }
  }

  // =========================================================
  // BORRAR EL ARCHIVO LOCAL (nunca URLs externas)
  // =========================================================
  private function deleteOwnerImageFile(string $storedPath): void
  {
    if (str_starts_with($storedPath, self::OWNER_IMAGE_DIR)) {
      $file = __DIR__ . '/../../Public/' . $storedPath;
      if (is_file($file)) {
        @unlink($file);
      }
    }
  }

  // =========================================================
  // GUARDIA: SOLO OWNER AUTENTICADO
  // =========================================================
  private function requireOwner(): void
  {
    if (($_SESSION['type'] ?? null) !== 'owner') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
