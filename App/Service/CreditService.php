<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/EarningService.php';
require_once __DIR__ . '/../Repository/CreditConfigRepository.php';
require_once __DIR__ . '/../Repository/CreditRepository.php';
require_once __DIR__ . '/../Repository/CreditPaymentRepository.php';
require_once __DIR__ . '/../Repository/CreditInstallmentRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Model/CreditConfig.php';
require_once __DIR__ . '/../Model/Credit.php';
require_once __DIR__ . '/../Model/CreditPayment.php';
require_once __DIR__ . '/../Model/CreditInstallment.php';

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
  private CreditInstallmentRepository $installmentRepo;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private InvoiceRepository $invoiceRepo;
  private BookingService $bookingService;
  private EarningService $earningService;
  private NotificationService $notificationService;

  public function __construct(?PDO $connection = null)
  {
    $this->connection = $connection ?? DataBase::getConnection();

    $this->configRepo = new CreditConfigRepository($this->connection);
    $this->creditRepo = new CreditRepository($this->connection);
    $this->paymentRepo = new CreditPaymentRepository($this->connection);
    $this->installmentRepo = new CreditInstallmentRepository($this->connection);
    $this->bookingRepo = new BookingRepository($this->connection);
    $this->venueRepo = new VenueRepository($this->connection);
    $this->invoiceRepo = new InvoiceRepository($this->connection);
    $this->bookingService = new BookingService();
    $this->earningService = new EarningService($this->connection);
    $this->notificationService = new NotificationService(
      new NotificationRepository($this->connection)
    );
  }

  // =========================================================
  // CONFIGURACIÓN DEL PROPIETARIO
  // =========================================================
  public function getConfig(int $idOwner): CreditConfig
  {
    return $this->configRepo->findByOwner($idOwner)
      ?? new CreditConfig(idOwner: $idOwner);
  }

  public function saveConfig(
    int $idOwner,
    bool $enabled,
    int $days,
    int $frequentMin,
    int $installments = 1,
    string $frequency = 'mensual',
    int $graceDays = 0,
    int $reminderDays = 3
  ): void {
    if ($days < 1 || $days > 365) {
      throw new BusinessRuleException('El plazo de crédito debe estar entre 1 y 365 días.');
    }

    if ($frequentMin < 0 || $frequentMin > 1000) {
      throw new BusinessRuleException('El mínimo de reservas para cliente frecuente no es válido.');
    }

    if ($installments < 1 || $installments > 36) {
      throw new BusinessRuleException('La cantidad de cuotas debe estar entre 1 y 36.');
    }

    if (!in_array($frequency, ['quincenal', 'mensual'], true)) {
      throw new BusinessRuleException('La frecuencia de cuotas no es válida.');
    }

    if ($graceDays < 0 || $graceDays > 30) {
      throw new BusinessRuleException('Los días de gracia deben estar entre 0 y 30.');
    }

    if ($reminderDays < 0 || $reminderDays > 30) {
      throw new BusinessRuleException('Los días de recordatorio deben estar entre 0 y 30.');
    }

    $config = $this->configRepo->findByOwner($idOwner);

    if ($config === null) {
      $this->configRepo->save(new CreditConfig(
        idOwner: $idOwner,
        enabled: $enabled,
        days: $days,
        frequentMin: $frequentMin,
        installments: $installments,
        frequency: $frequency,
        graceDays: $graceDays,
        reminderDays: $reminderDays
      ));
      return;
    }

    $config->setEnabled($enabled);
    $config->setDays($days);
    $config->setFrequentMin($frequentMin);
    $config->setInstallments($installments);
    $config->setFrequency($frequency);
    $config->setGraceDays($graceDays);
    $config->setReminderDays($reminderDays);
    $this->configRepo->update($config);
  }

  // =========================================================
  // ¿EL CLIENTE ES FRECUENTE?
  // Cuenta solo las reservas CONFIRMADAS del cliente en los locales
  // del propietario que ofrece el crédito.
  // =========================================================
  public function isFrequent(int $idClient, int $idOwner, int $minBookings): bool
  {
    if ($minBookings <= 0) {
      return true;
    }

    return $this->bookingRepo->countConfirmedByClientAndOwner($idClient, $idOwner) >= $minBookings;
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

    if (!$this->isFrequent($idClient, $venue->getIdOwner(), $config->getFrequentMin())) {
      return [
        'allowed' => false,
        'reason'  => 'El crédito es solo para clientes frecuentes (al menos ' . $config->getFrequentMin() . ' reservas confirmadas).',
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
    if ($booking !== null && $this->bookingRepo->hasActiveRangeConflict(
      $booking->getIdLocal(),
      $booking->getBookingDate(),
      $booking->getBookingEndDate() ?? $booking->getBookingDate(),
      $idBooking,
      $booking->getRentalBlockId()
    )) {
      throw new BusinessRuleException('Fecha no disponible. Cambia las fechas de la reserva antes de solicitar crédito.');
    }
    $venue = $this->venueRepo->findById($booking->getIdLocal());
    $config = $check['config'];

    $totals = $this->bookingService->calculateTotals($idBooking);
    $total = round((float) $totals['total'], 2);

    if ($total <= 0) {
      throw new BusinessRuleException('El monto de la reserva debe ser mayor a 0 para otorgar crédito.');
    }

    $installments = $this->buildInstallments($total, $config);
    $dueDate = $installments !== [] ? end($installments)->getDueDate() : date('Y-m-d', strtotime('+' . $config->getDays() . ' days'));

    $idCredit = $this->creditRepo->save(new Credit(
      idBooking: $idBooking,
      idClient: $idClient,
      idOwner: $venue->getIdOwner(),
      total: $total,
      balance: $total,
      dueDate: $dueDate,
      state: 'pendiente'
    ));

    $this->installmentRepo->replaceForCredit($idCredit, $installments);

    // La reserva queda confirmada y su factura pasa a estado "credito".
    $this->bookingRepo->updateStatus($idBooking, 'confirmado');

    $invoice = $this->invoiceRepo->findByBooking($idBooking);
    if ($invoice !== null) {
      $this->invoiceRepo->updateStatus($invoice->getIdInvoice(), 'credito');
    }

    $ownerRolePk = $this->notificationService->findRoleIdByOwner($venue->getIdOwner());
    if ($ownerRolePk !== null) {
      $this->notificationService->notify(
        $ownerRolePk,
        'Un cliente solicitó un crédito de ₡ ' . number_format($total, 2) . '.',
        base_url('credit', 'detail', ['id' => $idCredit])
      );
    }

    return $idCredit;
  }

  private function buildInstallments(float $total, CreditConfig $config): array
  {
    $count = max(1, $config->getInstallments());
    $base = $total / $count;
    $isBiweekly = $config->getFrequency() === 'quincenal';
    $installments = [];
    $accumulated = 0.0;
    $start = strtotime(date('Y-m-d'));

    for ($i = 1; $i <= $count; $i++) {
      $amount = $i === $count ? round($total - $accumulated, 2) : round($base, 2);
      $accumulated = round($accumulated + $amount, 2);
      $relative = $isBiweekly ? '+' . (15 * $i) . ' days' : '+' . $i . ' months';
      $dueDate = date('Y-m-d', strtotime($relative, $start));

      $installments[] = new CreditInstallment(
        idInstallment: 0,
        idCredit: 0,
        number: $i,
        amount: $amount,
        dueDate: $dueDate,
        state: 'pendiente'
      );
    }

    return $installments;
  }

  // =========================================================
  // REGISTRAR UN ABONO (pago parcial) DE UN CRÉDITO
  // =========================================================
  public function registerPayment(
    int $idCredit,
    float $amount,
    int $rolePk,
    string $note,
    string $proofFile
  ): void
  {
    $credit = $this->creditRepo->findById($idCredit);

    if ($credit === null || !$credit->getIsActive()) {
      throw new BusinessRuleException('El crédito no existe.');
    }

    if ($credit->getState() === 'pagado') {
      throw new BusinessRuleException('Este crédito ya está cancelado por completo.');
    }

    $amount = round($amount, 2);
    $this->validatePaymentAmount($idCredit, $amount);

    if (trim($proofFile) === '') {
      throw new BusinessRuleException('Debes adjuntar el comprobante del abono.');
    }

    $this->paymentRepo->save(new CreditPayment(
      idCredit: $idCredit,
      amount: $amount,
      note: trim($note),
      idRole: $rolePk,
      proofFile: $proofFile
    ));

    $newBalance = round($credit->getBalance() - $amount, 2);
    if ($newBalance < 0) {
      $newBalance = 0.0;
    }

    $newState = $newBalance <= 0 ? 'pagado' : 'parcial';
    $this->creditRepo->updateBalanceAndState($idCredit, $newBalance, $newState);
    $this->installmentRepo->syncPaidStates(
      $idCredit,
      $this->installmentRepo->totalPaid($idCredit),
      date('Y-m-d')
    );

    if ($newState === 'pagado') {
      $this->settleCredit($credit);
    }
  }

  public function minimumPayment(int $idCredit): float
  {
    $credit = $this->creditRepo->findById($idCredit);
    if ($credit === null || $credit->getBalance() <= 0) {
      return 0.0;
    }

    foreach ($this->installmentRepo->findByCredit($idCredit) as $installment) {
      if ($installment->getState() !== 'pagado') {
        return round(min($credit->getBalance(), $installment->getAmount()), 2);
      }
    }

    return round($credit->getBalance(), 2);
  }

  public function validatePaymentAmount(int $idCredit, float $amount): void
  {
    $credit = $this->creditRepo->findById($idCredit);
    if ($credit === null || !$credit->getIsActive()) {
      throw new BusinessRuleException('El crédito no existe.');
    }
    if ($amount <= 0) {
      throw new BusinessRuleException('El monto del abono debe ser mayor a 0.');
    }
    if ($amount > $credit->getBalance() + 0.001) {
      throw new BusinessRuleException('El abono no puede superar el saldo pendiente de ₡ ' . number_format($credit->getBalance(), 2) . '.');
    }
    $minimum = $this->minimumPayment($idCredit);
    if ($amount + 0.001 < $minimum) {
      throw new BusinessRuleException('El abono debe ser de al menos ₡ ' . number_format($minimum, 2) . '.');
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

    $rolePk = $this->notificationService->findRoleIdByOwner($credit->getIdOwner());
    if ($rolePk !== null) {
      $this->notificationService->notify(
        $rolePk,
        'El crédito #' . $credit->getIdCredit() . ' fue pagado por completo. Puedes considerar desbloquear al cliente.',
        base_url('credit', 'detail', ['id' => $credit->getIdCredit()])
      );
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

  public function installmentsForCredit(int $idCredit): array
  {
    return $this->installmentRepo->findByCredit($idCredit);
  }

  public function overdueCountByOwner(int $idOwner): int
  {
    return $this->installmentRepo->overdueCountByOwner($idOwner);
  }

  public function overdueCountByClient(int $idClient, ?int $idOwner = null): int
  {
    return $this->installmentRepo->overdueCountByClient($idClient, $idOwner);
  }

  public function sendDueReminders(int $idOwner): int
  {
    $config = $this->getConfig($idOwner);
    $credits = $this->creditRepo->findByOwner($idOwner);
    $sent = 0;
    $limit = date('Y-m-d', strtotime('+' . $config->getReminderDays() . ' days'));

    foreach ($credits as $creditRow) {
      if (($creditRow['tbcreditstate'] ?? '') === 'pagado') {
        continue;
      }

      foreach ($this->installmentRepo->findByCredit((int) $creditRow['tbcreditid']) as $installment) {
        if ($installment->getState() === 'pagado') {
          continue;
        }
        if ($installment->getDueDate() <= $limit) {
          $rolePk = $this->notificationService->findRoleIdByClient((int) $creditRow['tbclientid']);
          if ($rolePk !== null) {
            $this->notificationService->notify(
              $rolePk,
              'Tu cuota #' . $installment->getNumber() . ' de ₡ ' . number_format($installment->getAmount(), 2)
                . ' vence el ' . $installment->getDueDate() . '.',
              base_url('credit', 'detail', ['id' => (int) $creditRow['tbcreditid']])
            );
            $sent++;
          }
        }
      }
    }

    return $sent;
  }
}
