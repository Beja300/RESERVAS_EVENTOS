<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Model/Booking.php';
require_once __DIR__ . '/../Model/BookingHistory.php';
require_once __DIR__ . '/../Model/BookingRefund.php';
require_once __DIR__ . '/CommissionConfigService.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class BookingService
{
    private PDO $connection;
    private BookingRepository $bookingRepo;
    private DetailRepository $detailRepo;
    private VenueRepository $venueRepo;
    private InvoiceRepository $invoiceRepo;
    private BookingHistoryRepository $historyRepo;
    private BookingRefundRepository $refundRepo;
    private BookingTicketRepository $ticketRepo;
    private ClientRepository $clientRepo;
    private OwnerRepository $ownerRepo;
    private PaymentMethodRepository $paymentMethodRepo;
    private ServiceRepository $serviceRepo;
    private CommissionConfigService $configService;

    public function __construct()
    {
        $this->connection = DataBase::getConnection();

        $this->bookingRepo = new BookingRepository($this->connection);
        $this->detailRepo = new DetailRepository($this->connection);
        $this->venueRepo = new VenueRepository($this->connection);
        $this->invoiceRepo = new InvoiceRepository($this->connection);
        $this->historyRepo = new BookingHistoryRepository($this->connection);
        $this->refundRepo = new BookingRefundRepository($this->connection);
        $this->ticketRepo = new BookingTicketRepository($this->connection);
        $this->clientRepo = new ClientRepository($this->connection);
        $this->ownerRepo = new OwnerRepository($this->connection);
        $this->paymentMethodRepo = new PaymentMethodRepository($this->connection);
        $this->serviceRepo = new ServiceRepository($this->connection);
        $this->configService = new CommissionConfigService(
            new CommissionConfigRepository($this->connection)
        );
    }

    public function createBooking(
        int $clientPk,
        int $venuePk,
        string $date,
        ?string $endDate = null,
        ?string $eventType = null,
        ?string $eventDetail = null
    ): int {
        if ($date < date('Y-m-d')) {
            throw new BusinessRuleException(
                'La fecha de inicio de la reserva no puede ser anterior a hoy.'
            );
        }

        if ($endDate === null || $endDate === '') {
            throw new BusinessRuleException(
                'Debes indicar la fecha final de la reserva.'
            );
        }

        if ($endDate < $date) {
            throw new BusinessRuleException(
                'La fecha final no puede ser anterior a la fecha de inicio.'
            );
        }

        $days = (int) ((strtotime($endDate) - strtotime($date)) / 86400) + 1;

        if ($days < 1) {
            throw new BusinessRuleException(
                'La reserva debe cubrir al menos un día.'
            );
        }

        $venue = $this->venueRepo->findById($venuePk);

        if ($venue === null || !$venue->getIsActive()) {
            throw new BusinessRuleException(
                'Este local no está disponible para reservas.'
            );
        }

        if ($venue->getPriceVenue() <= 0) {
            throw new BusinessRuleException(
                'Este local no tiene un precio de renta configurado. Contacta al propietario.'
            );
        }

        if (
            $this->bookingRepo->hasActiveRangeConflict(
                $venuePk,
                $date,
                $endDate
            )
        ) {
            throw new BusinessRuleException(
                'Este local ya tiene una reserva que se cruza con ese rango de fechas. Elige otro rango.'
            );
        }

        $booking = new Booking(
            0,
            $clientPk,
            $venuePk,
            $date,
            'pendiente',
            false,
            $endDate,
            $eventType,
            $eventDetail
        );

        $this->connection->beginTransaction();

        try {

            $idBooking = $this->bookingRepo->save($booking);

            // Línea base: renta del local por el número de días
            // (garantiza factura nunca en 0 y acumula precio por día)
            $this->detailRepo->addVenueLine($idBooking, $venue, $days);

            $this->connection->commit();

            return $idBooking;
        } catch (\Throwable $e) {

            $this->connection->rollBack();

            throw $e;
        }
    }

    /**
     * Desglose de una reserva (fórmula única de la plataforma):
     *   - subtotal   = suma de las líneas (cantidad x precio - descuento)
     *   - commission = comisión de la plataforma sobre el subtotal
     *   - tax        = IVA que paga el cliente sobre el subtotal
     *   - total      = lo que paga el cliente (subtotal + IVA)
     *   - ownerAmount = total - comisión - IVA = subtotal - comisión
     * Las tasas se leen de la configuración vigente (tbcommissionconfig).
     */
    public function calculateTotals(int $bookingPk): array
    {
        $subtotal = 0.0;

        foreach (
            $this->detailRepo->findByBooking($bookingPk)
            as $line
        ) {
            $subtotal += $line->getSubtotal();
        }

        $config = $this->configService->getActive();

        $commission = round($subtotal * ($config->getPercentage() / 100), 2);
        $tax        = round($subtotal * ($config->getTax() / 100), 2);
        $total      = round($subtotal + $tax, 2);

        return [
            'subtotal'     => $subtotal,
            'commission'   => $commission,
            'tax'          => $tax,
            'total'        => $total,
            'commissionPct' => $config->getPercentage(),
            'taxPct'       => $config->getTax(),
        ];
    }

public function cancel(int $bookingPk): Booking
  {
    $booking =
      $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException(
        'La reserva no existe.'
      );
    }

    if (
      $booking->getBookingState() !== 'pendiente'
    ) {
      throw new BusinessRuleException(
        'Solo se pueden cancelar reservas pendientes.'
      );
    }

    $this->bookingRepo->updateStatus(
      $bookingPk,
      'cancelado'
    );

    return $booking;
  }

  // =========================================================
  // SOLICITAR REEMBOLSO (cliente -> administrador la valida)
  // El cliente debe dar un motivo válido (mín. 10 caracteres).
  // =========================================================
  public function requestRefund(int $bookingPk, int $clientRoleId, string $motivo): void
  {
    $motivo = trim($motivo);

    $length = function_exists('mb_strlen') ? mb_strlen($motivo) : strlen($motivo);

    if ($length < 10) {
      throw new BusinessRuleException(
        'Debes indicar un motivo válido para el reembolso (mínimo 10 caracteres).'
      );
    }

    $booking = $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    if (in_array($booking->getBookingState(), ['cancelado', 'rechazado'], true)) {
      throw new BusinessRuleException('Esta reserva ya no puede solicitar un reembolso.');
    }

    if ($this->invoiceRepo->findByBooking($bookingPk) === null) {
      throw new BusinessRuleException(
        'Solo se puede solicitar un reembolso si la reserva tiene un pago registrado.'
      );
    }

    $existing = $this->refundRepo->findByBooking($bookingPk);

    if ($existing !== null && in_array($existing->getState(), ['pendiente', 'aprobado'], true)) {
      throw new BusinessRuleException(
        'Esta reserva ya tiene una solicitud de reembolso en curso.'
      );
    }

    $this->refundRepo->save(
      new BookingRefund(
        id: 0,
        idBooking: $bookingPk,
        clientRoleId: $clientRoleId,
        detail: $motivo,
        state: 'pendiente'
      )
    );

    $this->historyRepo->save(
      new BookingHistory(
        id: 0,
        idBooking: $bookingPk,
        roleId: $clientRoleId,
        action: 'SOLICITUD_REEMBOLSO',
        detail: $motivo
      )
    );
  }

  // =========================================================
  // LECTURAS DE DOMINIO (fachada para los controladores)
  // El controller ya NO accede a repositorios directamente.
  // =========================================================
  public function getBooking(int $bookingPk): ?Booking
  {
    return $this->bookingRepo->findById($bookingPk);
  }

  public function getVenue(int $venuePk): ?Venue
  {
    return $this->venueRepo->findById($venuePk);
  }

  public function getClient(int $clientPk): ?Client
  {
    return $this->clientRepo->findByClientPk($clientPk);
  }

  public function getOwner(int $ownerPk): ?Owner
  {
    return $this->ownerRepo->findByOwnerPk($ownerPk);
  }

  public function getTicketForBooking(int $bookingPk): ?BookingTicket
  {
    return $this->ticketRepo->findByBooking($bookingPk);
  }

  public function getRefundForBooking(int $bookingPk): ?BookingRefund
  {
    return $this->refundRepo->findByBooking($bookingPk);
  }

  public function getDetailsForBooking(int $bookingPk): array
  {
    return $this->detailRepo->findByBooking($bookingPk);
  }

  public function getService(int $servicePk): ?Service
  {
    return $this->serviceRepo->findById($servicePk);
  }

  public function getBookedDates(int $venuePk): array
  {
    return $this->bookingRepo->bookedDatesByVenue($venuePk);
  }

  public function getBookingsByVenue(int $venuePk): array
  {
    return $this->bookingRepo->findByVenue($venuePk);
  }

  public function getBookingsByClient(int $clientPk): array
  {
    return $this->bookingRepo->findByClient($clientPk);
  }

  public function getPendingBookingsByOwner(int $ownerPk): array
  {
    return $this->bookingRepo->findPendingByOwner($ownerPk);
  }

  public function getActivePaymentMethods(): array
  {
    return $this->paymentMethodRepo->findActive();
  }
}
