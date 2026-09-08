<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/PaymentMethodService.php';
require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class InvoiceController extends BaseController
{
  private InvoiceService $invoiceService;
  private PaymentMethodService $paymentMethodService;
  private BookingService $bookingService;

  public function __construct()
  {
    $this->invoiceService = new InvoiceService();
    $this->paymentMethodService = new PaymentMethodService();
    $this->bookingService = new BookingService();
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE PAGO (cliente)
  // =========================================================
  public function showForm(): void
  {
    $this->requireClient();

    $idBooking = (int) ($_GET['bookingId'] ?? 0);
    $booking = $this->bookingService->getBooking($idBooking);

    if ($booking === null || $booking->getIdClient() !== $this->currentUser()->getIdClient()) {
      $this->redirect('booking', 'myBookings');
    }

    $paymentMethods = $this->paymentMethodService->findActive();
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];
    $details = $this->bookingService->getDetailsForBooking($idBooking);
    $venue = $this->bookingService->getVenue($booking->getIdLocal());

    require_once __DIR__ . '/../View/Invoice/Form.php';
  }

  // =========================================================
  // GENERAR FACTURA (pago de la reserva)
  // =========================================================
  public function generate(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $client = $this->currentUser();
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    try {

      $booking = $this->bookingService->getBooking($idBooking);

      if ($booking === null || $booking->getIdClient() !== $client->getIdClient()) {
        throw new BusinessRuleException('No tienes permiso sobre esta reserva.');
      }

      $this->invoiceService->generate($idBooking, $idPaymentMethod, $date);

      header('Location: ../../Public/index.php?controller=invoice&action=detail&bookingId=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $paymentMethods = $this->paymentMethodService->findActive();
      $totals = $this->bookingService->calculateTotals($idBooking);
      $total = $totals['total'];
      $details = $this->bookingService->getDetailsForBooking($idBooking);
      $booking = $this->bookingService->getBooking($idBooking);
      $venue = $booking !== null ? $this->bookingService->getVenue($booking->getIdLocal()) : null;

      require_once __DIR__ . '/../View/Invoice/Form.php';
    }
  }

  // =========================================================
  // DETALLE DE LA FACTURA DE UNA RESERVA
  // =========================================================
  public function detail(): void
  {
    $this->requireLogin();

    $idBooking = (int) ($_GET['bookingId'] ?? 0);
    $invoice = $this->invoiceService->findByBooking($idBooking);

    if ($invoice === null) {
      $this->redirect('booking', 'myBookings');
    }

    $booking = $this->bookingService->getBooking($idBooking);
    $type = $_SESSION['type'] ?? null;

    if ($type === 'client' && $booking->getIdClient() !== $this->currentUser()->getIdClient()) {
      $this->redirect('booking', 'myBookings');
    }

    $details = $this->bookingService->getDetailsForBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];

    $venue = $booking !== null ? $this->bookingService->getVenue($booking->getIdLocal()) : null;
    $client = $booking !== null ? $this->bookingService->getClient($booking->getIdClient()) : null;
    $paymentMethod = $this->paymentMethodService->findById($invoice->getIdPaymentMethod());

    $serviceMap = [];
    foreach ($details as $d) {
      if ($d->getIdLocalService() > 0
          && !isset($serviceMap[$d->getIdLocalService()])) {
        $service = $this->bookingService->getService($d->getIdLocalService());
        if ($service !== null) {
          $serviceMap[$d->getIdLocalService()] = $service;
        }
      }
    }

    require_once __DIR__ . '/../View/Invoice/Detail.php';
  }

  // =========================================================
  // LISTA DE FACTURAS (del cliente logueado)
  // =========================================================
  public function list(): void
  {
    $this->requireClient();

    $client = $this->currentUser();
    $bookings = $this->bookingService->getBookingsByClient($client->getIdClient());

    $invoices = [];
    foreach ($bookings as $booking) {
      $invoice = $this->invoiceService->findByBooking($booking->getIdBooking());
      if ($invoice !== null) {
        $invoices[] = $invoice;
      }
    }

    $paymentMethodById = [];
    foreach ($this->paymentMethodService->findAll() as $pm) {
      $paymentMethodById[$pm->getIdPaymentMethod()] = $pm->getPaymentMethod();
    }

    require_once __DIR__ . '/../View/Invoice/List.php';
  }
}