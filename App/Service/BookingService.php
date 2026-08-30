<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Model/Booking.php';
require_once __DIR__ . '/../Model/BookingHistory.php';
require_once __DIR__ . '/../Model/BookingRefund.php';
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

    public function __construct()
    {
        $this->connection = DataBase::getConnection();

        $this->bookingRepo = new BookingRepository($this->connection);
        $this->detailRepo = new DetailRepository($this->connection);
        $this->venueRepo = new VenueRepository($this->connection);
        $this->invoiceRepo = new InvoiceRepository($this->connection);
        $this->historyRepo = new BookingHistoryRepository($this->connection);
        $this->refundRepo = new BookingRefundRepository($this->connection);
    }

    public function createBooking(
        int $clientPk,
        int $venuePk,
        string $date,
        ?string $eventType = null
    ): int {
        if ($date < date('Y-m-d')) {
            throw new BusinessRuleException(
                'La fecha de la reserva no puede ser anterior a hoy.'
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
            $this->bookingRepo->hasActiveBookingOnDate(
                $venuePk,
                $date
            )
        ) {
            throw new BusinessRuleException(
                'Este local ya tiene una reserva para esa fecha. Elige otra fecha.'
            );
        }

        $booking = new Booking(
            0,
            $clientPk,
            $venuePk,
            $date,
            'pendiente',
            false
        );

        $this->connection->beginTransaction();

        try {

            $idBooking = $this->bookingRepo->save($booking);

            // Línea base: renta del local (garantiza factura nunca en 0)
            $this->detailRepo->addVenueLine($idBooking, $venue);

            $this->connection->commit();

            return $idBooking;
        } catch (\Throwable $e) {

            $this->connection->rollBack();

            throw $e;
        }
    }

    // Porcentajes de cobro aplicados al total de la reserva
    private const COMMISSION_PCT = 0.05; // Comisión de la plataforma: 5%
    private const TAX_PCT        = 0.13; // Impuesto al valor agregado: 13%

    public function calculateTotal(int $bookingPk): float
    {
        return $this->calculateTotals($bookingPk)['total'];
    }

    /**
     * Calcula el desglose de una reserva:
     *   - subtotal   = suma de las líneas (cantidad x precio - descuento)
     *   - commission = 5% sobre el subtotal
     *   - tax        = 13% de IVA sobre (subtotal + comisión)
     *   - total      = subtotal + comisión + IVA
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

        $commission = round($subtotal * self::COMMISSION_PCT, 2);
        $tax        = round(($subtotal + $commission) * self::TAX_PCT, 2);
        $total      = round($subtotal + $commission + $tax, 2);

        return [
            'subtotal'   => $subtotal,
            'commission' => $commission,
            'tax'        => $tax,
            'total'      => $total,
        ];
    }

    public function confirm(int $bookingPk): void
    {
        if (
            count(
                $this->detailRepo->findByBooking($bookingPk)
            ) === 0
        ) {
            throw new BusinessRuleException(
                'No puedes confirmar una reserva sin detalle.'
            );
        }

        $this->bookingRepo->updateStatus(
            $bookingPk,
            'confirmado'
        );
    }

public function cancel(int $bookingPk): void
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
}
