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
    $paymentMethods = ($_SESSION['type'] ?? null) === 'admin'
      ? $this->paymentMethodRepo->findAll()
      : $this->paymentMethodRepo->findActive();

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

      header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      require_once __DIR__ . '/../View/PaymentMethod/Form.php';
    }
  }

  // =========================================================
  // MOSTRAR FORM DE EDICIÓN (solo admin)
  // =========================================================
  public function edit(): void
  {
    session_start();
    $this->requireAdmin();

    $idPaymentMethod = (int) ($_GET['id'] ?? 0);
    $paymentMethod = $this->paymentMethodRepo->findById($idPaymentMethod);

    if ($paymentMethod === null) {
      header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
      exit;
    }

    require_once __DIR__ . '/../View/PaymentMethod/Edit.php';
  }

  // =========================================================
  // ACTUALIZAR (solo admin)
  // =========================================================
  public function update(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
      exit;
    }

    $idPaymentMethod = (int) ($_POST['id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $isActive = isset($_POST['isActive']);

    try {

      $this->paymentMethodService->updateMethod($idPaymentMethod, $type, $isActive);

      header('Location: ../../Public/index.php?controller=paymentmethod&action=list&updated=1');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();
      $paymentMethod = $this->paymentMethodRepo->findById($idPaymentMethod);

      if ($paymentMethod === null) {
        header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
        exit;
      }

      require_once __DIR__ . '/../View/PaymentMethod/Edit.php';
    }
  }

  // =========================================================
  // ELIMINAR (soft delete, solo admin)
  // =========================================================
  public function delete(): void
  {
    session_start();
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
      exit;
    }

    $idPaymentMethod = (int) ($_POST['id'] ?? 0);

    try {

      $this->paymentMethodService->deleteMethod($idPaymentMethod);
    } catch (BusinessRuleException $e) {
      // Sin cambios; se redirige igual a la lista.
    }

    header('Location: ../../Public/index.php?controller=paymentmethod&action=list');
    exit;
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
