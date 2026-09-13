<?php

require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminUserController
{
  private AdminService $adminService;
  private AuthService $authService;
  private RoleSecurityService $roleSecurityService;
  private RoleRepository $roleRepo;
  private AdminRepository $adminRepo;
  private ClientRepository $clientRepo;
  private OwnerRepository $ownerRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->adminService = new AdminService();
    $this->authService = new AuthService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->roleRepo = new RoleRepository($connection);
    $this->adminRepo = new AdminRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->ownerRepo = new OwnerRepository($connection);
  }

  // =========================================================
  // LISTAR USUARIOS (todos los roles)
  // =========================================================
  public function users(): void
  {
    require_role('admin');

    $admins = $this->adminRepo->findAll();
    $clients = $this->clientRepo->findAll();
    $owners = $this->ownerRepo->findAll();

    require_once __DIR__ . '/../View/Admin/List.php';
  }

  // =========================================================
  // ACTIVAR CUENTA
  // =========================================================
  public function activateUser(): void
  {
    require_role('admin');

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {
      $this->adminService->activate($idRole);
      redirect_to('admin', 'users');
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      redirect_to('admin', 'users', ['error' => $error]);
    }
  }

  // =========================================================
  // DESACTIVAR CUENTA
  // =========================================================
  public function deactivateUser(): void
  {
    require_role('admin');

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $targetType = trim($_POST['type'] ?? $_GET['type'] ?? '');

    try {
      $this->adminService->desactivate($idRole, $targetType);
      redirect_to('admin', 'users');
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      redirect_to('admin', 'users', ['error' => $error]);
    }
  }

  // =========================================================
  // MOSTRAR FORM PARA CREAR ADMINISTRADOR
  // =========================================================
  public function showAdminForm(): void
  {
    require_role('admin');

    require_once __DIR__ . '/../View/Admin/AdminForm.php';
  }

  // =========================================================
  // CREAR ADMINISTRADOR
  // =========================================================
  public function createAdmin(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'showAdminForm');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;

    try {
      $this->authService->registerAdmin($name, $email, $password, $phoneNumber);

      redirect_to('admin', 'users', ['created' => 1]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Admin/AdminForm.php';
    }
  }

  // =========================================================
  // MOSTRAR FORM PARA CREAR CLIENTE
  // =========================================================
  public function showClientForm(): void
  {
    require_role('admin');

    require_once __DIR__ . '/../View/Admin/ClientForm.php';
  }

  // =========================================================
  // CREAR CLIENTE
  // =========================================================
  public function createClient(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'showClientForm');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;

    try {
      $this->authService->registerClient($name, $email, $password, $phoneNumber);

      redirect_to('admin', 'users', ['created' => 'client']);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Admin/ClientForm.php';
    }
  }

  // =========================================================
  // MOSTRAR FORM PARA CREAR PROPIETARIO
  // =========================================================
  public function showOwnerForm(): void
  {
    require_role('admin');

    require_once __DIR__ . '/../View/Admin/OwnerForm.php';
  }

  // =========================================================
  // CREAR PROPIETARIO
  // =========================================================
  public function createOwner(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'showOwnerForm');
    }

    $businessName = trim($_POST['ownerBusinessName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ownerFirstName = trim($_POST['ownerFirstName'] ?? '');
    $ownerLastName = trim($_POST['ownerLastName'] ?? '') ?: null;
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;
    $ownerAlias = trim($_POST['ownerAlias'] ?? '') ?: null;
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '') ?: null;

    try {
      if ($businessName === '') {
        throw new BusinessRuleException('El nombre de negocio es obligatorio.');
      }

      $this->authService->registerOwner(
        $businessName,
        $email,
        $password,
        $ownerFirstName,
        $ownerLastName,
        $phoneNumber,
        $ownerAlias,
        $ownerIdentification
      );

      redirect_to('admin', 'users', ['created' => 'owner']);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Admin/OwnerForm.php';
    }
  }

  // =========================================================
  // MOSTRAR FORM PARA EDITAR USUARIO (admin|client|owner)
  // =========================================================
  public function showEditForm(): void
  {
    require_role('admin');

    $idRole = (int) ($_GET['id'] ?? 0);
    $type = trim($_GET['type'] ?? '');

    $user = $this->loadUser($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      redirect_to('admin', 'users');
    }

    require_once __DIR__ . '/../View/Admin/' . $this->viewFileFor($type);
  }

  // =========================================================
  // ACTUALIZAR USUARIO (admin|client|owner)
  // =========================================================
  public function updateUser(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'users');
    }

    $idRole = (int) ($_POST['id'] ?? 0);
    $type = trim($_POST['type'] ?? '');

    $user = $this->loadUser($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      redirect_to('admin', 'users');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '') ?: null;
    $password = $_POST['password'] ?? '';

    try {
      if ($name === '') {
        throw new BusinessRuleException('El nombre es obligatorio.');
      }

      if (strtolower($email) !== strtolower($user->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      if ($password !== '') {
        $this->authService->validatePasswordStrength($password);
      }

      $currentUserEmail = $user->getEmail();
      $currentUserPhone = $user->getPhoneNumber();

      $user->setName($name);
      $user->setEmail($email);
      $user->setPhoneNumber($phoneNumber);

      if ($type === 'owner') {
        $ownerLastName = trim($_POST['ownerLastName'] ?? '');
        $ownerAlias = trim($_POST['ownerAlias'] ?? '');
        $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');

        if ($ownerIdentification !== ''
            && strtolower($ownerIdentification) !== strtolower($user->getIdentificationNumberOwner())) {
          $this->authService->validateIdentificationIsUnique($ownerIdentification);
        }

        $user->setLastNameOwner($ownerLastName);
        $user->setAliasOwner($ownerAlias);
        $user->setIdentificationNumberOwner($ownerIdentification);
      }

      $this->roleRepo->update($user);

      if ($password !== '') {
        $this->roleSecurityService->adminResetPassword($idRole, $password);
      }

      $this->roleSecurityService->recordPhoneChange($idRole, $currentUserPhone, $phoneNumber);
      $this->roleSecurityService->recordEmailChange($idRole, $currentUserEmail, $email);

      if ($type === 'owner') {
        $this->ownerRepo->updateProfile($user);
      }

      if (($_SESSION['type'] ?? null) === $type
          && ($_SESSION['user'] ?? null) instanceof Role
          && $_SESSION['user']->getIdRole() === $idRole) {
        $_SESSION['user'] = $user;
      }

      redirect_to('admin', 'users', ['updated' => $type]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Admin/' . $this->viewFileFor($type);
    }
  }

  // =========================================================
  // CARGAR USUARIO POR TIPO E ID DE ROL
  // =========================================================
  private function loadUser(string $type, int $idRole): Admin|Client|Owner|null
  {
    return match ($type) {
      'admin'  => $this->adminRepo->findByRoleId($idRole),
      'client' => $this->clientRepo->findByRoleId($idRole),
      'owner'  => $this->ownerRepo->findByRoleId($idRole),
      default  => null,
    };
  }

  // =========================================================
  // MAPEO TIPO -> VISTA DE EDICIÓN
  // =========================================================
  private function viewFileFor(string $type): ?string
  {
    return [
      'admin'  => 'AdminEdit.php',
      'client' => 'ClientEdit.php',
      'owner'  => 'OwnerEdit.php',
    ][$type] ?? null;
  }
}