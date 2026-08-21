<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/../Model/DetailRepository.php';
require_once __DIR__ . '/../Model/ServiceRepository.php';
require_once __DIR__ . '/../Model/BookingRepository.php';
require_once __DIR__ . '/../Model/Detail.php';

class DetailService
{
    private DetailRepository $detailRepo;
    private ServiceRepository $serviceRepo;
    private BookingRepository $bookingRepo;
    private ServiceService $serviceService;

    public function __construct()
    {
        $this->detailRepo = new DetailRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->bookingRepo = new BookingRepository();
        $this->serviceService = new ServiceService();
    }

    public function addLine(int $bookingPk, int $servicePk, int $quantity): int
    {
        if ($quantity <= 0) {
            throw new BusinessRuleException("La cantidad debe ser mayor a 0.");
        }

        $this->serviceService->assertCanBeBooked($servicePk);

        $service = $this->serviceRepo->findById($servicePk);
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($service->getVenueFk() !== $booking->getVenueFk()) {
            throw new BusinessRuleException("Este servicio no pertenece al local de la reserva.");
        }

        // El precio se copia AQUí, al momento de agregarlo -- si el
        // negocio sube el precio después, no afecta esta reserva.
        $detail = new Detail($bookingPk, $servicePk, $quantity, $service->getPrice());
        return $this->detailRepo->save($detail);
    }
}