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
        $this->bookingRepo = new BookingRepository();
        $this->detailRepo = new DetailRepository();
        $this->venueRepo = new VenueRepository();
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

        if ($venue === null || !$venue->isActive()) {
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
            $clientPk,
            $venuePk,
            $date,
            $eventType,
            status: 'pendiente'
        );

        return $this->bookingRepo->save($booking);
    }

    public function calculateTotal(int $bookingPk): float
    {
        $total = 0.0;

        foreach (
            $this->detailRepo->findByBooking($bookingPk)
            as $line
        ) {
            $total += $line->getSubtotal();
        }

        return $total;
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
            $booking->getStatus() !== 'pendiente'
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
