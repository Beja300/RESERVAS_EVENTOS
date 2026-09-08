<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/LocationRepository.php';
require_once __DIR__ . '/../Model/Client.php';

/**
 * ClientService — Capa de negocio del cliente.
 *
 * Agenda dashboard, perfil, ubicación y desactivación a partir de
 * repositorios. El controller ya NO accede a repositorios directamente.
 */
class ClientService
{
    private ClientRepository $clientRepo;
    private BookingRepository $bookingRepo;
    private RoleRepository $roleRepo;
    private LocationRepository $locationRepo;

    public function __construct(PDO $connection)
    {
        $this->clientRepo = new ClientRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->roleRepo = new RoleRepository($connection);
        $this->locationRepo = new LocationRepository($connection);
    }

    public function assertCanBook(int $clientPk): void
    {
        $client = $this->clientRepo->findByClientPk($clientPk);
        if ($client === null || !$client->getIsClientActive() || !$client->getIsActive()) {
            throw new BusinessRuleException("Tu cuenta está desactivada; no puedes crear reservas.");
        }
    }

    public function assertOwnsBooking(int $clientPk, int $bookingPk): void
    {
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($booking === null || $booking->getIdClient() !== $clientPk) {
            throw new BusinessRuleException("No tienes permiso sobre esta reserva.");
        }
    }

    // =========================================================
    // DASHBOARD
    // =========================================================
    /**
     * @return Booking[]
     */
    public function getClientBookings(int $clientId): array
    {
        return $this->bookingRepo->findByClient($clientId);
    }

    // =========================================================
    // PERFIL / UBICACIÓN
    // =========================================================
    public function getLocation(?int $locationId): ?object
    {
        if ($locationId === null) {
            return null;
        }
        return $this->locationRepo->findById($locationId);
    }

    public function isValidLocation(int $locationId): bool
    {
        return $this->locationRepo->findById($locationId) !== null;
    }

    /**
     * Persiste los datos del perfil (imagen + ubicación) del cliente.
     */
    public function saveProfile(Client $client, string $image, ?int $locationId): void
    {
        $this->roleRepo->update($client);
        $this->clientRepo->updateProfile($client->getIdClient(), $image, $locationId);
        $client->setImageClient($image);
        $client->setLocationId($locationId);
    }

    public function findLocationIdByParts(string $province, string $canton, string $district): ?int
    {
        return $this->locationRepo->findIdByParts($province, $canton, $district);
    }

    public function findLocationIdByCanton(string $province, string $canton): ?int
    {
        return $this->locationRepo->findIdByCanton($province, $canton);
    }

    // =========================================================
    // DESACTIVACIÓN
    // =========================================================
    public function deactivateAccount(int $roleId): void
    {
        $this->roleRepo->setActive($roleId, false);
    }
}