<?php

require_once __DIR__ . '/../Service/ProfileService.php';
require_once __DIR__ . '/../Service/ImageStorageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerProfileController
{
  private ProfileService $profileService;
  private BookingRepository $bookingRepo;
  private RoleRepository $roleRepo;
  private OwnerRepository $ownerRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->profileService = new ProfileService();
    $this->bookingRepo = new BookingRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->ownerRepo = new OwnerRepository($connection);
  }

  // =========================================================
  // VER PERFIL (el propietario edita directamente el formulario)
  // =========================================================
  public function profile(): void
  {
    require_role('owner');

    require_once __DIR__ . '/../View/Owner/Form.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (todos los datos del propietario).
  // Para cambiar la contraseña se exige confirmar la actual.
  // =========================================================
  public function updateProfile(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    $currentEmail = $owner->getEmail();
    $currentPhone = $owner->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;
    $ownerLastName = trim($_POST['ownerLastName'] ?? '');
    $ownerAlias = trim($_POST['ownerAlias'] ?? '');
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    try {
      if ($name === '') {
        $name = $owner->getName();
      }

      $this->profileService->validateUniqueEmail($currentEmail, $email);
      $this->profileService->validatePhone($phoneNumber);

      if ($ownerIdentification !== '') {
        $this->profileService->validateUniqueIdentification($owner->getIdentificationNumberOwner(), $ownerIdentification);
      }

      $this->profileService->changePasswordIfRequested($owner, $currentPassword, $newPassword);

      $owner->setName($name);
      $owner->setEmail($email);
      $owner->setPhoneNumber($phoneNumber);
      $owner->setLastNameOwner($ownerLastName);
      $owner->setAliasOwner($ownerAlias);
      $owner->setIdentificationNumberOwner($ownerIdentification);

      $this->resolveOwnerProfileImage($owner);

      $this->profileService->recordCredentialChanges($owner, $currentPhone, $phoneNumber, $currentEmail, $email);

      $this->roleRepo->update($owner);
      $this->ownerRepo->updateProfile($owner);

      $_SESSION['user'] = $owner;

      respond_or_redirect(
        ['ok' => true, 'message' => 'Perfil actualizado correctamente.'],
        'owner',
        'profile',
        ['updated' => 1]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Owner/Form.php';
    }
  }

  // =========================================================
  // ELIMINAR FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    ImageStorageService::deleteLocal($owner->getImageOwner());
    $owner->setImageOwner('');

    $this->ownerRepo->updateProfile($owner);

    $_SESSION['user'] = $owner;

    respond_or_redirect(
      ['ok' => true, 'message' => 'Foto de perfil eliminada.'],
      'owner',
      'profile',
      ['removed' => 1]
    );
  }

  // =========================================================
  // DESACTIVAR CUENTA (borrado lógico de tbrole).
  // Solo se permite si el propietario NO tiene reservas futuras
  // (hoy o después) pendientes o confirmadas en sus locales.
  // =========================================================
  public function deactivateAccount(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    try {
      if ($this->bookingRepo->hasUpcomingActiveByOwner($owner->getIdOwner())) {
        throw new BusinessRuleException(
          'No puedes desactivar tu perfil mientras tengas reservas confirmadas o pendientes con fecha de hoy o futura.'
        );
      }

      $this->roleRepo->setActive($owner->getIdRol(), false);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Tu perfil fue desactivado.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Owner/Form.php';
      return;
    }

    session_unset();
    session_destroy();

    redirect_to('auth', 'showLogin');
  }

  // =========================================================
  // FOTO DE PERFIL: prioriza borrar, luego archivo, luego URL.
  // =========================================================
  private function resolveOwnerProfileImage(Owner $owner): void
  {
    $current = $owner->getImageOwner();
    $newImage = $current;

    if (isset($_POST['removePhoto'])) {
      $newImage = '';
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $newImage = ImageStorageService::store(
        $_FILES['image'],
        'owners',
        'owner_' . $owner->getIdOwner()
      );
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
      ImageStorageService::deleteLocal($current);
      $owner->setImageOwner($newImage);
    }
  }
}