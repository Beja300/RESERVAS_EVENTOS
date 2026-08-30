<?php

require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/DetailService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/ClientService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/OwnerPaymentService.php';
require_once __DIR__ . '/../Service/BookingTicketService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class BookingController
{
  private BookingService $bookingService;
  private DetailService $detailService;
  private InvoiceService $invoiceService;
  private ClientService $clientService;
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private BookingTicketService $bookingTicketService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private ServiceRepository $serviceRepo;
  private VenueRepository $venueRepo;
  private BookingTicketRepository $ticketRepo;
  private BookingRefundRepository $refundRepo;
  private PaymentMethodRepository $paymentMethodRepo;
  private OwnerPaymentRepository $ownerPaymentRepo;
  private OwnerPaymentService $ownerPaymentService;

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
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->refundRepo = new BookingRefundRepository($connection);
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
    $this->ownerPaymentRepo = new OwnerPaymentRepository($connection);
    $this->ownerPaymentService = new OwnerPaymentService($connection);
    $this->bookingTicketService = new BookingTicketService($connection);
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
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];
    $venue = $this->venueRepo->findById($booking->getIdLocal());
    $ticket = $this->ticketRepo->findByBooking($idBooking);
    $paymentMethods = $this->paymentMethodRepo->findActive();

    // Métodos de pago configurados por el dueño de ESTE local.
    $ownerPaymentMethods = [];
    if ($venue !== null) {
      foreach ($this->ownerPaymentService->findByOwner($venue->getIdOwner()) as $op) {
        if (!$op->getIsActive()) {
          continue;
        }
        $ownerPaymentMethods[] = [
          'idPaymentMethod' => $op->getIdPaymentMethod(),
          'paymentMethod'   => $op->getPaymentMethod(),
          'holder'          => $op->getHolder(),
          'account'         => $op->getAccount(),
          'instructions'    => $op->getInstructions(),
        ];
      }
    }

    // Servicios disponibles del local, excluyendo los ya agregados.
    $bookedServiceIds = [];
    foreach ($details as $d) {
      if ($d->getIdLocalService() > 0) {
        $bookedServiceIds[] = $d->getIdLocalService();
      }
    }
    $availableServices = [];
    foreach ($this->serviceService->findAvailableByLocal($booking->getIdLocal()) as $s) {
      if (!in_array($s->getIdService(), $bookedServiceIds, true)) {
        $availableServices[] = $s;
      }
    }

    // El cliente solo puede modificar (agregar servicios / pagar)
    // mientras la reserva esté pendiente y no haya subido comprobante.
    $isPending = $booking->getBookingState() === 'pendiente';
    $isModifiable = $isPending && $ticket === null;
    $isClient = ($_SESSION['type'] ?? null) === 'client';
    $hasTicket = $ticket !== null;

    $refundRequest = $isClient ? $this->refundRepo->findByBooking($idBooking) : null;

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

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Servicio agregado correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

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

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Reserva cancelada correctamente.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=myBookings');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // SOLICITAR REEMBOLSO (cliente -> admin la valida)
  // =========================================================
  public function requestRefund(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->bookingService->requestRefund($idBooking, (int) $client->getIdRol(), $motivo);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Solicitud de reembolso enviada.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking . '&error=' . urlencode($error));
      exit;
    }
  }
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
  // SUBIR COMPROBANTE DE PAGO (cliente)
  // =========================================================
  public function uploadTicket(): void
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

      if (empty($_FILES['ticket']['tmp_name'])) {
        throw new BusinessRuleException('Selecciona un archivo de comprobante.');
      }

      $tmp = $_FILES['ticket']['tmp_name'];
      $ext = strtolower(pathinfo($_FILES['ticket']['name'], PATHINFO_EXTENSION));

      $dir = __DIR__ . '/../../Public/resource/tickets';
      if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
      }

      $fileName = 'ticket_' . $idBooking . '_' . time() . '.' . $ext;
      $target = $dir . '/' . $fileName;

      if (!move_uploaded_file($tmp, $target)) {
        throw new BusinessRuleException('No se pudo guardar el comprobante.');
      }

      $this->bookingTicketService->upload($idBooking, 'resource/tickets/' . $fileName, $ext, $idPaymentMethod);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Comprobante subido. El propietario lo revisará para aprobar la reserva.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // APROBAR COMPROBANTE (owner) -> genera factura y ganancia
  // =========================================================
  public function approveTicket(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->myBookings();
      return;
    }

    $owner = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {

      $booking = $this->bookingRepo->findById($idBooking);

      if ($booking === null) {
        throw new BusinessRuleException('La reserva no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());

      $ticket = $this->ticketRepo->findByBooking($idBooking);

      if ($ticket === null) {
        throw new BusinessRuleException('Esta reserva no tiene comprobante.');
      }

      $this->bookingTicketService->approve($ticket->getIdTicket(), $owner->getIdRol());

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Comprobante aprobado y reserva confirmada.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    }
  }

  // =========================================================
  // RECHAZAR COMPROBANTE (owner)
  // =========================================================
  public function rejectTicket(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->myBookings();
      return;
    }

    $owner = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {

      $booking = $this->bookingRepo->findById($idBooking);

      if ($booking === null) {
        throw new BusinessRuleException('La reserva no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());

      $ticket = $this->ticketRepo->findByBooking($idBooking);

      if ($ticket === null) {
        throw new BusinessRuleException('Esta reserva no tiene comprobante.');
      }

      $this->bookingTicketService->reject($ticket->getIdTicket());

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Comprobante rechazado.']);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
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
