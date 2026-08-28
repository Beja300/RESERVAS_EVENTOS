<?php

require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/DetailService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/ClientService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class BookingController
{
  private BookingService $bookingService;
  private DetailService $detailService;
  private InvoiceService $invoiceService;
  private ClientService $clientService;
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private ServiceRepository $serviceRepo;
  private VenueRepository $venueRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->bookingService = new BookingService();
    $this->detailService = new DetailService($connection);
    $this->invoiceService = new InvoiceService();
    $this->clientService = new ClientService();
    $this->serviceService = new ServiceService(new ServiceRepository($connection));
    $this->ownerService = new OwnerService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->serviceRepo = new ServiceRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
  }

  // =========================================================
  // CREAR UNA RESERVA (cliente)
  // =========================================================
  public function create(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $client = $_SESSION['user'];
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $eventType = trim($_POST['eventType'] ?? '') ?: null;

    try {

      $this->clientService->assertCanBook($client->getIdClient());

      $idBooking = $this->bookingService->createBooking(
        $client->getIdClient(),
        $idVenue,
        $date,
        $eventType
      );

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $venue = $this->venueRepo->findById($idVenue);
      $services = $this->serviceService->findAvailableByLocal($idVenue);

      require_once __DIR__ . '/../View/Booking/Form.php';
    }
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE RESERVA (cliente)
  // =========================================================
  public function showForm(): void
  {
    session_start();

    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $venue = $this->venueRepo->findById($idVenue);

    if ($venue === null || !$venue->getIsActive()) {
      header('Location: ../../Public/index.php?controller=venue&action=catalog');
      exit;
    }

    $services = $this->serviceService->findAvailableByLocal($idVenue);

    require_once __DIR__ . '/../View/Booking/Form.php';
  }

  // =========================================================
  // MIS RESERVAS (cliente)
  // =========================================================
  public function myBookings(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];
    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    require_once __DIR__ . '/../View/Booking/List.php';
  }

  // =========================================================
  // DETALLE DE UNA RESERVA (cliente/owner)
  // =========================================================
  public function detail(): void
  {
    session_start();

    $idBooking = (int) ($_GET['id'] ?? 0);
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      header('Location: ../../Public/index.php?controller=venue&action=catalog');
      exit;
    }

    $type = $_SESSION['type'] ?? null;

    if ($type === 'client') {
      $client = $_SESSION['user'];
      if ($booking->getIdClient() !== $client->getIdClient()) {
        header('Location: ../../Public/index.php?controller=booking&action=myBookings');
        exit;
      }
    } elseif ($type === 'owner') {
      $owner = $_SESSION['user'];
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());
    } else {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }

    $details = $this->detailRepo->findByBooking($idBooking);
    $total = $this->bookingService->calculateTotal($idBooking);
    $venue = $this->venueRepo->findById($booking->getIdLocal());

    require_once __DIR__ . '/../View/Booking/Detail.php';
  }

  // =========================================================
  // AGREGAR SERVICIO AL DETALLE (cliente)
  // =========================================================
  public function addLine(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idService = (int) ($_POST['serviceId'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->detailService->addLine($idBooking, $idService, $quantity);

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // CONFIRMAR RESERVA (cliente)
  // =========================================================
  public function confirm(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->bookingService->confirm($idBooking);

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // CANCELAR RESERVA (cliente)
  // =========================================================
  public function cancel(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->bookingService->cancel($idBooking);

      header('Location: ../../Public/index.php?controller=booking&action=myBookings');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // GENERAR PAGO / FACTURA (cliente)
  // =========================================================
  public function pay(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->invoiceService->generate($idBooking, $idPaymentMethod, date('Y-m-d'));

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // RESERVAS DE UN LOCAL (owner)
  // =========================================================
  public function venueBookings(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $bookings = $this->bookingRepo->findByVenue($idVenue);

      require_once __DIR__ . '/../View/Booking/List.php';
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=venue&action=list');
      exit;
    }
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
