<?php

require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class OwnerController
{
  private AuthService $authService;
  private HistoryService $historyService;
  private VenueRepository $venueRepo;
  private BookingRepository $bookingRepo;
  private RoleRepository $roleRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->authService = new AuthService();
    $this->historyService = new HistoryService($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
  }

  // =========================================================
  // DASHBOARD (sus locales + sus reservas)
  // =========================================================
  public function dashboard(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];

    $venues = $this->venueRepo->findByOwner($owner->getIdOwner());

    $bookings = [];
    foreach ($venues as $venue) {
      $bookings[$venue->getIdVenue()] = $this->bookingRepo->findByVenue($venue->getIdVenue());
    }

    require_once __DIR__ . '/../View/Owner/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    session_start();
    $this->requireOwner();

    $owner = $_SESSION['user'];

    require_once __DIR__ . '/../View/Owner/Form.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (nombre, correo, teléfono)
  // =========================================================
  public function updateProfile(): void
  {
    session_start();
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $_SESSION['user'];

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if (strtolower($email) !== strtolower($owner->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      $owner->setName($name);
      $owner->setEmail($email);
      $owner->setPhoneNumber($phoneNumber);

      $this->roleRepo->update($owner);

      $_SESSION['user'] = $owner;

      header('Location: ../../Public/index.php?controller=owner&action=profile');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Owner/Form.php';
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
