<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/EarningRepository.php';
require_once __DIR__ . '/../Repository/VenueRatingRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Model/Owner.php';

/**
 * OwnerService — Capa de negocio del propietario.
 *
 * Agenda la actividad del propietario (dashboard), sus perfiles y su
 * desactivación a partir de repositorios. El controller ya NO accede a
 * repositorios directamente: solo a este servicio.
 */
class OwnerService
{
    private VenueRepository $venueRepo;
    private BookingRepository $bookingRepo;
    private BookingTicketRepository $ticketRepo;
    private EarningRepository $earningRepo;
    private VenueRatingRepository $venueRatingRepo;
    private RoleRepository $roleRepo;
    private OwnerRepository $ownerRepo;
    private PaymentMethodRepository $paymentMethodRepo;
    private DetailRepository $detailRepo;

    public function __construct(PDO $connection)
    {
        $this->venueRepo = new VenueRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->ticketRepo = new BookingTicketRepository($connection);
        $this->earningRepo = new EarningRepository($connection);
        $this->venueRatingRepo = new VenueRatingRepository($connection);
        $this->roleRepo = new RoleRepository($connection);
        $this->ownerRepo = new OwnerRepository($connection);
        $this->paymentMethodRepo = new PaymentMethodRepository($connection);
        $this->detailRepo = new DetailRepository($connection);
    }

    public function hasActiveVenue(int $ownerPk): bool
    {
        foreach ($this->venueRepo->findByOwner($ownerPk) as $venue) {
            if ($venue->getIsActive()) {
                return true;
            }
        }
        return false;
    }

    public function assertOwnsVenue(int $ownerPk, int $venuePk): void
    {
        $venue = $this->venueRepo->findById($venuePk);
        if ($venue === null || $venue->getIdOwner() !== $ownerPk) {
            throw new BusinessRuleException("No tienes permiso sobre este local.");
        }
    }

    // =========================================================
    // DASHBOARD
    // =========================================================
    /**
     * Datos agregados del panel del propietario.
     *
     * @return array{venues: array, bookings: array, stats: array,
     *               topVenue: array, topServices: array}
     */
    public function dashboardSummary(int $ownerId, string $yearMonth): array
    {
        $venues = $this->venueRepo->findByOwner($ownerId);

        $bookings = [];
        foreach ($venues as $venue) {
            $bookings[$venue->getIdVenue()] = $this->bookingRepo->findByVenue($venue->getIdVenue());
        }

        $earnings = $this->earningRepo->totalsByOwnerForMonth($ownerId, $yearMonth);
        $nextBooking = $this->bookingRepo->nextBookingByOwner($ownerId, date('Y-m-d'));
        $averageRating = $this->venueRatingRepo->findAverageByOwner($ownerId);

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $stats = [
            'locales'       => count($venues),
            'porRevisar'    => $this->ticketRepo->countPendingByOwner($ownerId),
            'ganancias'     => $earnings['ownerAmount'],
            'comision'      => $earnings['commission'],
            'totalBruto'    => $earnings['total'],
            'reservasMes'   => $this->bookingRepo->countByOwnerForMonth($ownerId, $yearMonth),
            'proximaReserva'=> $nextBooking,
            'rating'        => $averageRating,
            'monthLabel'    => $monthNames[(int) substr($yearMonth, 5, 2)] . ' ' . substr($yearMonth, 0, 4),
        ];

        return [
            'venues'      => $venues,
            'bookings'    => $bookings,
            'stats'       => $stats,
            'topVenue'    => $this->bookingRepo->topVenuesByOwner($ownerId, $yearMonth, 1),
            'topServices' => $this->detailRepo->topServicesByOwner($ownerId, $yearMonth, 3),
        ];
    }

    // =========================================================
    // PERFIL
    // =========================================================
    public function saveProfile(Owner $owner): void
    {
        $this->roleRepo->update($owner);
        $this->ownerRepo->updateProfile($owner);
    }

    public function removeProfilePhoto(Owner $owner): void
    {
        $owner->setImageOwner('');
        $this->ownerRepo->updateProfile($owner);
    }

    // =========================================================
    // DESACTIVACIÓN DE CUENTA
    // =========================================================
    public function deactivateProfile(int $ownerId, int $roleId): void
    {
        if ($this->bookingRepo->hasUpcomingActiveByOwner($ownerId)) {
            throw new BusinessRuleException(
                'No puedes desactivar tu perfil mientras tengas reservas confirmadas o pendientes con fecha de hoy o futura.'
            );
        }

        $this->roleRepo->setActive($roleId, false);
    }

    // =========================================================
    // DATOS DE COBRO
    // =========================================================
    /**
     * @return PaymentMethod[]
     */
    public function getActivePaymentMethods(): array
    {
        return $this->paymentMethodRepo->findActive();
    }

    // =========================================================
    // LECTURA DE UN PROPIETARIO
    // =========================================================
    public function getOwner(int $ownerPk): ?Owner
    {
        return $this->ownerRepo->findByOwnerPk($ownerPk);
    }
}