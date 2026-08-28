<?php

require_once __DIR__ . '/../Service/PaymentMethodService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class PaymentMethodController
{
  private PaymentMethodService $paymentMethodService;
  private PaymentMethodRepository $paymentMethodRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->paymentMethodService = new PaymentMethodService();
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
  }

  // =========================================================
  // LISTAR MÉTODOS DE PAGO ACTIVOS
  // =========================================================
  public function list(): void
  {
    $paymentMethods = $this->paymentMethodRepo->findActive();

    require_once __DIR__ . '/../View/PaymentMethod/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (solo admin)
  // =========================================================
  public function showForm(): void
  {
    session_start();
    $this->requireAdmin();

    require_once __DIR__ . '/../View/PaymentMethod/Form.php';
  }

  // =========================================================
  // CREAR (solo admin)
  // =========================================================
  public function create(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->showForm();
      return;
    }

    $type = trim($_POST['type'] ?? '');

    try {

      $this->paymentMethodService->validateAndCreate($type);

      header('Location: ../../Public/index.php?controller=paymentMethod&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/PaymentMethod/Form.php';
    }
  }

  // =========================================================
  // GUARDIA: SOLO ADMIN AUTENTICADO
  // =========================================================
  private function requireAdmin(): void
  {
    if (($_SESSION['type'] ?? null) !== 'admin') {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
