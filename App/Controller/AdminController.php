<?php

require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class AdminController
{
  private AdminService $adminService;
  private AuthService $authService;
  private InvoiceService $invoiceService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private RoleRepository $roleRepo;
  private AdminRepository $adminRepo;
  private ClientRepository $clientRepo;
  private OwnerRepository $ownerRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->adminService = new AdminService();
    $this->authService = new AuthService();
    $this->invoiceService = new InvoiceService();
    $this->bookingRepo = new BookingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->adminRepo = new AdminRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->ownerRepo = new OwnerRepository($connection);
  }

  // =========================================================
  // DASHBOARD (estadísticas)
  // =========================================================
  public function dashboard(): void
  {
    session_start();
    $this->requireAdmin();

    $yearMonth = date('Y-m');
    $bookings = $this->bookingRepo->findByMonth($yearMonth);
    $topVenues = $this->bookingRepo->topActiveVenues(5);
    $topServices = $this->detailRepo->topRequestedServices(5);

    require_once __DIR__ . '/../View/Admin/Dashboard.php';
  }

  // =========================================================
  // LISTAR USUARIOS (todos los roles)
  // =========================================================
  public function users(): void
  {
    session_start();
    $this->requireAdmin();

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
    session_start();
    $this->requireAdmin();

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    $this->adminService->activate($idRole);

    header('Location: ../../Public/index.php?controller=admin&action=users');
    exit;
  }

  // =========================================================
  // DESACTIVAR CUENTA
  // =========================================================
  public function deactivateUser(): void
  {
    session_start();
    $this->requireAdmin();

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $targetType = trim($_POST['type'] ?? $_GET['type'] ?? '');

    try {

      $this->adminService->desactivate($idRole, $targetType);

      header('Location: ../../Public/index.php?controller=admin&action=users');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=admin&action=users');
      exit;
    }
  }

  // =========================================================
  // RESERVAS DEL MES (para verificación de pagos)
  // =========================================================
  public function bookings(): void
  {
    session_start();
    $this->requireAdmin();

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));
    $bookings = $this->bookingRepo->findByMonth($yearMonth);

    require_once __DIR__ . '/../View/Admin/List.php';
  }

  // =========================================================
  // APROBAR PAGO DE UNA RESERVA
  // =========================================================
  public function approvePayment(): void
  {
    session_start();
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->invoiceService->approve($idBooking);

      header('Location: ../../Public/index.php?controller=admin&action=dashboard');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=admin&action=dashboard');
      exit;
    }
  }

  // =========================================================
  // RECHAZAR PAGO DE UNA RESERVA
  // =========================================================
  public function rejectPayment(): void
  {
    session_start();
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->invoiceService->reject($idBooking);

      header('Location: ../../Public/index.php?controller=admin&action=dashboard');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=admin&action=dashboard');
      exit;
    }
  }

  // =========================================================
  // LIMPIAR DATOS DE PRUEBA (botón cleaner del admin)
  // =========================================================
  public function cleanTestData(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=admin&action=dashboard');
      exit;
    }

    $connection = DataBase::getConnection();

    // Se vacían solo tablas de datos generados por el uso (prueba/demo).
    // NO se tocan datos maestros: roles, perfiles, ubicaciones,
    // métodos de pago ni configuración de comisión.
    $tables = [
      'tbeearning',
      'tbinvoice',
      'tbbookingticket',
      'tbbookingdetail',
      'tbbooking',
      'tbvenuerating',
      'tbservicerating',
      'tbpromotionservice',
      'tbpromotion',
      'tbservicehistory',
      'tbservice',
      'tbvenue',
      'tbnotification',
      'tbuserhistory',
      'tbownerhistory',
      'tbownerpayment',
    ];

    foreach ($tables as $table) {
      $connection->exec('DELETE FROM ' . $table);
    }

    header('Location: ../../Public/index.php?controller=admin&action=dashboard&cleaned=1');
    exit;
  }

  // =========================================================
  // MOSTRAR FORM PARA CREAR ADMINISTRADOR
  // =========================================================
  public function showAdminForm(): void
  {
    session_start();
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/AdminForm.php';
  }

  // =========================================================
  // CREAR ADMINISTRADOR
  // =========================================================
  public function createAdmin(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=admin&action=showAdminForm');
      exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      $this->authService->registerAdmin($name, $email, $password, $phoneNumber);

      header('Location: ../../Public/index.php?controller=admin&action=users&created=1');
      exit;
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
    session_start();
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/ClientForm.php';
  }

  // =========================================================
  // CREAR CLIENTE
  // =========================================================
  public function createClient(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=admin&action=showClientForm');
      exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      $this->authService->registerClient($name, $email, $password, $phoneNumber);

      header('Location: ../../Public/index.php?controller=admin&action=users&created=client');
      exit;
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
    session_start();
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/OwnerForm.php';
  }

  // =========================================================
  // CREAR PROPIETARIO
  // =========================================================
  public function createOwner(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=admin&action=showOwnerForm');
      exit;
    }

    $businessName = trim($_POST['ownerBusinessName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ownerFirstName = trim($_POST['ownerFirstName'] ?? '');
    $ownerLastName = trim($_POST['ownerLastName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $ownerAlias = trim($_POST['ownerAlias'] ?? '');
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');

    if ($ownerLastName === '') {
      $ownerLastName = null;
    }

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    if ($ownerAlias === '') {
      $ownerAlias = null;
    }

    if ($ownerIdentification === '') {
      $ownerIdentification = null;
    }

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

      header('Location: ../../Public/index.php?controller=admin&action=users&created=owner');
      exit;
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
    session_start();
    $this->requireAdmin();

    $idRole = (int) ($_GET['id'] ?? 0);
    $type = trim($_GET['type'] ?? '');

    $user = $this->loadUser($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      header('Location: ../../Public/index.php?controller=admin&action=users');
      exit;
    }

    require_once __DIR__ . '/../View/Admin/' . $this->viewFileFor($type);
  }

  // =========================================================
  // ACTUALIZAR USUARIO (admin|client|owner)
  // =========================================================
  public function updateUser(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=admin&action=users');
      exit;
    }

    $idRole = (int) ($_POST['id'] ?? 0);
    $type = trim($_POST['type'] ?? '');

    $user = $this->loadUser($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      header('Location: ../../Public/index.php?controller=admin&action=users');
      exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

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

      $user->setName($name);
      $user->setEmail($email);
      $user->setPhoneNumber($phoneNumber);

      if ($type === 'owner') {
        $ownerLastName = trim($_POST['ownerLastName'] ?? '');
        $ownerAlias = trim($_POST['ownerAlias'] ?? '');
        $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');

        if ($ownerIdentification !== '' && strtolower($ownerIdentification) !== strtolower($user->getIdentificationNumberOwner())) {
          $this->authService->validateIdentificationIsUnique($ownerIdentification);
        }

        $user->setLastNameOwner($ownerLastName);
        $user->setAliasOwner($ownerAlias);
        $user->setIdentificationNumberOwner($ownerIdentification);
      }

      $this->roleRepo->update($user);

      if ($password !== '') {
        $this->roleRepo->updatePassword($idRole, $password);
      }

      if ($type === 'owner') {
        $this->ownerRepo->updateProfile($user);
      }

      if (($_SESSION['type'] ?? null) === $type
          && ($_SESSION['user'] ?? null) instanceof Role
          && $_SESSION['user']->getIdRole() === $idRole) {
        $_SESSION['user'] = $user;
      }

      header('Location: ../../Public/index.php?controller=admin&action=users&updated=' . $type);
      exit;
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
    switch ($type) {
      case 'admin':
        return $this->adminRepo->findByRoleId($idRole);
      case 'client':
        return $this->clientRepo->findByRoleId($idRole);
      case 'owner':
        return $this->ownerRepo->findByRoleId($idRole);
    }

    return null;
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

  // =========================================================
  // GUARDIA: SOLO ADMIN AUTENTICADO
  // =========================================================
  private function requireAdmin(): void
  {
    if (($_SESSION['type'] ?? null) !== 'admin') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
