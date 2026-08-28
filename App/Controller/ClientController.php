<?php

require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class ClientController
{
  private HistoryService $historyService;
  private AuthService $authService;
  private BookingRepository $bookingRepo;
  private RoleRepository $roleRepo;
  private DetailRepository $detailRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->historyService = new HistoryService($connection);
    $this->authService = new AuthService();
    $this->bookingRepo = new BookingRepository($connection);
    $this->roleRepo = new RoleRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
  }

  // =========================================================
  // DASHBOARD (recomendaciones + mis reservas)
  // =========================================================
  public function dashboard(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];

    $recommendations = $this->historyService->recommendVenues(
      $client->getIdRol(),
      5
    );

    $bookings = $this->bookingRepo->findByClient($client->getIdClient());

    require_once __DIR__ . '/../View/Client/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    session_start();
    $this->requireClient();

    $client = $_SESSION['user'];

    require_once __DIR__ . '/../View/Client/Profile.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (nombre, correo, teléfono)
  // =========================================================
  public function updateProfile(): void
  {
    session_start();
    $this->requireClient();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $client = $_SESSION['user'];

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if (strtolower($email) !== strtolower($client->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      $client->setName($name);
      $client->setEmail($email);
      $client->setPhoneNumber($phoneNumber);

      $this->roleRepo->update($client);

      $_SESSION['user'] = $client;

      header('Location: ../../Public/index.php?controller=client&action=profile');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Client/Profile.php';
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
}
