<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Model/ClientRepository.php';
require_once __DIR__ . '/../Model/BookingRepository.php';

class ClientService
{
    private ClientRepository $clientRepo;
    private BookingRepository $bookingRepo;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
        $this->bookingRepo = new BookingRepository();
    }

    public function assertCanBook(int $clientPk): void
    {
        $client = $this->clientRepo->findByClientPk($clientPk);
        if ($client === null || !$client->isClientActive() || !$client->isActive()) {
            throw new BusinessRuleException("Tu cuenta está desactivada; no puedes crear reservas.");
        }
    }

    public function assertOwnsBooking(int $clientPk, int $bookingPk): void
    {
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($booking === null || $booking->getClientFk() !== $clientPk) {
            throw new BusinessRuleException("No tienes permiso sobre esta reserva.");
        }
    }
}