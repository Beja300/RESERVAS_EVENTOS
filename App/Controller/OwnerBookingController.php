<?php

require_once __DIR__ . '/../Service/BookingActionService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerBookingController
{
  private BookingActionService $bookingActionService;
  private OwnerService $ownerService;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private BookingTicketRepository $ticketRepo;
  private ClientRepository $clientRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->bookingActionService = new BookingActionService($connection);
    $this->ownerService = new OwnerService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
  }

  // =========================================================
  // RESERVAS DE UN LOCAL (owner)
  // =========================================================
  public function venueBookings(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $idVenue = (int) ($_GET['venueId'] ?? 0);

    try {
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $idVenue);

      $bookings = $this->bookingRepo->findByVenue($idVenue);

      $venueNames = [];
      $hasTicket = [];
      $clientNames = [];
      foreach ($bookings as $b) {
        $venue = $this->venueRepo->findById($b->getIdLocal());
        $venueNames[$b->getIdBooking()] = $venue !== null
          ? $venue->getNameVenue()
          : 'Local #' . $b->getIdLocal();
        $hasTicket[$b->getIdBooking()] = $this->ticketRepo->findByBooking($b->getIdBooking()) !== null;
        $client = $this->clientRepo->findByClientPk($b->getIdClient());
        $clientNames[$b->getIdBooking()] = $client !== null
          ? $client->getName()
          : '#' . $b->getIdClient();
      }

      require_once __DIR__ . '/../View/Booking/List.php';
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      redirect_to('venue', 'list');
    }
  }

  // =========================================================
  // RESERVAS PENDIENTES DEL OWNER (todos sus locales)
  // =========================================================
  public function pendingBookings(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];

    $allPending = $this->bookingRepo->findPendingByOwner($owner->getIdOwner());

    $bookings = [];
    $venueNames = [];
    $hasTicket = [];
    $clientNames = [];

    foreach ($allPending as $b) {
      $ticket = $this->ticketRepo->findByBooking($b->getIdBooking());

      // Solo las que tienen comprobante por aprobar (ticket pendiente).
      if ($ticket === null || $ticket->getState() !== 'pendiente') {
        continue;
      }

      $bookings[] = $b;

      $venue = $this->venueRepo->findById($b->getIdLocal());
      $venueNames[$b->getIdBooking()] = $venue !== null
        ? $venue->getNameVenue()
        : 'Local #' . $b->getIdLocal();

      $hasTicket[$b->getIdBooking()] = true;

      $client = $this->clientRepo->findByClientPk($b->getIdClient());
      $clientNames[$b->getIdBooking()] = $client !== null
        ? $client->getName()
        : '#' . $b->getIdClient();
    }

    $pageTitle = 'Reservas pendientes';
    $isPendingBookings = true;

    require_once __DIR__ . '/../View/Booking/List.php';
  }

  // =========================================================
  // APROBAR COMPROBANTE (owner) -> genera factura y ganancia
  // =========================================================
  public function approveTicket(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('booking', 'myBookings');
    }

    $owner = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {
      $this->bookingActionService->approveTicket($owner->getIdOwner(), (int) $owner->getIdRol(), $idBooking);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Comprobante aprobado y reserva confirmada.'],
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
  // RECHAZAR COMPROBANTE (owner)
  // =========================================================
  public function rejectTicket(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('booking', 'myBookings');
    }

    $owner = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {
      $this->bookingActionService->rejectTicket($owner->getIdOwner(), $idBooking);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Comprobante rechazado.'],
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