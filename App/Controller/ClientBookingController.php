<?php

require_once __DIR__ . '/../Service/BookingActionService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class ClientBookingController
{
  private BookingActionService $bookingActionService;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private BookingTicketRepository $ticketRepo;
  private ServiceService $serviceService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->bookingActionService = new BookingActionService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->serviceService = new ServiceService(new ServiceRepository($connection));
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE RESERVA (cliente)
  // =========================================================
  public function showForm(): void
  {
    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $venue = $this->venueRepo->findById($idVenue);

    if ($venue === null || !$venue->getIsActive()) {
      redirect_to('venue', 'catalog');
    }

    $services = $this->serviceService->findAvailableByLocal($idVenue);
    $bookedDates = $this->bookingRepo->bookedDatesByVenue($idVenue);

    require_once __DIR__ . '/../View/Booking/Form.php';
  }

  // =========================================================
  // CREAR UNA RESERVA (cliente)
  // =========================================================
  public function create(): void
  {
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $client = $_SESSION['user'];
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '') ?: null;
    $eventType = trim($_POST['eventType'] ?? '') ?: null;
    $eventDetail = trim($_POST['eventDetail'] ?? '') ?: null;

    try {
      $idBooking = $this->bookingActionService->create(
        $client->getIdClient(),
        (int) $client->getIdRol(),
        $idVenue,
        $date,
        $endDate,
        $eventType,
        $eventDetail
      );

      redirect_to('booking', 'detail', ['id' => $idBooking]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      $venue = $this->venueRepo->findById($idVenue);
      $services = $this->serviceService->findAvailableByLocal($idVenue);
      $bookedDates = $this->bookingRepo->bookedDatesByVenue($idVenue);

      require_once __DIR__ . '/../View/Booking/Form.php';
    }
  }

  // =========================================================
  // MIS RESERVAS (cliente)
  // =========================================================
  public function myBookings(): void
  {
    require_role('client');

    $client = $_SESSION['user'];
    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    $venueNames = [];
    $hasTicket = [];
    foreach ($bookings as $b) {
      $venue = $this->venueRepo->findById($b->getIdLocal());
      $venueNames[$b->getIdBooking()] = $venue !== null
        ? $venue->getNameVenue()
        : 'Local #' . $b->getIdLocal();
      $hasTicket[$b->getIdBooking()] = $this->ticketRepo->findByBooking($b->getIdBooking()) !== null;
    }

    require_once __DIR__ . '/../View/Booking/List.php';
  }

  // =========================================================
  // AGREGAR SERVICIO AL DETALLE (cliente)
  // =========================================================
  public function addLine(): void
  {
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('booking', 'detail', ['id' => (int) ($_GET['id'] ?? 0)]);
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idService = (int) ($_POST['serviceId'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    try {
      $this->bookingActionService->addLine($client->getIdClient(), $idBooking, $idService, $quantity);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Servicio agregado correctamente.'],
        'booking',
        'detail',
        ['id' => $idBooking]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      respond_or_redirect(['ok' => false, 'message' => $error], 'booking', 'detail', ['id' => $idBooking], 422);
    }
  }

  // =========================================================
  // CANCELAR RESERVA (cliente)
  // =========================================================
  public function cancel(): void
  {
    require_role('client');

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {
      $this->bookingActionService->cancel($client->getIdClient(), (int) $client->getIdRol(), $idBooking);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Reserva cancelada correctamente.'],
        'booking',
        'myBookings'
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      respond_or_redirect(['ok' => false, 'message' => $error], 'booking', 'detail', ['id' => $idBooking], 422);
    }
  }

  // =========================================================
  // SOLICITAR REEMBOLSO (cliente -> admin la valida)
  // =========================================================
  public function requestRefund(): void
  {
    require_role('client');

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    try {
      $this->bookingActionService->requestRefund(
        $client->getIdClient(),
        (int) $client->getIdRol(),
        $idBooking,
        $motivo
      );

      respond_or_redirect(
        ['ok' => true, 'message' => 'Solicitud de reembolso enviada.'],
        'booking',
        'detail',
        ['id' => $idBooking]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      respond_or_redirect(
        ['ok' => false, 'message' => $error],
        'booking',
        'detail',
        ['id' => $idBooking, 'error' => $error],
        422
      );
    }
  }

  // =========================================================
  // PAGAR CON FACTURA (cliente)
  // =========================================================
  public function pay(): void
  {
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('booking', 'detail', ['id' => (int) ($_GET['id'] ?? 0)]);
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);

    try {
      $this->bookingActionService->pay($client->getIdClient(), (int) $client->getIdRol(), $idBooking, $idPaymentMethod);

      redirect_to('booking', 'detail', ['id' => $idBooking]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      redirect_to('booking', 'detail', ['id' => $idBooking]);
    }
  }

  // =========================================================
  // SUBIR COMPROBANTE DE PAGO (cliente)
  // =========================================================
  public function uploadTicket(): void
  {
    require_role('client');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('booking', 'detail', ['id' => (int) ($_GET['id'] ?? 0)]);
    }

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);

    try {
      $this->bookingActionService->uploadTicket(
        $client->getIdClient(),
        (int) $client->getIdRol(),
        $idBooking,
        $idPaymentMethod
      );

      respond_or_redirect(
        ['ok' => true, 'message' => 'Comprobante subido. El propietario lo revisará para aprobar la reserva.'],
        'booking',
        'detail',
        ['id' => $idBooking]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      respond_or_redirect(['ok' => false, 'message' => $error], 'booking', 'detail', ['id' => $idBooking], 422);
    }
  }
}