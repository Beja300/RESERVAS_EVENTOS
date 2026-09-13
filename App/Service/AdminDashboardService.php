<?php

require_once __DIR__ . '/EarningService.php';
require_once __DIR__ . '/CommissionConfigService.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/VenueRatingRepository.php';
require_once __DIR__ . '/../Repository/ServiceRatingRepository.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';

/**
 * Agrega las estadísticas del panel del administrador para un año-mes.
 */
class AdminDashboardService
{
  private EarningService $earningService;
  private CommissionConfigService $commissionConfigService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private ClientRepository $clientRepo;
  private VenueRatingRepository $venueRatingRepo;
  private ServiceRatingRepository $serviceRatingRepo;

  public function __construct(PDO $connection)
  {
    $this->earningService = new EarningService($connection);
    $this->commissionConfigService = new CommissionConfigService(new CommissionConfigRepository($connection));
    $this->bookingRepo = new BookingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->venueRatingRepo = new VenueRatingRepository($connection);
    $this->serviceRatingRepo = new ServiceRatingRepository($connection);
  }

  /**
   * Devuelve un array plano con las variables que usa Dashboard.php.
   */
  public function metrics(string $yearMonth): array
  {
    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $config = $this->commissionConfigService->getActive();

    return [
      'bookings'       => $this->bookingRepo->findByMonth($yearMonth),
      'topVenues'      => $this->bookingRepo->topActiveVenues(5),
      'topServices'    => $this->detailRepo->topRequestedServices(5),
      'monthStats'     => $this->earningService->summarizeByMonth($yearMonth),
      'config'         => $config,
      'commissionPct'  => $config->getPercentage(),
      'taxPct'         => $config->getTax(),
      'stateCounts'    => $this->bookingRepo->countByState($yearMonth),
      'occupancy'      => $this->bookingRepo->occupancyByVenue($yearMonth),
      'clientStats'    => [
        'nuevos'      => $this->clientRepo->countNewThisMonth($yearMonth),
        'recurrentes' => $this->clientRepo->countRecurrentThisMonth($yearMonth),
      ],
      'topClients'     => $this->clientRepo->topByBookings($yearMonth, 5),
      'venueAvg'       => $this->venueRatingRepo->averageStars(),
      'venueReviews'   => $this->venueRatingRepo->countAll(),
      'serviceAvg'     => $this->serviceRatingRepo->averageStars(),
      'serviceReviews' => $this->serviceRatingRepo->countAll(),
      'prevMonth'      => date('Y-m', strtotime($yearMonth . '-01 first day of last month')),
      'nextMonth'      => date('Y-m', strtotime($yearMonth . '-01 first day of next month')),
    ];
  }
}