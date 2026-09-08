<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/PaymentMethodService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';

class PaymentMethodController extends BaseController
{
  private PaymentMethodService $paymentMethodService;

  public function __construct()
  {
    $this->paymentMethodService = new PaymentMethodService();
  }

  // =========================================================
  // LISTAR MÉTODOS DE PAGO ACTIVOS
  // =========================================================
  public function list(): void
  {
    $paymentMethods = ($_SESSION['type'] ?? null) === 'admin'
      ? $this->paymentMethodService->findAll()
      : $this->paymentMethodService->findActive();

    require_once __DIR__ . '/../View/PaymentMethod/List.php';
  }

  // =========================================================
  // MOSTRAR FORMULARIO (solo admin)
  // =========================================================
  public function showForm(): void
  {
    $this->requireAdmin();

    require_once __DIR__ . '/../View/PaymentMethod/Form.php';
  }

  // =========================================================
  // CREAR (solo admin)
  // =========================================================
  public function create(): void
  {
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
    $this->requireAdmin();

    $idPaymentMethod = (int) ($_GET['id'] ?? 0);
    $paymentMethod = $this->paymentMethodService->findById($idPaymentMethod);

    if ($paymentMethod === null) {
      $this->redirect('paymentmethod', 'list');
    }

    require_once __DIR__ . '/../View/PaymentMethod/Edit.php';
  }

  // =========================================================
  // ACTUALIZAR (solo admin)
  // =========================================================
  public function update(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('paymentmethod', 'list');
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
      $paymentMethod = $this->paymentMethodService->findById($idPaymentMethod);

      if ($paymentMethod === null) {
        $this->redirect('paymentmethod', 'list');
      }

      require_once __DIR__ . '/../View/PaymentMethod/Edit.php';
    }
  }

  // =========================================================
  // ELIMINAR (soft delete, solo admin)
  // =========================================================
  public function delete(): void
  {
    $this->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->redirect('paymentmethod', 'list');
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
}