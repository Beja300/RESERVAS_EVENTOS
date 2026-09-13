<?php

require_once __DIR__ . '/../Service/BookingDetailService.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

/**
 * DETALLE DE UNA RESERVA (cliente/owner). Compartido por ambos roles:
 * valida la pertenencia según el rol logueado y ensambla la vista.
 */
class BookingDetailController
{
  private BookingDetailService $detailService;
  private BookingRepository $bookingRepo;
  private OwnerService $ownerService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->detailService = new BookingDetailService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->ownerService = new OwnerService($connection);
  }

  public function detail(): void
  {
    $idBooking = (int) ($_GET['id'] ?? 0);
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      redirect_to('venue', 'catalog');
    }

    $type = $_SESSION['type'] ?? null;

    if ($type === 'client') {
      $client = $_SESSION['user'];
      if ($booking->getIdClient() !== $client->getIdClient()) {
        redirect_to('booking', 'myBookings');
      }
    } elseif ($type === 'owner') {
      $owner = $_SESSION['user'];
      $this->ownerService->assertOwnsVenue($owner->getIdOwner(), $booking->getIdLocal());
    } else {
      redirect_to('auth', 'showLogin');
    }

    $data = $this->detailService->assemble($idBooking);

    extract($data, EXTR_SKIP);

    require_once __DIR__ . '/../View/Booking/Detail.php';
  }
}