<?php

require_once __DIR__ . '/../Service/BookingActionService.php';
require_once __DIR__ . '/../Service/ServiceService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/VenueRentalBlockRepository.php';
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
    $rentalBlocks = (new VenueRentalBlockRepository(DataBase::getConnection()))->findByVenue($idVenue, true);
    $bookedDates = $rentalBlocks === [] ? $this->bookingRepo->bookedDatesByVenue($idVenue) : [];

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
    $attendees = isset($_POST['attendees']) && $_POST['attendees'] !== '' ? (int) $_POST['attendees'] : null;
    $rentalBlockId = isset($_POST['rentalBlockId']) && $_POST['rentalBlockId'] !== '' ? (int) $_POST['rentalBlockId'] : null;

    $serviceQuantities = [];
    if (isset($_POST['services']) && is_array($_POST['services'])) {
      foreach ($_POST['services'] as $serviceId => $quantity) {
        $quantity = (int) $quantity;
        if ($quantity > 0) {
          $serviceQuantities[(int) $serviceId] = $quantity;
        }
      }
    }

    try {
      $idBooking = $this->bookingActionService->create(
        $client->getIdClient(),
        (int) $client->getIdRol(),
        $idVenue,
        $date,
        $endDate,
        $eventType,
        $eventDetail,
        $attendees,
        $rentalBlockId,
        $serviceQuantities
      );

      redirect_to('booking', 'detail', ['id' => $idBooking]);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      $venue = $this->venueRepo->findById($idVenue);
      $services = $this->serviceService->findAvailableByLocal($idVenue);
      $rentalBlocks = (new VenueRentalBlockRepository(DataBase::getConnection()))->findByVenue($idVenue, true);
      $bookedDates = $rentalBlocks === [] ? $this->bookingRepo->bookedDatesByVenue($idVenue) : [];

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
    require_post();
    csrf_validate();

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

  public function updateDetails(): void
  {
    require_role('client');
    require_post();
    csrf_validate();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    try {
      $this->bookingActionService->updateEditableDetails(
        $client->getIdClient(),
        $idBooking,
        trim($_POST['startDate'] ?? ''),
        trim($_POST['endDate'] ?? ''),
        trim($_POST['eventType'] ?? '') ?: null,
        trim($_POST['eventDetail'] ?? '') ?: null,
        (int) ($_POST['attendees'] ?? 0)
      );
      redirect_to('booking', 'detail', ['id' => $idBooking, 'updated' => '1']);
    } catch (BusinessRuleException $e) {
      redirect_to('booking', 'detail', ['id' => $idBooking, 'editError' => $e->getMessage()]);
    }
  }

  public function updateLine(): void
  {
    require_role('client');
    require_post();
    csrf_validate();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    try {
      $this->bookingActionService->updateServiceLine(
        $client->getIdClient(),
        $idBooking,
        (int) ($_POST['detailId'] ?? 0),
        (int) ($_POST['quantity'] ?? 0)
      );
      redirect_to('booking', 'detail', ['id' => $idBooking, 'updated' => '1']);
    } catch (BusinessRuleException $e) {
      redirect_to('booking', 'detail', ['id' => $idBooking, 'editError' => $e->getMessage()]);
    }
  }

  public function removeLine(): void
  {
    require_role('client');
    require_post();
    csrf_validate();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    try {
      $this->bookingActionService->removeServiceLine(
        $client->getIdClient(),
        $idBooking,
        (int) ($_POST['detailId'] ?? 0)
      );
      redirect_to('booking', 'detail', ['id' => $idBooking, 'updated' => '1']);
    } catch (BusinessRuleException $e) {
      redirect_to('booking', 'detail', ['id' => $idBooking, 'editError' => $e->getMessage()]);
    }
  }

  public function setServiceSelection(): void
  {
    require_role('client');
    require_post();
    csrf_validate();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);
    $selected = ($_POST['selected'] ?? '0') === '1';
    try {
      $this->bookingActionService->setServiceSelection(
        $client->getIdClient(),
        $idBooking,
        (int) ($_POST['serviceId'] ?? 0),
        $selected,
        (int) ($_POST['quantity'] ?? 0)
      );
      respond_or_redirect(
        ['ok' => true, 'message' => $selected ? 'Servicio actualizado.' : 'Servicio eliminado de la reserva.'],
        'booking',
        'detail',
        ['id' => $idBooking]
      );
    } catch (BusinessRuleException $e) {
      respond_or_redirect(
        ['ok' => false, 'message' => $e->getMessage()],
        'booking',
        'detail',
        ['id' => $idBooking],
        422
      );
    }
  }

  // =========================================================
  // CANCELAR RESERVA (cliente)
  // =========================================================
  public function cancel(): void
  {
    require_role('client');
    require_post();

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
    require_post();

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
    require_post();
    csrf_validate();

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
