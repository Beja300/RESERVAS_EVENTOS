<?php

require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';

class AuthController
{
  private AuthService $authService;

  public function __construct()
  {
    $this->authService = new AuthService();
  }

  // =========================================================
  // MOSTRAR LOGIN
  // =========================================================
  public function showLogin(): void
  {
    require_once __DIR__ . '/../View/Auth/Login.php';
  }

  // =========================================================
  // LOGIN
  // =========================================================
  public function login(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showLogin();
      return;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {

      $result = $this->authService->login(
        $email,
        $password
      );

      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }

      session_regenerate_id(true);

      $_SESSION['type'] = $result['type'];
      $_SESSION['user'] = $result['user'];

      switch ($result['type']) {

        case 'admin':
          header('Location: ../View/Admin/Dashboard.php');
          break;

        case 'client':
          header('Location: ../View/Client/Dashboard.php');
          break;

        case 'owner':
          header('Location: ../View/Owner/Dashboard.php');
          break;

        default:
          header('Location: ../View/Auth/Login.php');
          break;
      }

      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Auth/Login.php';
    }
  }

  // =========================================================
  // MOSTRAR REGISTRO
  // =========================================================
  public function showRegister(): void
  {
    require_once __DIR__ . '/../View/Auth/Register.php';
  }

  // =========================================================
  // REGISTRAR CLIENTE
  // =========================================================
  public function registerClient(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showRegister();
      return;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      $client = $this->authService->registerClient(
        $name,
        $email,
        $password,
        $phoneNumber
      );

      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }

      session_regenerate_id(true);

      $_SESSION['type'] = 'client';
      $_SESSION['user'] = $client;

      header('Location: ../View/Client/Dashboard.php');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Auth/Register.php';
    }
  }

  // =========================================================
  // REGISTRAR PROPIETARIO
  // =========================================================
  public function registerOwner(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showRegister();
      return;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $ownerFirstName = trim($_POST['ownerFirstName'] ?? '');
    $ownerLastName = trim($_POST['ownerLastName'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $ownerAlias = trim($_POST['ownerAlias'] ?? '');
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');

    if ($ownerLastName === '') {
      $ownerLastName = null;
    }

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    if ($ownerAlias === '') {
      $ownerAlias = null;
    }

    if ($ownerIdentification === '') {
      $ownerIdentification = null;
    }

    try {

      $owner = $this->authService->registerOwner(
        $name,
        $email,
        $password,
        $ownerFirstName,
        $ownerLastName,
        $phoneNumber,
        $ownerAlias,
        $ownerIdentification
      );

      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }

      session_regenerate_id(true);

      $_SESSION['type'] = 'owner';
      $_SESSION['user'] = $owner;

      header('Location: ../View/Owner/Dashboard.php');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/Auth/Register.php';
    }
  }

  // =========================================================
  // CERRAR SESIÓN
  // =========================================================
  public function logout(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION = [];

    session_destroy();

    header('Location: ../View/Auth/Login.php');
    exit;
  }
}
