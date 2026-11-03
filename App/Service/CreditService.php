<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceException.php';
require_once __DIR__ . '/ErrorLogger.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/EarningService.php';
require_once __DIR__ . '/../Repository/CreditConfigRepository.php';
require_once __DIR__ . '/../Repository/CreditRepository.php';
require_once __DIR__ . '/../Repository/CreditPaymentRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Model/CreditConfig.php';
require_once __DIR__ . '/../Model/Credit.php';
require_once __DIR__ . '/../Model/CreditPayment.php';

/**
 * Módulo de crédito: cuentas por cobrar (lo que el cliente debe) y
 * cuentas por pagar (lo que la plataforma/propietario tiene pendiente
 * de cobro). El propietario define el plazo y el umbral de cliente
 * frecuente; el cliente puede financiar sus reservas y abonar.
 */
class CreditService
{
  private PDO $connection;
  private CreditConfigRepository $configRepo;
  private CreditRepository $creditRepo;
  private CreditPaymentRepository $paymentRepo;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private InvoiceRepository $invoiceRepo;
  private BookingService $bookingService;
  private EarningService $earningService;

  public function __construct(?PDO $connection = null)
  {
    $this->connection = $connection ?? DataBase::getConnection();

    $this->configRepo = new CreditConfigRepository($this->connection);
    $this->creditRepo = new CreditRepository($this->connection);
    $this->paymentRepo = new CreditPaymentRepository($this->connection);
    $this->bookingRepo = new BookingRepository($this->connection);
    $this->venueRepo = new VenueRepository($this->connection);
    $this->invoiceRepo = new InvoiceRepository($this->connection);
    $this->bookingService = new BookingService();
    $this->earningService = new EarningService($this->connection);
  }

  // =========================================================
  // CONFIGURACIÓN DEL PROPIETARIO
  // =========================================================
  public function getConfig(int $idOwner): CreditConfig
  {
    return $this->configRepo->findByOwner($idOwner)
      ?? new CreditConfig(idOwner: $idOwner);
  }

  public function saveConfig(int $idOwner, bool $enabled, int $days, int $frequentMin): void
  {
    if ($days < 1 || $days > 365) {
      throw new BusinessRuleException('El plazo de crédito debe estar entre 1 y 365 días.');
    }

    if ($frequentMin < 0 || $frequentMin > 1000) {
      throw new BusinessRuleException('El mínimo de reservas para cliente frecuente debe estar entre 0 y 1000.');
    }

    $config = $this->configRepo->findByOwner($idOwner);

    if ($config === null) {
      $this->configRepo->save(new CreditConfig(
        idOwner: $idOwner,
        enabled: $enabled,
        days: $days,
        frequentMin: $frequentMin
      ));
      return;
    }

    $config->setEnabled($enabled);
    $config->setDays($days);
    $config->setFrequentMin($frequentMin);
    $this->configRepo->update($config);
  }

  // =========================================================
  // ¿EL CLIENTE ES FRECUENTE?
  // =========================================================
  public function isFrequent(int $idClient, int $minBookings): bool
  {
    if ($minBookings <= 0) {
      return true;
    }

    return count($this->bookingRepo->findByClient($idClient)) >= $minBookings;
  }

  // =========================================================
  // ¿PUEDE PEDIR CRÉDITO ESTA RESERVA?
  // Devuelve ['allowed' => bool, 'reason' => string, 'config' => CreditConfig]
  // =========================================================
  public function canRequestCredit(int $idBooking, int $idClient): array
  {
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null || $booking->getIdClient() !== $idClient) {
      return ['allowed' => false, 'reason' => 'La reserva no existe o no te pertenece.', 'config' => null];
    }

    if ($booking->getBookingState() !== 'pendiente') {
      return ['allowed' => false, 'reason' => 'Solo se puede financiar una reserva pendiente.', 'config' => null];
    }

    if ($this->creditRepo->findByBooking($idBooking) !== null) {
      return ['allowed' => false, 'reason' => 'Esta reserva ya tiene un crédito asociado.', 'config' => null];
    }

    $venue = $this->venueRepo->findById($booking->getIdLocal());

    if ($venue === null) {
      return ['allowed' => false, 'reason' => 'El local de la reserva no existe.', 'config' => null];
    }

    $config = $this->getConfig($venue->getIdOwner());

    if (!$config->isEnabled()) {
      return ['allowed' => false, 'reason' => 'El propietario no ofrece crédito para este local.', 'config' => $config];
    }

    if (!$this->isFrequent($idClient, $config->getFrequentMin())) {
      return [
        'allowed' => false,
        'reason'  => 'El crédito es solo para clientes frecuentes (al menos ' . $config->getFrequentMin() . ' reservas).',
        'config'  => $config,
      ];
    }

    return ['allowed' => true, 'reason' => '', 'config' => $config];
  }

  // =========================================================
  // OTORGAR CRÉDITO SOBRE UNA RESERVA
  // =========================================================
  public function requestCredit(int $idBooking, int $idClient): int
  {
    $check = $this->canRequestCredit($idBooking, $idClient);

    if (!$check['allowed']) {
      throw new BusinessRuleException($check['reason']);
    }

    $booking = $this->bookingRepo->findById($idBooking);
    $venue = $this->venueRepo->findById($booking->getIdLocal());
    $config = $check['config'];

    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = round((float) $totals['total'], 2);

    if ($total <= 0) {
      throw new BusinessRuleException('El monto de la reserva debe ser mayor a 0 para otorgar crédito.');
    }

    $dueDate = date('Y-m-d', strtotime('+' . $config->getDays() . ' days'));

    DataBase::beginTransaction($this->connection);

    try {
      $idCredit = $this->creditRepo->save(new Credit(
        idBooking: $idBooking,
        idClient: $idClient,
        idOwner: $venue->getIdOwner(),
        total: $total,
        balance: $total,
        dueDate: $dueDate,
        state: 'pendiente'
      ));

      // La reserva queda confirmada y su factura pasa a estado "credito".
      $this->bookingRepo->updateStatus($idBooking, 'confirmado');

      $invoice = $this->invoiceRepo->findByBooking($idBooking);
      if ($invoice !== null) {
        $this->invoiceRepo->updateStatus($invoice->getIdInvoice(), 'credito');
      }

      DataBase::commit();

      return $idCredit;
    } catch (\Throwable $e) {
      DataBase::rollBack();

      if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
        throw $e;
      }

      ErrorLogger::log($e, __METHOD__);
      throw new ServiceException(
        'No se pudo completar la operación. Intenta de nuevo.',
        0,
        $e
      );
    }
  }

  // =========================================================
  // REGISTRAR UN ABONO (pago parcial) DE UN CRÉDITO
  // =========================================================
  public function registerPayment(int $idCredit, float $amount, int $rolePk, string $note = ''): void
  {
    $credit = $this->creditRepo->findById($idCredit);

    if ($credit === null || !$credit->getIsActive()) {
      throw new BusinessRuleException('El crédito no existe.');
    }

    if ($credit->getState() === 'pagado') {
      throw new BusinessRuleException('Este crédito ya está cancelado por completo.');
    }

    $amount = round($amount, 2);

    if ($amount <= 0) {
      throw new BusinessRuleException('El monto del abono debe ser mayor a 0.');
    }

    if ($amount > $credit->getBalance() + 0.001) {
      throw new BusinessRuleException('El abono no puede superar el saldo pendiente de ₡ ' . number_format($credit->getBalance(), 2) . '.');
    }

    DataBase::beginTransaction($this->connection);

    try {
      $this->paymentRepo->save(new CreditPayment(
        idCredit: $idCredit,
        amount: $amount,
        note: trim($note),
        idRole: $rolePk
      ));

      $newBalance = round($credit->getBalance() - $amount, 2);
      if ($newBalance < 0) {
        $newBalance = 0.0;
      }

      $newState = $newBalance <= 0 ? 'pagado' : 'parcial';
      $this->creditRepo->updateBalanceAndState($idCredit, $newBalance, $newState);

      if ($newState === 'pagado') {
        $this->settleCredit($credit);
      }

      DataBase::commit();
    } catch (\Throwable $e) {
      DataBase::rollBack();

      if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
        throw $e;
      }

      ErrorLogger::log($e, __METHOD__);
      throw new ServiceException(
        'No se pudo completar la operación. Intenta de nuevo.',
        0,
        $e
      );
    }
  }

  // =========================================================
  // CIERRE DEL CRÉDITO: factura pagada + ingreso del propietario
  // =========================================================
  private function settleCredit(Credit $credit): void
  {
    $idBooking = $credit->getIdBooking();

    $invoice = $this->invoiceRepo->findByBooking($idBooking);
    if ($invoice !== null && $invoice->getStatusInvoice() !== 'pagado') {
      $this->invoiceRepo->updateStatus($invoice->getIdInvoice(), 'pagado');
    }

    if ($this->earningService->findByBooking($idBooking) === null) {
      $totals = $this->bookingService->calculateTotals($idBooking);
      $this->earningService->recordEarning($idBooking, $totals, $credit->getIdOwner());
    }
  }

  // =========================================================
  // ESTADO EFECTIVO (marca "vencido" si pasó la fecha de pago)
  // =========================================================
  public function effectiveState(string $state, string $dueDate): string
  {
    if ($state === 'pagado') {
      return 'pagado';
    }

    return $dueDate !== '' && $dueDate < date('Y-m-d') ? 'vencido' : $state;
  }

  // =========================================================
  // CONSULTAS DE LISTADO
  // =========================================================
  public function findById(int $idCredit): ?Credit
  {
    return $this->creditRepo->findById($idCredit);
  }

  public function findByBooking(int $idBooking): ?Credit
  {
    return $this->creditRepo->findByBooking($idBooking);
  }

  public function listForClient(int $idClient): array
  {
    return $this->creditRepo->findByClient($idClient);
  }

  public function listForOwner(int $idOwner): array
  {
    return $this->creditRepo->findByOwner($idOwner);
  }

  public function listAll(): array
  {
    return $this->creditRepo->findAll();
  }

  public function paymentsForCredit(int $idCredit): array
  {
    return $this->paymentRepo->findByCredit($idCredit);
  }

  public function outstandingByClient(int $idClient): float
  {
    return $this->creditRepo->sumOutstandingByClient($idClient);
  }

  public function outstandingByOwner(int $idOwner): float
  {
    return $this->creditRepo->sumOutstandingByOwner($idOwner);
  }
}
