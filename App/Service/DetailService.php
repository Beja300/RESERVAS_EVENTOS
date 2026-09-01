<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Model/Detail.php';

class DetailService {
    private DetailRepository $detailRepo;
    private ServiceRepository $serviceRepo;
    private BookingRepository $bookingRepo;
    private BookingTicketRepository $ticketRepo;
    private ServiceService $serviceService;

    public function __construct(PDO $connection) {
        $this->detailRepo = new DetailRepository($connection);
        $this->serviceRepo = new ServiceRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->ticketRepo = new BookingTicketRepository($connection);
        $this->serviceService = new ServiceService(new ServiceRepository($connection));
    }

    public function assertCanModify(int $bookingPk): void
    {
        $booking = $this->bookingRepo->findById($bookingPk);

        if ($booking === null) {
            throw new BusinessRuleException("La reserva no existe.");
        }

        if ($booking->getBookingState() !== 'pendiente') {
            throw new BusinessRuleException("Solo se pueden modificar los servicios de una reserva pendiente.");
        }

        $ticket = $this->ticketRepo->findByBooking($bookingPk);

        if ($ticket !== null) {
            throw new BusinessRuleException(
                "Ya subiste el comprobante de pago; no puedes agregar más servicios."
            );
        }
    }

    public function addLine(int $bookingPk, int $servicePk, int $quantity): int {
        if ($quantity <= 0) {
            throw new BusinessRuleException("La cantidad debe ser mayor a 0.");
        }

        $this->assertCanModify($bookingPk);

        $this->serviceService->assertCanBeBooked($servicePk);

        $service = $this->serviceRepo->findById($servicePk);
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($service->getIdLocal() !== $booking->getIdLocal()) {
            throw new BusinessRuleException("Este servicio no pertenece al local de la reserva.");
        }

        $detail = new Detail(
            idDetail: 0,
            idClientBooking: $bookingPk,
            idLocalService: $servicePk,
            quantityDetail: $quantity,
            unitPrice: $service->getPriceService(),
            discount: 0.0,
            isActiveDetail: true
        );

        return $this->detailRepo->save($detail);
    }
}
