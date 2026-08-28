<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/Booking.php';

class BookingService
{
    private BookingRepository $bookingRepo;
    private DetailRepository $detailRepo;
    private VenueRepository $venueRepo;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->bookingRepo = new BookingRepository($connection);
        $this->detailRepo = new DetailRepository($connection);
        $this->venueRepo = new VenueRepository($connection);
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

        return $this->bookingRepo->save($booking);
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
                'No puedes confirmar una reserva sin servicios agregados.'
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
}
