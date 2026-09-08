<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/EarningService.php';
require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/BookingAdminService.php';
require_once __DIR__ . '/../Service/DemoDataService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/CommissionConfigService.php';
require_once __DIR__ . '/../Service/ProfileImageService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
require_once __DIR__ . '/../Model/Role.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class AdminController extends BaseController
{
  private AdminService $adminService;
  private AuthService $authService;
  private RoleSecurityService $roleSecurityService;
  private InvoiceService $invoiceService;
  private EarningService $earningService;
  private BookingService $bookingService;
  private BookingAdminService $bookingAdminService;
  private NotificationService $notificationService;
  private CommissionConfigService $commissionConfigService;
  private DemoDataService $demoDataService;
  private ProfileImageService $profileImageService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->adminService = new AdminService();
    $this->authService = new AuthService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->invoiceService = new InvoiceService();
    $this->earningService = new EarningService($connection);
    $this->bookingService = new BookingService();
    $this->bookingAdminService = new BookingAdminService($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
    $this->commissionConfigService = new CommissionConfigService(
      new CommissionConfigRepository($connection)
    );
    $this->demoDataService = new DemoDataService($connection);
    $this->profileImageService = new ProfileImageService();
  }

  // =========================================================
  // DASHBOARD (estadísticas)
  // =========================================================
  public function dashboard(): void
  {
    $this->requireAdmin();

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $dashboard = $this->adminService->getDashboardData($yearMonth);
    $bookings = $dashboard['bookings'];
    $topVenues = $dashboard['topVenues'];
    $topServices = $dashboard['topServices'];
    $stateCounts = $dashboard['stateCounts'];
    $occupancy = $dashboard['occupancy'];
    $clientStats = $dashboard['clientStats'];
    $topClients = $dashboard['topClients'];
    $venueAvg = $dashboard['venueAvg'];
    $venueReviews = $dashboard['venueReviews'];
    $serviceAvg = $dashboard['serviceAvg'];
    $serviceReviews = $dashboard['serviceReviews'];

    $monthStats = $this->earningService->summarizeByMonth($yearMonth);
    $config = $this->commissionConfigService->getActive();
    $commissionPct = $config->getPercentage();
    $taxPct = $config->getTax();
    $prevMonth = date('Y-m', strtotime($yearMonth . '-01 first day of last month'));
    $nextMonth = date('Y-m', strtotime($yearMonth . '-01 first day of next month'));

    require_once __DIR__ . '/../View/Admin/Dashboard.php';
  }

  // =========================================================
  // LISTAR USUARIOS (todos los roles)
  // =========================================================
  public function users(): void
  {
    $this->requireAdmin();

    $admins = $this->adminService->findAllAdmins();
    $clients = $this->adminService->findAllClients();
    $owners = $this->adminService->findAllOwners();

    require_once __DIR__ . '/../View/Admin/List.php';
  }

  // =========================================================
  // ACTIVAR CUENTA
  // =========================================================
  public function activateUser(): void
  {
    $this->requireAdmin();

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    $this->adminService->activate($idRole);

    $this->redirect('admin', 'users');
  }

  // =========================================================
  // DESACTIVAR CUENTA
  // =========================================================
  public function deactivateUser(): void
  {
    $this->requireAdmin();

    $idRole = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $targetType = trim($_POST['type'] ?? $_GET['type'] ?? '');

    try {

      $this->adminService->desactivate($idRole, $targetType);

      $this->redirect('admin', 'users');
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      $this->redirect('admin', 'users');
    }
  }

  // =========================================================
  // RESERVAS DEL MES (para verificación de pagos)
  // =========================================================
  public function bookings(): void
  {
    $this->requireAdmin();

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $bookings = $this->adminService->getBookingsByMonthWithDetails($yearMonth);
    $history = $this->adminService->getBookingHistoryAll();
    $refundsPending = $this->adminService->getPendingRefunds();
    $prevMonth = date('Y-m', strtotime($yearMonth . '-01 first day of last month'));
    $nextMonth = date('Y-m', strtotime($yearMonth . '-01 first day of next month'));

    require_once __DIR__ . '/../View/Admin/List.php';
  }

  // =========================================================
  // HISTORIAL GLOBAL DE ACCIONES DE USUARIOS
  // (tbuserhistory: VIEW, SEARCH, BOOKING, PURCHASE, APPROVE...)
  // =========================================================
  public function userHistory(): void
  {
    $this->requireAdmin();

    $history = $this->adminService->getAllUserHistory();

    require_once __DIR__ . '/../View/Admin/UserHistory.php';
  }

  // =========================================================
  // MI PERFIL (admin autenticado)
  // =========================================================
  public function profile(): void
  {
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/Profile.php';
  }

  // =========================================================
  // ACTUALIZAR MI PERFIL (datos base, foto y contraseña)
  // =========================================================
  public function updateProfile(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $admin = $_SESSION['user'];

    $currentEmail = $admin->getEmail();
    $currentPhone = $admin->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if ($name === '') {
        throw new BusinessRuleException('El nombre es obligatorio.');
      }

      if (strtolower($email) !== strtolower($admin->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      // Cambio de contraseña: solo con confirmación de la actual.
      $hasCurrent = trim($currentPassword) !== '';
      $hasNew = trim($newPassword) !== '';

      if ($hasCurrent || $hasNew) {
        if (!$hasCurrent || !$hasNew) {
          throw new BusinessRuleException('Para cambiar tu contraseña debes escribir la contraseña actual y la nueva.');
        }

        if (!password_verify($currentPassword, $admin->getPassword())) {
          throw new BusinessRuleException('La contraseña actual no es correcta.');
        }

        $this->authService->validatePasswordStrength($newPassword);
      }

      $admin->setName($name);
      $admin->setEmail($email);
      $admin->setPhoneNumber($phoneNumber);

      $admin->setImageAdmin(
        $this->profileImageService->resolveAndPersist(
          $admin->getImageAdmin(),
          $_POST,
          $_FILES,
          'resource/admins/',
          'admin_'
        )
      );

      $this->adminService->updateAdminProfile($admin);

      if ($hasCurrent && $hasNew) {
        $this->roleSecurityService->changePassword($admin->getIdRol(), $newPassword);
      }

      // Seguridad: auditar y alertar ante cambios de credenciales.
      $this->roleSecurityService->recordPhoneChange($admin->getIdRol(), $currentPhone, $phoneNumber);
      $this->roleSecurityService->recordEmailChange($admin->getIdRol(), $currentEmail, $email);

      $_SESSION['user'] = $admin;

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Perfil actualizado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=admin&action=profile&updated=1');
      exit;
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
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $admin = $_SESSION['user'];

    $this->profileImageService->deleteStoredFile($admin->getImageAdmin(), 'resource/admins/');
    $admin->setImageAdmin('');

    $this->adminService->updateAdminProfile($admin);

    $_SESSION['user'] = $admin;

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Foto de perfil eliminada.']);
    }

    header('Location: ../../Public/index.php?controller=admin&action=profile&removed=1');
    exit;
  }

  // =========================================================
  // DESACTIVAR MI CUENTA (último admin activo protegido)
  // =========================================================
  public function deactivateAccount(): void
  {
    $this->requireAdmin();

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

    header('Location: ../../Public/index.php?controller=auth&action=showLogin');
    exit;
  }

  // =========================================================
  // APROBAR PAGO DE UNA RESERVA
  // =========================================================
  public function approvePayment(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->invoiceService->approve($idBooking);

      $totals = $this->bookingService->calculateTotals($idBooking);
      $this->earningService->recordEarning($idBooking, $totals, $this->currentRoleId());

      $approvedBooking = $this->adminService->getBookingById($idBooking);
      if ($approvedBooking !== null) {
        $this->notificationService->notifyClientPaymentApproved((int) $approvedBooking->getIdClient(), (int) $idBooking);

        $approvedVenue = $this->adminService->getVenueById($approvedBooking->getIdLocal());
        if ($approvedVenue !== null) {
          $this->notificationService->notifyOwnerPaymentReceived((int) $approvedVenue->getIdOwner(), (int) $idBooking);
        }
      }

      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=payment_approved');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($error));
      exit;
    }
  }

  // =========================================================
  // RECHAZAR PAGO DE UNA RESERVA
  // =========================================================
  public function rejectPayment(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->invoiceService->reject($idBooking);

      $rejectedBooking = $this->adminService->getBookingById($idBooking);
      if ($rejectedBooking !== null) {
        $this->notificationService->notifyClientPaymentRejected((int) $rejectedBooking->getIdClient(), (int) $idBooking);
      }

      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=payment_rejected');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($error));
      exit;
    }
  }

  // =========================================================
  // DETALLE DE UNA RESERVA (panel del Admin)
  // =========================================================
  public function bookingDetail(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_GET['id'] ?? 0);
    $booking = $this->adminService->getBookingById($idBooking);

    if ($booking === null) {
      $this->redirect('admin', 'bookings');
    }

    $client = $this->adminService->getClientByPk($booking->getIdClient());
    $venue = $this->adminService->getVenueById($booking->getIdLocal());

    $lines = $this->adminService->getDetailLinesByBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);

    $invoice = $this->invoiceService->findByBooking($idBooking);
    $ticket = $this->adminService->getTicketByBooking($idBooking);
    $earning = $this->earningService->findByBooking($idBooking);
    $refundRequest = $this->adminService->getRefundByBooking($idBooking);
    $history = $this->adminService->getBookingHistoryByBooking($idBooking);
    $venues = $this->adminService->getActiveVenues();
    $bookedDates = $this->adminService->getBookedDates($booking->getIdLocal());

    require_once __DIR__ . '/../View/Admin/BookingDetail.php';
  }

  // =========================================================
  // CANCELAR RESERVA (admin)
  // =========================================================
  public function cancelBooking(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentRoleId();

    try {
      $cancelledBooking = $this->bookingAdminService->cancel($idBooking, $adminRoleId, $note);
      if ($cancelledBooking !== null) {
        $this->notificationService->notifyClientBookingCancelled((int) $cancelledBooking->getIdClient(), (int) $idBooking);
      }
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=cancelled');
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // =========================================================
  // REPROGRAMAR (cambiar fecha) — admin
  // =========================================================
  public function rescheduleBooking(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? 0);
    $newDate = trim($_POST['date'] ?? '');
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentRoleId();

    try {
      $rescheduledBooking = $this->bookingAdminService->reschedule($idBooking, $adminRoleId, $newDate, $note);
      if ($rescheduledBooking !== null) {
        $this->notificationService->notifyClientBookingRescheduled((int) $rescheduledBooking->getIdClient(), (int) $idBooking);
      }
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=rescheduled');
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // =========================================================
  // CAMBIAR LOCAL — admin
  // =========================================================
  public function changeBookingVenue(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? 0);
    $newVenueId = (int) ($_POST['venueId'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentRoleId();

    try {
      $venueChangedBooking = $this->bookingAdminService->changeVenue($idBooking, $adminRoleId, $newVenueId, $note);
      if ($venueChangedBooking !== null) {
        $this->notificationService->notifyClientVenueChanged((int) $venueChangedBooking->getIdClient(), (int) $idBooking);
      }
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=venue_changed');
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // =========================================================
  // APROBAR REEMBOLSO (admin valida la solicitud del cliente)
  // =========================================================
  public function refundBooking(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? 0);
    $refundRequestId = (int) ($_POST['refundId'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentRoleId();

    try {
      $refundedBooking = $this->bookingAdminService->approveRefund($idBooking, $adminRoleId, $refundRequestId, $note);
      if ($refundedBooking !== null) {
        $this->notificationService->notifyClientRefundApproved((int) $refundedBooking->getIdClient(), (int) $idBooking);
      }
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=refunded');
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // =========================================================
  // RECHAZAR SOLICITUD DE REEMBOLSO
  // =========================================================
  public function rejectRefundBooking(): void
  {
    $this->requireAdmin();

    $idBooking = (int) ($_POST['id'] ?? 0);
    $refundRequestId = (int) ($_POST['refundId'] ?? 0);
    $adminRoleId = $this->currentRoleId();

    try {
      $refundRejectedBooking = $this->bookingAdminService->rejectRefund($refundRequestId, $adminRoleId);
      if ($refundRejectedBooking !== null) {
        $this->notificationService->notifyClientRefundRejected((int) $refundRejectedBooking->getIdClient(), (int) $idBooking);
      }
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&msg=refund_rejected');
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ../../Public/index.php?controller=admin&action=bookingDetail&id=' . $idBooking . '&error=' . urlencode($e->getMessage()));
      exit;
    }
  }

  // =========================================================
  // CONFIGURACIÓN DE COMISIÓN E IVA (solo admin)
  // =========================================================
  public function commissionConfig(): void
  {
    $this->requireAdmin();

    $config = $this->commissionConfigService->getActive();

    require_once __DIR__ . '/../View/Admin/CommissionConfig.php';
  }

  // =========================================================
  // GUARDAR COMISIÓN E IVA (solo admin) — AJAX o POST normal
  // =========================================================
  public function saveCommissionConfig(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'commissionConfig');
    }

    $percentage = (float) trim($_POST['percentage'] ?? '');
    $tax        = (float) trim($_POST['tax'] ?? '');

    try {

      $this->commissionConfigService->setPercentage($percentage);
      $this->commissionConfigService->setTax($tax);

      $this->notificationService->notifyCommissionConfigChanged($percentage, $tax);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Comisión e IVA actualizados correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=admin&action=commissionConfig&saved=1');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=admin&action=commissionConfig&error=' . urlencode($error));
      exit;
    }
  }

  // =========================================================
  // LIMPIAR DATOS DE PRUEBA (botón cleaner del admin)
  // =========================================================
  public function cleanTestData(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'dashboard');
    }

    $this->demoDataService->cleanGeneratedData();

    header('Location: ../../Public/index.php?controller=admin&action=dashboard&cleaned=1');
    exit;
  }

  // =========================================================
  // MOSTRAR FORM PARA CREAR ADMINISTRADOR
  // =========================================================
  public function showAdminForm(): void
  {
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/AdminForm.php';
  }

  // =========================================================
  // CREAR ADMINISTRADOR
  // =========================================================
  public function createAdmin(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'showAdminForm');
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
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/ClientForm.php';
  }

  // =========================================================
  // CREAR CLIENTE
  // =========================================================
  public function createClient(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'showClientForm');
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
    $this->requireAdmin();

    require_once __DIR__ . '/../View/Admin/OwnerForm.php';
  }

  // =========================================================
  // CREAR PROPIETARIO
  // =========================================================
  public function createOwner(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'showOwnerForm');
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
    $this->requireAdmin();

    $idRole = (int) ($_GET['id'] ?? 0);
    $type = trim($_GET['type'] ?? '');

    $user = $this->adminService->findUserByRoleId($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      $this->redirect('admin', 'users');
    }

    require_once __DIR__ . '/../View/Admin/' . $this->viewFileFor($type);
  }

  // =========================================================
  // ACTUALIZAR USUARIO (admin|client|owner)
  // =========================================================
  public function updateUser(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('admin', 'users');
    }

    $idRole = (int) ($_POST['id'] ?? 0);
    $type = trim($_POST['type'] ?? '');

    $user = $this->adminService->findUserByRoleId($type, $idRole);

    if ($user === null || !$this->viewFileFor($type)) {
      $this->redirect('admin', 'users');
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

      $currentUserEmail = $user->getEmail();
      $currentUserPhone = $user->getPhoneNumber();

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

      $this->adminService->updateRole($user);

      if ($password !== '') {
        $this->roleSecurityService->adminResetPassword($idRole, $password);
      }

      // Seguridad: auditar y alertar ante cambios de credenciales.
      $this->roleSecurityService->recordPhoneChange($idRole, $currentUserPhone, $phoneNumber);
      $this->roleSecurityService->recordEmailChange($idRole, $currentUserEmail, $email);

      if ($type === 'owner') {
        $this->adminService->updateOwnerProfile($user);
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