<?php

require_once __DIR__ . '/../Service/CreditService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

/**
 * Controller del módulo de crédito (cuentas por cobrar y por pagar).
 * - Cliente: financia una reserva y registra abonos.
 * - Propietario: define la política de crédito y ve lo que tiene por cobrar.
 * - Admin: ve todas las cuentas.
 */
class CreditController
{
  private CreditService $creditService;

  public function __construct()
  {
    $this->creditService = new CreditService(DataBase::getConnection());
  }

  // =========================================================
  // PROPIETARIO: FORMULARIO DE POLÍTICA DE CRÉDITO
  // =========================================================
  public function ownerConfig(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $config = $this->creditService->getConfig($owner->getIdOwner());

    require_once __DIR__ . '/../View/Credit/OwnerConfig.php';
  }

  // =========================================================
  // PROPIETARIO: GUARDAR POLÍTICA
  // =========================================================
  public function saveConfig(): void
  {
    require_role('owner');
    require_post();

    $owner = $_SESSION['user'];
    $enabled = isset($_POST['enabled']);
    $days = (int) ($_POST['days'] ?? 30);
    $frequentMin = (int) ($_POST['frequentMin'] ?? 3);

    try {
      $this->creditService->saveConfig($owner->getIdOwner(), $enabled, $days, $frequentMin);
      redirect_to('credit', 'ownerConfig', ['saved' => '1']);
    } catch (BusinessRuleException $e) {
      $config = $this->creditService->getConfig($owner->getIdOwner());
      $config->setEnabled($enabled);
      $config->setDays($days);
      $config->setFrequentMin($frequentMin);
      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Credit/OwnerConfig.php';
    }
  }

  // =========================================================
  // PROPIETARIO: CUENTAS POR PAGAR (créditos de sus locales)
  // =========================================================
  public function ownerList(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $credits = $this->creditService->listForOwner($owner->getIdOwner());
    $outstanding = $this->creditService->outstandingByOwner($owner->getIdOwner());

    require_once __DIR__ . '/../View/Credit/OwnerList.php';
  }

  // =========================================================
  // CLIENTE: MIS CRÉDITOS (cuentas por cobrar)
  // =========================================================
  public function clientList(): void
  {
    require_role('client');

    $client = $_SESSION['user'];
    $credits = $this->creditService->listForClient($client->getIdClient());
    $outstanding = $this->creditService->outstandingByClient($client->getIdClient());

    require_once __DIR__ . '/../View/Credit/ClientList.php';
  }

  // =========================================================
  // CLIENTE: SOLICITAR CRÉDITO PARA UNA RESERVA
  // =========================================================
  public function request(): void
  {
    require_role('client');
    require_post();

    $client = $_SESSION['user'];
    $idBooking = (int) ($_POST['bookingId'] ?? 0);

    try {
      $idCredit = $this->creditService->requestCredit($idBooking, $client->getIdClient());
      redirect_to('credit', 'detail', ['id' => $idCredit, 'created' => '1']);
    } catch (BusinessRuleException $e) {
      redirect_to('booking', 'detail', ['id' => $idBooking, 'creditError' => $e->getMessage()]);
    }
  }

  // =========================================================
  // CLIENTE: REGISTRAR UN ABONO
  // =========================================================
  public function pay(): void
  {
    require_role('client');
    require_post();

    $client = $_SESSION['user'];
    $idCredit = (int) ($_POST['creditId'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    $credit = $this->creditService->findById($idCredit);

    if ($credit === null || $credit->getIdClient() !== $client->getIdClient()) {
      redirect_to('credit', 'clientList');
    }

    try {
      $this->creditService->registerPayment($idCredit, $amount, (int) $client->getIdRol(), $note);
      redirect_to('credit', 'detail', ['id' => $idCredit, 'paid' => '1']);
    } catch (BusinessRuleException $e) {
      redirect_to('credit', 'detail', ['id' => $idCredit, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // DETALLE DE UN CRÉDITO (cliente dueño, propietario o admin)
  // =========================================================
  public function detail(): void
  {
    require_login();

    $idCredit = (int) ($_GET['id'] ?? 0);
    $credit = $this->creditService->findById($idCredit);

    if ($credit === null) {
      redirect_to('venue', 'catalog');
    }

    $type = $_SESSION['type'] ?? null;
    $user = $_SESSION['user'];

    $allowed = false;
    if ($type === 'admin') {
      $allowed = true;
    } elseif ($type === 'client' && $credit->getIdClient() === $user->getIdClient()) {
      $allowed = true;
    } elseif ($type === 'owner' && $credit->getIdOwner() === $user->getIdOwner()) {
      $allowed = true;
    }

    if (!$allowed) {
      redirect_to('venue', 'catalog');
    }

    $payments = $this->creditService->paymentsForCredit($idCredit);
    $effectiveState = $this->creditService->effectiveState($credit->getState(), $credit->getDueDate());
    $error = $_GET['error'] ?? null;

    require_once __DIR__ . '/../View/Credit/Detail.php';
  }

  // =========================================================
  // ADMIN: TODAS LAS CUENTAS
  // =========================================================
  public function adminList(): void
  {
    require_role('admin');

    $credits = $this->creditService->listAll();

    require_once __DIR__ . '/../View/Credit/AdminList.php';
  }
}
