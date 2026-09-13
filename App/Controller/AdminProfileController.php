<?php

require_once __DIR__ . '/../Service/ProfileService.php';
require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/ImageStorageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminProfileController
{
  private ProfileService $profileService;
  private AdminService $adminService;
  private RoleRepository $roleRepo;
  private AdminRepository $adminRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->profileService = new ProfileService();
    $this->adminService = new AdminService();
    $this->roleRepo = new RoleRepository($connection);
    $this->adminRepo = new AdminRepository($connection);
  }

  // =========================================================
  // MI PERFIL (admin autenticado)
  // =========================================================
  public function profile(): void
  {
    require_role('admin');

    require_once __DIR__ . '/../View/Admin/Profile.php';
  }

  // =========================================================
  // ACTUALIZAR MI PERFIL (datos base, foto y contraseña)
  // =========================================================
  public function updateProfile(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $admin = $_SESSION['user'];

    $currentEmail = $admin->getEmail();
    $currentPhone = $admin->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    try {
      if ($name === '') {
        throw new BusinessRuleException('El nombre es obligatorio.');
      }

      $this->profileService->validateUniqueEmail($currentEmail, $email);
      $this->profileService->validatePhone($phoneNumber);
      $this->profileService->changePasswordIfRequested($admin, $currentPassword, $newPassword);

      $admin->setName($name);
      $admin->setEmail($email);
      $admin->setPhoneNumber($phoneNumber);

      $this->resolveAdminProfileImage($admin);

      $this->profileService->recordCredentialChanges($admin, $currentPhone, $phoneNumber, $currentEmail, $email);

      $this->roleRepo->update($admin);
      $this->adminRepo->updateProfile($admin);

      $_SESSION['user'] = $admin;

      respond_or_redirect(
        ['ok' => true, 'message' => 'Perfil actualizado correctamente.'],
        'admin',
        'profile',
        ['updated' => 1]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Admin/Profile.php';
    }
  }

  // =========================================================
  // ELIMINAR MI FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $admin = $_SESSION['user'];

    ImageStorageService::deleteLocal($admin->getImageAdmin());
    $admin->setImageAdmin('');

    $this->adminRepo->updateProfile($admin);

    $_SESSION['user'] = $admin;

    respond_or_redirect(
      ['ok' => true, 'message' => 'Foto de perfil eliminada.'],
      'admin',
      'profile',
      ['removed' => 1]
    );
  }

  // =========================================================
  // DESACTIVAR MI CUENTA (último admin activo protegido)
  // =========================================================
  public function deactivateAccount(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $admin = $_SESSION['user'];

    try {
      $this->adminService->desactivate($admin->getIdRol(), 'admin');

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Tu cuenta fue desactivada.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Admin/Profile.php';
      return;
    }

    session_unset();
    session_destroy();

    redirect_to('auth', 'showLogin');
  }

  // =========================================================
  // FOTO DE PERFIL: prioriza archivo, luego URL.
  // =========================================================
  private function resolveAdminProfileImage(Admin $admin): void
  {
    $current = $admin->getImageAdmin();
    $newImage = $current;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $newImage = ImageStorageService::store(
        $_FILES['image'],
        'admins',
        'admin_' . $admin->getIdAdmin()
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
      $admin->setImageAdmin($newImage);
    }
  }
}