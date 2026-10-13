<?php

require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/EarningRepository.php';
require_once __DIR__ . '/../Repository/VenueRatingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';

/**
 * Agrega las métricas del panel del propietario para un año-mes dado.
 * Centraliza las consultas que antes se ensamblaban en OwnerController.
 */
class OwnerDashboardService
{
  private VenueRepository $venueRepo;
  private BookingRepository $bookingRepo;
  private BookingTicketRepository $ticketRepo;
  private EarningRepository $earningRepo;
  private VenueRatingRepository $venueRatingRepo;
  private DetailRepository $detailRepo;

  public function __construct(PDO $connection)
  {
    $this->venueRepo = new VenueRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->earningRepo = new EarningRepository($connection);
    $this->venueRatingRepo = new VenueRatingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
  }

  /**
   * Devuelve un array plano con las variables que usa la vista del
   * dashboard (venezuelas/bookings/earnings/stats/topVenue/topServices).
   */
  public function metrics(int $ownerId, string $yearMonth): array
  {
    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $venues = $this->venueRepo->findByOwner($ownerId);

    $bookings = [];
    foreach ($venues as $venue) {
      $bookings[$venue->getIdVenue()] = $this->bookingRepo->findByVenue($venue->getIdVenue());
    }

    $earnings = $this->earningRepo->totalsByOwnerForMonth($ownerId, $yearMonth);
    $nextBooking = $this->bookingRepo->nextBookingByOwner($ownerId, date('Y-m-d'));
    $averageRating = $this->venueRatingRepo->findAverageByOwner($ownerId);

    $monthNames = [
      1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
      5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
      9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    $stats = [
      'locales'        => count($venues),
      'porRevisar'     => $this->ticketRepo->countPendingByOwner($ownerId),
      'ganancias'      => $earnings['ownerAmount'],
      'comision'       => $earnings['commission'],
      'totalBruto'     => $earnings['total'],
      'reservasMes'    => $this->bookingRepo->countByOwnerForMonth($ownerId, $yearMonth),
      'proximaReserva' => $nextBooking,
      'rating'         => $averageRating,
      'monthLabel'     => $monthNames[(int) substr($yearMonth, 5, 2)] . ' ' . substr($yearMonth, 0, 4),
    ];

    return [
      'venues'       => $venues,
      'bookings'     => $bookings,
      'earnings'     => $earnings,
      'nextBooking'  => $nextBooking,
      'averageRating'=> $averageRating,
      'stats'        => $stats,
      'topVenue'     => $this->bookingRepo->topVenuesByOwner($ownerId, $yearMonth, 1),
      'topServices'  => $this->detailRepo->topServicesByOwner($ownerId, $yearMonth, 3),
      'prevMonth'    => date('Y-m', strtotime($yearMonth . '-01 first day of last month')),
      'nextMonth'    => date('Y-m', strtotime($yearMonth . '-01 first day of next month')),
    ];
  }
}