<?php

require_once __DIR__ . '/../Service/AdminService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class AdminController
{
  private AdminService $adminService;
  private InvoiceService $invoiceService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private AdminRepository $adminRepo;
  private ClientRepository $clientRepo;
  private OwnerRepository $ownerRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->adminService = new AdminService();
    $this->invoiceService = new InvoiceService();
    $this->bookingRepo = new BookingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
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
