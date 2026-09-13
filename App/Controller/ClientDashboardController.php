<?php

require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class ClientDashboardController
{
  private HistoryService $historyService;
  private BookingRepository $bookingRepo;
  private LocationRepository $locationRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->historyService = new HistoryService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->locationRepo = new LocationRepository($connection);
  }

  // =========================================================
  // DASHBOARD (recomendaciones + mis reservas)
  // =========================================================
  public function dashboard(): void
  {
    require_role('client');

    $client = $_SESSION['user'];

    $recommendations = $this->historyService->recommendVenues($client->getIdRol(), 5);

    $clientLocationId = $client->getLocationId();

    $hasValidLocation = $clientLocationId !== null
      && $this->locationRepo->findById($clientLocationId) !== null;

    $nearbyVenues = $hasValidLocation
      ? $this->historyService->recommendVenuesByLocation($clientLocationId, 5)
      : [];

    $hasLocation = $hasValidLocation;

    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    $allVenues = array_merge($recommendations, $nearbyVenues);
    $locationByVenue = [];
    $locationCache = [];
    foreach ($allVenues as $v) {
      $locId = $v->getIdLocation();
      if ($locId > 0) {
        if (!isset($locationCache[$locId])) {
          $locationCache[$locId] = $this->locationRepo->findById($locId);
        }
        $locationByVenue[$v->getIdVenue()] = $locationCache[$locId];
      }
    }

    require_once __DIR__ . '/../View/Client/Dashboard.php';
  }
}