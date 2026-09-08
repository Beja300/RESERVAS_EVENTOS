<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../Repository/VenueRatingRepository.php';
require_once __DIR__ . '/../Repository/ServiceRatingRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/HistoryRepository.php';
require_once __DIR__ . '/../Service/BookingTicketService.php';
require_once __DIR__ . '/../Model/Admin.php';
require_once __DIR__ . '/../Model/Client.php';
require_once __DIR__ . '/../Model/Owner.php';
require_once __DIR__ . '/../Model/Role.php';
require_once __DIR__ . '/../Model/Booking.php';
require_once __DIR__ . '/../Model/BookingRefund.php';
require_once __DIR__ . '/../Model/BookingTicket.php';

class AdminService
{
    private AdminRepository $adminRepo;
    private RoleRepository $roleRepo;
    private BookingRepository $bookingRepo;
    private DetailRepository $detailRepo;
    private VenueRepository $venueRepo;
    private ClientRepository $clientRepo;
    private OwnerRepository $ownerRepo;
    private VenueRatingRepository $venueRatingRepo;
    private ServiceRatingRepository $serviceRatingRepo;
    private BookingHistoryRepository $bookingHistoryRepo;
    private BookingRefundRepository $bookingRefundRepo;
    private BookingTicketRepository $bookingTicketRepo;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->adminRepo = new AdminRepository($connection);
        $this->roleRepo = new RoleRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->detailRepo = new DetailRepository($connection);
        $this->venueRepo = new VenueRepository($connection);
        $this->clientRepo = new ClientRepository($connection);
        $this->ownerRepo = new OwnerRepository($connection);
        $this->venueRatingRepo = new VenueRatingRepository($connection);
        $this->serviceRatingRepo = new ServiceRatingRepository($connection);
        $this->bookingHistoryRepo = new BookingHistoryRepository($connection);
        $this->bookingRefundRepo = new BookingRefundRepository($connection);
        $this->bookingTicketRepo = new BookingTicketRepository($connection);
    }

    // =========================================================
    // CONTAR ADMINS ACTIVOS
    // =========================================================
    private function countActiveAdmins(): int
    {
        return count(array_filter(
            $this->adminRepo->findAll(),
            fn(Admin $admin) => $admin->getIsActive()
        ));
    }

    // =========================================================
    // DESACTIVAR
    // =========================================================
    public function desactivate(int $idRole, string $targetType): void
    {
        if ($targetType === 'admin' && $this->countActiveAdmins() <= 1) {
            throw new BusinessRuleException(
                "No puedes desactivar esta cuenta: es el único administrador activo."
            );
        }

        $this->roleRepo->setActive($idRole, false);
    }

    // =========================================================
    // ACTIVAR
    // =========================================================
    public function activate(int $idRole): void
    {
        $this->roleRepo->setActive($idRole, true);
    }

    // =========================================================
    // LISTAR USUARIOS DE CADA ROL (panel del Admin)
    // =========================================================
    public function findAllAdmins(): array
    {
        return $this->adminRepo->findAll();
    }

    public function findAllClients(): array
    {
        return $this->clientRepo->findAll();
    }

    public function findAllOwners(): array
    {
        return $this->ownerRepo->findAll();
    }

    // =========================================================
    // CARGAR USUARIO POR TIPO E ID DE ROL
    // =========================================================
    public function findUserByRoleId(string $type, int $idRole): Admin|Client|Owner|null
    {
        switch ($type) {
            case 'admin':
                return $this->adminRepo->findByRoleId($idRole);
            case 'client':
                return $this->clientRepo->findByRoleId($idRole);
            case 'owner':
                return $this->ownerRepo->findByRoleId($idRole);
        }

        return null;
    }

    // =========================================================
    // GUARDAR DATOS BASE DE UN ROL (admin|client|owner)
    // =========================================================
    public function updateRole(Role $role): void
    {
        $this->roleRepo->update($role);
    }

    // =========================================================
    // GUARDAR DATOS PROPIOS DE UN ADMIN
    // =========================================================
    public function updateAdminProfile(Admin $admin): void
    {
        $this->roleRepo->update($admin);
        $this->adminRepo->updateProfile($admin);
    }

    // =========================================================
    // GUARDAR DATOS PROPIOS DE UN OWNER
    // =========================================================
    public function updateOwnerProfile(Owner $owner): void
    {
        $this->ownerRepo->updateProfile($owner);
    }

    // =========================================================
    // ESTADÍSTICAS DEL DASHBOARD (un solo payload)
    // =========================================================
    public function getDashboardData(string $yearMonth): array
    {
        return [
            'bookings'        => $this->bookingRepo->findByMonth($yearMonth),
            'topVenues'       => $this->bookingRepo->topActiveVenues(5),
            'topServices'     => $this->detailRepo->topRequestedServices(5),
            'stateCounts'     => $this->bookingRepo->countByState($yearMonth),
            'occupancy'       => $this->bookingRepo->occupancyByVenue($yearMonth),
            'clientStats'     => [
                'nuevos'      => $this->clientRepo->countNewThisMonth($yearMonth),
                'recurrentes' => $this->clientRepo->countRecurrentThisMonth($yearMonth),
            ],
            'topClients'      => $this->clientRepo->topByBookings($yearMonth, 5),
            'venueAvg'        => $this->venueRatingRepo->averageStars(),
            'venueReviews'    => $this->venueRatingRepo->countAll(),
            'serviceAvg'      => $this->serviceRatingRepo->averageStars(),
            'serviceReviews'  => $this->serviceRatingRepo->countAll(),
        ];
    }

    // =========================================================
    // HISTORIAL GLOBAL DE ACCIONES DE USUARIOS
    // =========================================================
    public function getAllUserHistory(): array
    {
        $historyRepo = new HistoryRepository();
        return $historyRepo->listAll();
    }

    // =========================================================
    // LECTURAS DE RESERVAS (panel del Admin)
    // =========================================================
    public function getBookingsByMonthWithDetails(string $yearMonth): array
    {
        return $this->bookingRepo->findByMonthWithDetails($yearMonth);
    }

    public function getBookingById(int $bookingPk): ?Booking
    {
        return $this->bookingRepo->findById($bookingPk);
    }

    public function getActiveVenues(): array
    {
        return $this->venueRepo->findActive();
    }

    public function getBookedDates(int $venuePk): array
    {
        return $this->bookingRepo->bookedDatesByVenue($venuePk);
    }

    public function getVenueById(int $venuePk): ?Venue
    {
        return $this->venueRepo->findById($venuePk);
    }

    public function getClientByPk(int $clientPk): ?Client
    {
        return $this->clientRepo->findByClientPk($clientPk);
    }

    public function getBookingHistoryAll(): array
    {
        return $this->bookingHistoryRepo->findAllWithDetails();
    }

    public function getBookingHistoryByBooking(int $bookingPk): array
    {
        return $this->bookingHistoryRepo->findByBooking($bookingPk);
    }

    public function getPendingRefunds(): array
    {
        return $this->bookingRefundRepo->findPending();
    }

    public function getRefundByBooking(int $bookingPk): ?BookingRefund
    {
        return $this->bookingRefundRepo->findByBooking($bookingPk);
    }

    public function getTicketByBooking(int $bookingPk): ?BookingTicket
    {
        return $this->bookingTicketRepo->findByBooking($bookingPk);
    }

    public function getDetailLinesByBooking(int $bookingPk): array
    {
        return $this->detailRepo->findByBooking($bookingPk);
    }
}