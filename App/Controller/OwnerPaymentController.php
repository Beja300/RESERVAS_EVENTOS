<?php

require_once __DIR__ . '/../Service/OwnerPaymentService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerPaymentController
{
  private OwnerPaymentService $ownerPaymentService;
  private PaymentMethodRepository $paymentMethodRepo;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->ownerPaymentService = new OwnerPaymentService($connection);
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
  }

  // =========================================================
  // CONFIGURAR DATOS DE COBRO (métodos que acepta el owner).
  // Estos datos los ve el cliente al elegir cómo pagar.
  // =========================================================
  public function paymentData(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];

    $ownerPayments = $this->ownerPaymentService->findByOwner($owner->getIdOwner());
    $paymentMethods = $this->paymentMethodRepo->findActive();

    require_once __DIR__ . '/../View/Owner/PaymentData.php';
  }

  // =========================================================
  // GUARDAR / ACTUALIZAR UN MÉTODO DE COBRO (AJAX)
  // =========================================================
  public function savePayment(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->paymentData();
      return;
    }

    $owner = $_SESSION['user'];

    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);
    $ownerPaymentPk = (int) ($_POST['idOwnerPayment'] ?? 0);
    $holder = trim($_POST['holder'] ?? '');
    $account = trim($_POST['account'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $active = isset($_POST['active']);

    try {
      $this->ownerPaymentService->save(
        $owner->getIdOwner(),
        $idPaymentMethod,
        $holder,
        $account,
        $instructions,
        $active,
        $ownerPaymentPk
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Método de cobro guardado.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      $ownerPayments = $this->ownerPaymentService->findByOwner($owner->getIdOwner());
      $paymentMethods = $this->paymentMethodRepo->findActive();
      require_once __DIR__ . '/../View/Owner/PaymentData.php';
      return;
    }

    redirect_to('owner', 'paymentData', ['saved' => 1]);
  }

  // =========================================================
  // ELIMINAR UN MÉTODO DE COBRO (AJAX)
  // =========================================================
  public function removePayment(): void
  {
    require_role('owner');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->paymentData();
      return;
    }

    $owner = $_SESSION['user'];
    $idOwnerPayment = (int) ($_POST['idOwnerPayment'] ?? 0);

    try {
      $this->ownerPaymentService->remove($owner->getIdOwner(), $idOwnerPayment);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Método de cobro eliminado.']);
      }
    } catch (BusinessRuleException $e) {
      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      $ownerPayments = $this->ownerPaymentService->findByOwner($owner->getIdOwner());
      $paymentMethods = $this->paymentMethodRepo->findActive();
      require_once __DIR__ . '/../View/Owner/PaymentData.php';
      return;
    }

    redirect_to('owner', 'paymentData', ['removed' => 1]);
  }
}