<?php

require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/PaymentMethodService.php';
require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/EarningService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class InvoiceController
{
  private InvoiceService $invoiceService;
  private PaymentMethodService $paymentMethodService;
  private BookingService $bookingService;
  private EarningService $earningService;
  private InvoiceRepository $invoiceRepo;
  private BookingRepository $bookingRepo;
  private PaymentMethodRepository $paymentMethodRepo;
  private DetailRepository $detailRepo;
  private VenueRepository $venueRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->invoiceService = new InvoiceService();
    $this->paymentMethodService = new PaymentMethodService();
    $this->bookingService = new BookingService();
    $this->earningService = new EarningService($connection);
    $this->invoiceRepo = new InvoiceRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE PAGO (cliente)
  // =========================================================
  public function showForm(): void
  {
    session_start();
    $this->requireClient();

    $idBooking = (int) ($_GET['bookingId'] ?? 0);
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null || $booking->getIdClient() !== $this->currentClient()->getIdClient()) {
      header('Location: ../../Public/index.php?controller=booking&action=myBookings');
      exit;
    }

    $paymentMethods = $this->paymentMethodRepo->findActive();
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];
    $details = $this->detailRepo->findByBooking($idBooking);

    require_once __DIR__ . '/../View/Invoice/Form.php';
  }

  // =========================================================
  // GENERAR FACTURA (pago de la reserva)
  // =========================================================
  public function generate(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    try {

      $booking = $this->bookingRepo->findById($idBooking);

      if ($booking === null || $booking->getIdClient() !== $client->getIdClient()) {
        throw new BusinessRuleException('No tienes permiso sobre esta reserva.');
      }

      $this->invoiceService->generate($idBooking, $idPaymentMethod, $date);

      header('Location: ../../Public/index.php?controller=invoice&action=detail&bookingId=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $paymentMethods = $this->paymentMethodRepo->findActive();
      $totals = $this->bookingService->calculateTotals($idBooking);
      $total = $totals['total'];
      $details = $this->detailRepo->findByBooking($idBooking);

      require_once __DIR__ . '/../View/Invoice/Form.php';
    }
  }

  // =========================================================
  // DETALLE DE LA FACTURA DE UNA RESERVA
  // =========================================================
  public function detail(): void
  {
    session_start();
    $this->requireLogin();

    $idBooking = (int) ($_GET['bookingId'] ?? 0);
    $invoice = $this->invoiceRepo->findByBooking($idBooking);

    if ($invoice === null) {
      header('Location: ../../Public/index.php?controller=booking&action=myBookings');
      exit;
    }

    $booking = $this->bookingRepo->findById($idBooking);
    $type = $_SESSION['type'] ?? null;

    if ($type === 'client' && $booking->getIdClient() !== $this->currentClient()->getIdClient()) {
      header('Location: ../../Public/index.php?controller=booking&action=myBookings');
      exit;
    }

    $details = $this->detailRepo->findByBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];

    $venue = $booking !== null ? $this->venueRepo->findById($booking->getIdLocal()) : null;

    $earning = ($type === 'admin' || $type === 'owner')
      ? $this->earningService->findByBooking($idBooking)
      : null;

    require_once __DIR__ . '/../View/Invoice/Detail.php';
  }

  // =========================================================
  // LISTA DE FACTURAS (del cliente logueado)
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];
    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    $invoices = [];
    foreach ($bookings as $booking) {
      $invoice = $this->invoiceRepo->findByBooking($booking->getIdBooking());
      if ($invoice !== null) {
        $invoices[] = $invoice;
      }
    }

    require_once __DIR__ . '/../View/Invoice/List.php';
  }

  // =========================================================
  // GUARDIAS
  // =========================================================
  private function currentClient()
  {
    return $_SESSION['user'] ?? null;
  }

  private function requireLogin(): void
  {
    if (($_SESSION['type'] ?? null) === null) {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }

  private function requireClient(): void
  {
    if (($_SESSION['type'] ?? null) !== 'client') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
