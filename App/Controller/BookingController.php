<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/DetailService.php';
require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/ClientService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/OwnerPaymentService.php';
require_once __DIR__ . '/../Service/BookingTicketService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class BookingController extends BaseController
{
  private BookingService $bookingService;
  private DetailService $detailService;
  private InvoiceService $invoiceService;
  private ClientService $clientService;
  private ServiceService $serviceService;
  private OwnerService $ownerService;
  private BookingTicketService $bookingTicketService;
  private OwnerPaymentService $ownerPaymentService;
  private NotificationService $notificationService;
  private HistoryService $historyService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->bookingService = new BookingService();
    $this->detailService = new DetailService($connection);
    $this->invoiceService = new InvoiceService();
    $this->clientService = new ClientService($connection);
    $this->serviceService = new ServiceService(new ServiceRepository($connection));
    $this->ownerService = new OwnerService($connection);
    $this->ownerPaymentService = new OwnerPaymentService($connection);
    $this->bookingTicketService = new BookingTicketService($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
    $this->historyService = new HistoryService($connection);
  }

  // =========================================================
  // CREAR UNA RESERVA (cliente)
  // =========================================================
  public function create(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $client = $this->currentUser();
    $idVenue = (int) ($_POST['venueId'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '') ?: null;
    $eventType = trim($_POST['eventType'] ?? '') ?: null;
    $eventDetail = trim($_POST['eventDetail'] ?? '') ?: null;

    try {

      if ($eventType === 'otro' && ($eventDetail === null || $eventDetail === '')) {
        throw new BusinessRuleException(
          'Indica de qué trata tu evento para que el propietario del local lo conozca.'
        );
      }

      if ($eventDetail !== null && mb_strlen($eventDetail) > 255) {
        throw new BusinessRuleException(
          'La descripción del evento no puede superar los 255 caracteres.'
        );
      }

      $this->clientService->assertCanBook($client->getIdClient());

      $idBooking = $this->bookingService->createBooking(
        $client->getIdClient(),
        $idVenue,
        $date,
        $endDate,
        $eventType,
        $eventDetail
      );

      $this->historyService->logVenueBooking((int) $client->getIdRol(), $idVenue);

      $venue = $this->bookingService->getVenue($idVenue);
      if ($venue !== null) {
        $this->notificationService->notifyOwnerOfNewBooking(
          (int) $venue->getIdOwner(),
          $venue->getNameVenue(),
          (int) $idBooking
        );
      }
      $this->notificationService->notifyNewBookingToAdmins((int) $idBooking);

      header('Location: ../../Public/index.php?controller=booking&action=detail&id=' . $idBooking);
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $venue = $this->bookingService->getVenue($idVenue);
      $services = $this->serviceService->findAvailableByLocal($idVenue);
      $bookedDates = $this->bookingService->getBookedDates($idVenue);

      require_once __DIR__ . '/../View/Booking/Form.php';
    }
  }

  // =========================================================
  // MOSTRAR FORMULARIO DE RESERVA (cliente)
  // =========================================================
  public function showForm(): void
  {
    $this->startSession();

    $idVenue = (int) ($_GET['venueId'] ?? 0);
    $venue = $this->bookingService->getVenue($idVenue);

    if ($venue === null || !$venue->getIsActive()) {
      $this->redirect('venue', 'catalog');
    }

    $services = $this->serviceService->findAvailableByLocal($idVenue);
    $bookedDates = $this->bookingService->getBookedDates($idVenue);

    require_once __DIR__ . '/../View/Booking/Form.php';
  }

  // =========================================================
  // MIS RESERVAS (cliente)
  // =========================================================
  public function myBookings(): void
  {
    $this->requireClient();

    $client = $this->currentUser();
    $bookings = $this->bookingService->getBookingsByClient($client->getIdClient());

    $venueNames = [];
    $hasTicket = [];
    foreach ($bookings as $b) {
      $venue = $this->bookingService->getVenue($b->getIdLocal());
      $venueNames[$b->getIdBooking()] = $venue !== null
        ? $venue->getNameVenue()
        : 'Local #' . $b->getIdLocal();
      $hasTicket[$b->getIdBooking()] = $this->bookingService->getTicketForBooking($b->getIdBooking()) !== null;
    }

    require_once __DIR__ . '/../View/Booking/List.php';
  }

  // =========================================================
  // DETALLE DE UNA RESERVA (cliente/owner)
  // =========================================================
  public function detail(): void
  {
    $this->startSession();

    $idBooking = (int) ($_GET['id'] ?? 0);
    $booking = $this->bookingService->getBooking($idBooking);

    if ($booking === null) {
      $this->redirect('venue', 'catalog');
    }

    $type = $_SESSION['type'] ?? null;

    if ($type === 'client') {
      $client = $_SESSION['user'];
      if ($booking->getIdClient() !== $client->getIdClient()) {
        $this->redirect('booking', 'myBookings');
      }
    } elseif ($type === 'owner') {
      $owner = $_SESSION['user'];
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());
    } else {
      $this->redirect('auth', 'showLogin');
    }

    $details = $this->bookingService->getDetailsForBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = $totals['total'];
    $venue = $this->bookingService->getVenue($booking->getIdLocal());
    $client = $this->bookingService->getClient($booking->getIdClient());
    $owner = $venue !== null ? $this->bookingService->getOwner($venue->getIdOwner()) : null;
    $ticket = $this->bookingService->getTicketForBooking($idBooking);
    $paymentMethods = $this->bookingService->getActivePaymentMethods();

    $serviceMap = [];
    foreach ($details as $d) {
      if ($d->getIdLocalService() > 0
          && !isset($serviceMap[$d->getIdLocalService()])) {
        $service = $this->serviceService->findById($d->getIdLocalService());
        if ($service !== null) {
          $serviceMap[$d->getIdLocalService()] = $service;
        }
      }
    }

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

    $refundRequest = $isClient ? $this->bookingService->getRefundForBooking($idBooking) : null;

    require_once __DIR__ . '/../View/Booking/Detail.php';
  }

  // =========================================================
  // AGREGAR SERVICIO AL DETALLE (cliente)
  // =========================================================
  public function addLine(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $client = $this->currentUser();
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
    $this->requireClient();

    $client = $this->currentUser();
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $cancelledBooking = $this->bookingService->cancel($idBooking);

      $cancelledVenue = $cancelledBooking !== null
        ? $this->bookingService->getVenue($cancelledBooking->getIdLocal())
        : null;
      if ($cancelledVenue !== null) {
        $this->notificationService->notifyOwnerBookingCancelled(
          (int) $cancelledVenue->getIdOwner(),
          (int) $idBooking
        );
      }

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
    $this->requireClient();

    $client = $this->currentUser();
    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->bookingService->requestRefund($idBooking, (int) $client->getIdRol(), $motivo);

      $this->notificationService->notifyAdminsRefundRequested($idBooking);

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

  // =========================================================
  // PAGAR (generar factura) — cliente
  // =========================================================
  public function pay(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $client = $this->currentUser();
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);

    try {

      $this->clientService->assertOwnsBooking($client->getIdClient(), $idBooking);

      $this->invoiceService->generate($idBooking, $idPaymentMethod, date('Y-m-d'));

      $paidBooking = $this->bookingService->getBooking($idBooking);
      if ($paidBooking !== null) {
        $this->historyService->logVenuePurchase((int) $client->getIdRol(), (int) $paidBooking->getIdLocal());
      }

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
    $this->requireOwner();

    $owner = $this->currentUser();
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    try {

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $bookings = $this->bookingService->getBookingsByVenue($idVenue);

      $venueNames = [];
      $hasTicket = [];
      $clientNames = [];
      foreach ($bookings as $b) {
        $venue = $this->bookingService->getVenue($b->getIdLocal());
        $venueNames[$b->getIdBooking()] = $venue !== null
          ? $venue->getNameVenue()
          : 'Local #' . $b->getIdLocal();
        $hasTicket[$b->getIdBooking()] = $this->bookingService->getTicketForBooking($b->getIdBooking()) !== null;
        $client = $this->bookingService->getClient($b->getIdClient());
        $clientNames[$b->getIdBooking()] = $client !== null
          ? $client->getName()
          : '#' . $b->getIdClient();
      }

      require_once __DIR__ . '/../View/Booking/List.php';
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      $this->redirect('venue', 'list');
    }
  }

  // =========================================================
  // RESERVAS PENDIENTES DEL OWNER (todos sus locales)
  // Lista solo las reservas 'pendiente' que ya tienen comprobante
  // subido (ticket pendiente) y esperan aprobación del propietario.
  // =========================================================
  public function pendingBookings(): void
  {
    $this->requireOwner();

    $owner = $this->currentUser();

    $allPending = $this->bookingService->getPendingBookingsByOwner($owner->getIdOwner());

    $bookings = [];
    $venueNames = [];
    $hasTicket = [];
    $clientNames = [];

    foreach ($allPending as $b) {
      $ticket = $this->bookingService->getTicketForBooking($b->getIdBooking());

      // Solo las que tienen comprobante por aprobar (ticket pendiente).
      if ($ticket === null || $ticket->getState() !== 'pendiente') {
        continue;
      }

      $bookings[] = $b;

      $venue = $this->bookingService->getVenue($b->getIdLocal());
      $venueNames[$b->getIdBooking()] = $venue !== null
        ? $venue->getNameVenue()
        : 'Local #' . $b->getIdLocal();

      $hasTicket[$b->getIdBooking()] = true;

      $client = $this->bookingService->getClient($b->getIdClient());
      $clientNames[$b->getIdBooking()] = $client !== null
        ? $client->getName()
        : '#' . $b->getIdClient();
    }

    $pageTitle = 'Reservas pendientes';
    $isPendingBookings = true;

    require_once __DIR__ . '/../View/Booking/List.php';
  }

  // =========================================================
  // SUBIR COMPROBANTE DE PAGO (cliente)
  // =========================================================
  public function uploadTicket(): void
  {
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->detail();
      return;
    }

    $client = $this->currentUser();
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

      $ticketBooking = $this->bookingService->getBooking($idBooking);
      if ($ticketBooking !== null) {
        $this->historyService->logVenuePurchase((int) $client->getIdRol(), (int) $ticketBooking->getIdLocal());

        $ticketVenue = $this->bookingService->getVenue($ticketBooking->getIdLocal());
        if ($ticketVenue !== null) {
          $this->notificationService->notifyOwnerPaymentVerification(
            (int) $ticketVenue->getIdOwner(),
            (int) $idBooking
          );
        }
      }

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
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->myBookings();
      return;
    }

    $owner = $this->currentUser();
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {

      $booking = $this->bookingService->getBooking($idBooking);

      if ($booking === null) {
        throw new BusinessRuleException('La reserva no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());

      $ticket = $this->bookingService->getTicketForBooking($idBooking);

      if ($ticket === null) {
        throw new BusinessRuleException('Esta reserva no tiene comprobante.');
      }

      $this->bookingTicketService->approve($ticket->getIdTicket(), $owner->getIdRol());

      $this->notificationService->notifyClientPaymentApproved((int) $booking->getIdClient(), (int) $idBooking);

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
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->myBookings();
      return;
    }

    $owner = $this->currentUser();
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {

      $booking = $this->bookingService->getBooking($idBooking);

      if ($booking === null) {
        throw new BusinessRuleException('La reserva no existe.');
      }

      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());

      $ticket = $this->bookingService->getTicketForBooking($idBooking);

      if ($ticket === null) {
        throw new BusinessRuleException('Esta reserva no tiene comprobante.');
      }

      $this->bookingTicketService->reject($ticket->getIdTicket());

      $this->notificationService->notifyClientPaymentRejected((int) $booking->getIdClient(), (int) $idBooking);

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
}