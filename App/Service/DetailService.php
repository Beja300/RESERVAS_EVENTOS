<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/Detail.php';

class DetailService {
    private PDO $connection;
    private DetailRepository $detailRepo;
    private ServiceRepository $serviceRepo;
    private BookingRepository $bookingRepo;
    private BookingTicketRepository $ticketRepo;
    private VenueRepository $venueRepo;
    private ServiceService $serviceService;

    public function __construct(PDO $connection) {
        $this->connection = $connection;
        $this->detailRepo = new DetailRepository($connection);
        $this->serviceRepo = new ServiceRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->ticketRepo = new BookingTicketRepository($connection);
        $this->venueRepo = new VenueRepository($connection);
        $this->serviceService = new ServiceService(new ServiceRepository($connection));
    }

    public function assertCanModify(int $bookingPk, bool $allowDateCorrection = false): void
    {
        $booking = $this->bookingRepo->findById($bookingPk);

        if ($booking === null) {
            throw new BusinessRuleException("La reserva no existe.");
        }

        if ($booking->getBookingState() !== 'pendiente') {
            throw new BusinessRuleException("Solo se puede editar una reserva pendiente.");
        }

        $ticket = $this->ticketRepo->findByBooking($bookingPk);

        if ($ticket !== null) {
            throw new BusinessRuleException(
                "Ya subiste el comprobante de pago; no puedes editar la reserva."
            );
        }

        if (!$allowDateCorrection && $this->bookingRepo->hasActiveRangeConflict(
            $booking->getIdLocal(),
            $booking->getBookingDate(),
            $booking->getBookingEndDate() ?? $booking->getBookingDate(),
            $bookingPk,
            $booking->getRentalBlockId()
        )) {
            throw new BusinessRuleException('Fecha no disponible. Cambia las fechas de la reserva antes de editar servicios o pagar.');
        }
    }

    public function addLine(int $bookingPk, int $servicePk, int $quantity): int {
        if ($quantity <= 0) {
            throw new BusinessRuleException("La cantidad debe ser mayor a 0.");
        }

        $this->assertCanModify($bookingPk, true);

        $this->serviceService->assertCanBeBooked($servicePk);

        $service = $this->serviceRepo->findById($servicePk);
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($service === null || $booking === null) {
            throw new BusinessRuleException("El servicio o la reserva no existen.");
        }
        if ($service->getIdLocal() !== $booking->getIdLocal()) {
            throw new BusinessRuleException("Este servicio no pertenece al local de la reserva.");
        }

        $existingQuantity = 0;
        foreach ($this->detailRepo->findByBooking($bookingPk) as $line) {
            if ($line->getIdLocalService() === $servicePk) {
                $existingQuantity += $line->getQuantityDetail();
            }
        }

        $this->serviceService->assertQuantityAllowed($service, $quantity, $existingQuantity);
        $discount = $this->serviceService->lineDiscount($service, $quantity, $existingQuantity);

        $detail = new Detail(
            idDetail: 0,
            idClientBooking: $bookingPk,
            idLocalService: $servicePk,
            quantityDetail: $quantity,
            unitPrice: $service->getPriceService(),
            discount: $discount,
            isActiveDetail: true
        );

        return $this->detailRepo->save($detail);
    }

    public function updateBookingDetails(
        int $bookingPk,
        string $startDate,
        string $endDate,
        ?string $eventType,
        ?string $eventDetail,
        int $attendees
    ): void {
        $this->assertCanModify($bookingPk);
        $booking = $this->bookingRepo->findById($bookingPk);
        if ($booking === null) {
            throw new BusinessRuleException('La reserva no existe.');
        }

        if (!$this->isRealDate($startDate) || !$this->isRealDate($endDate)) {
            throw new BusinessRuleException('Indica un rango de fechas válido.');
        }
        if ($startDate < date('Y-m-d')) {
            throw new BusinessRuleException('La fecha de inicio no puede ser anterior a hoy.');
        }
        $maxDate = date('Y-m-d', strtotime('+1 year'));
        if ($startDate > $maxDate || $endDate > $maxDate) {
            throw new BusinessRuleException('Las fechas no pueden superar un año desde hoy.');
        }
        if ($endDate < $startDate) {
            throw new BusinessRuleException('La fecha final no puede ser anterior a la fecha inicial.');
        }

        $allowedEventTypes = ['', 'boda', 'cumpleanos', 'empresarial', 'otro'];
        $eventType = $eventType ?? '';
        if (!in_array($eventType, $allowedEventTypes, true)) {
            throw new BusinessRuleException('El tipo de evento seleccionado no es válido.');
        }
        $eventDetail = trim((string) $eventDetail);
        if ($eventType === 'otro' && $eventDetail === '') {
            throw new BusinessRuleException('Indica de qué trata tu evento.');
        }
        if (mb_strlen($eventDetail) > 255) {
            throw new BusinessRuleException('La descripción del evento no puede superar los 255 caracteres.');
        }
        if ($attendees <= 0) {
            throw new BusinessRuleException('La cantidad de asistentes debe ser mayor que cero.');
        }

        $venue = $this->venueRepo->findById($booking->getIdLocal());
        if ($venue === null) {
            throw new BusinessRuleException('No se encontró el local de esta reserva.');
        }
        $minCapacity = $venue->getMinCapacity() ?? 1;
        if ($attendees < $minCapacity || $attendees > $venue->getCapacityVenue()) {
            throw new BusinessRuleException(
                'La cantidad de asistentes debe estar entre ' . $minCapacity
                . ' y ' . $venue->getCapacityVenue() . '.'
            );
        }
        if ($this->bookingRepo->hasActiveRangeConflict(
            $booking->getIdLocal(),
            $startDate,
            $endDate,
            $bookingPk,
            $booking->getRentalBlockId()
        )) {
            throw new BusinessRuleException('El local ya tiene una reserva que se cruza con esas fechas. Elige otro rango.');
        }

        $rentalLine = $this->detailRepo->findVenueLine($bookingPk);
        if ($rentalLine === null) {
            throw new BusinessRuleException('No se encontró la línea de renta de esta reserva.');
        }
        $days = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
        $eventType = $eventType !== '' ? $eventType : null;
        $eventDetail = $eventDetail !== '' ? $eventDetail : null;

        $this->connection->beginTransaction();
        try {
            if (!$this->bookingRepo->updateClientEditableDetails(
                $bookingPk,
                $startDate,
                $endDate,
                $eventType,
                $eventDetail,
                $attendees
            ) || !$this->detailRepo->updateVenueLineQuantity($bookingPk, $days)) {
                throw new BusinessRuleException('No se pudieron guardar los cambios de la reserva.');
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function updateServiceQuantity(int $bookingPk, int $detailPk, int $quantity): void
    {
        $this->assertCanModify($bookingPk);
        if ($quantity <= 0) {
            throw new BusinessRuleException('La cantidad debe ser mayor que cero.');
        }

        $lines = $this->detailRepo->findByBooking($bookingPk);
        $target = null;
        foreach ($lines as $line) {
            if ($line->getIdDetail() === $detailPk && $line->getIdLocalService() > 0) {
                $target = $line;
                break;
            }
        }
        if ($target === null) {
            throw new BusinessRuleException('No se encontró ese servicio en la reserva.');
        }

        $servicePk = $target->getIdLocalService();
        $service = $this->serviceRepo->findById($servicePk);
        if ($service === null || !$service->getIsActive()) {
            throw new BusinessRuleException('El servicio ya no está disponible para edición.');
        }

        $otherQuantity = 0;
        foreach ($lines as $line) {
            if ($line->getIdLocalService() === $servicePk && $line->getIdDetail() !== $detailPk) {
                $otherQuantity += $line->getQuantityDetail();
            }
        }
        $this->serviceService->assertQuantityAllowed($service, $quantity, $otherQuantity);

        $this->connection->beginTransaction();
        try {
            $this->recalculateServiceDiscounts($bookingPk, $servicePk, $detailPk, $quantity);
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function removeServiceLine(int $bookingPk, int $detailPk): void
    {
        $this->assertCanModify($bookingPk);
        $lines = $this->detailRepo->findByBooking($bookingPk);
        $target = null;
        foreach ($lines as $line) {
            if ($line->getIdDetail() === $detailPk && $line->getIdLocalService() > 0) {
                $target = $line;
                break;
            }
        }
        if ($target === null) {
            throw new BusinessRuleException('No se encontró ese servicio en la reserva.');
        }

        $servicePk = $target->getIdLocalService();
        $service = $this->serviceRepo->findById($servicePk);
        $remainingQuantity = 0;
        foreach ($lines as $line) {
            if ($line->getIdLocalService() === $servicePk && $line->getIdDetail() !== $detailPk) {
                $remainingQuantity += $line->getQuantityDetail();
            }
        }
        if ($remainingQuantity > 0 && $service !== null) {
            $this->serviceService->assertQuantityAllowed($service, $remainingQuantity);
        }

        $this->connection->beginTransaction();
        try {
            if (!$this->detailRepo->deactivateServiceLine($bookingPk, $detailPk)) {
                throw new BusinessRuleException('No se pudo eliminar el servicio de la reserva.');
            }
            if ($remainingQuantity > 0) {
                $this->recalculateServiceDiscounts($bookingPk, $servicePk);
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function setServiceSelection(int $bookingPk, int $servicePk, bool $selected, int $quantity): void
    {
        $this->assertCanModify($bookingPk);
        $lines = array_values(array_filter(
            $this->detailRepo->findByBooking($bookingPk),
            static fn(Detail $line): bool => $line->getIdLocalService() === $servicePk
        ));

        if (!$selected) {
            if ($lines === []) {
                return;
            }
            $this->connection->beginTransaction();
            try {
                foreach ($lines as $line) {
                    if (!$this->detailRepo->deactivateServiceLine($bookingPk, $line->getIdDetail())) {
                        throw new BusinessRuleException('No se pudo quitar el servicio de la reserva.');
                    }
                }
                $this->connection->commit();
            } catch (\Throwable $e) {
                if ($this->connection->inTransaction()) {
                    $this->connection->rollBack();
                }
                throw $e;
            }
            return;
        }

        if ($quantity <= 0) {
            throw new BusinessRuleException('La cantidad debe ser mayor que cero.');
        }
        $booking = $this->bookingRepo->findById($bookingPk);
        $service = $this->serviceRepo->findById($servicePk);
        if ($booking === null || $service === null || !$service->getIsActive()) {
            throw new BusinessRuleException('El servicio ya no está disponible.');
        }
        if ($service->getIdLocal() !== $booking->getIdLocal()) {
            throw new BusinessRuleException('Este servicio no pertenece al local de la reserva.');
        }
        $this->serviceService->assertQuantityAllowed($service, $quantity);

        if ($lines === []) {
            $this->addLine($bookingPk, $servicePk, $quantity);
            return;
        }

        $canonicalLine = array_shift($lines);
        $discount = $this->serviceService->lineDiscount($service, $quantity);
        $this->connection->beginTransaction();
        try {
            if (!$this->detailRepo->updateServiceLine(
                $bookingPk,
                $canonicalLine->getIdDetail(),
                $quantity,
                $discount
            )) {
                throw new BusinessRuleException('No se pudo actualizar la cantidad del servicio.');
            }
            foreach ($lines as $duplicateLine) {
                if (!$this->detailRepo->deactivateServiceLine($bookingPk, $duplicateLine->getIdDetail())) {
                    throw new BusinessRuleException('No se pudieron consolidar las líneas del servicio.');
                }
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    private function recalculateServiceDiscounts(
        int $bookingPk,
        int $servicePk,
        ?int $updatedDetailPk = null,
        ?int $updatedQuantity = null
    ): void {
        $service = $this->serviceRepo->findById($servicePk);
        if ($service === null) {
            throw new BusinessRuleException('No se encontró el servicio.');
        }
        $serviceLines = array_values(array_filter(
            $this->detailRepo->findByBooking($bookingPk),
            static fn(Detail $line): bool => $line->getIdLocalService() === $servicePk
        ));

        $total = 0;
        foreach ($serviceLines as $line) {
            $total += ($line->getIdDetail() === $updatedDetailPk && $updatedQuantity !== null)
                ? $updatedQuantity
                : $line->getQuantityDetail();
        }
        $this->serviceService->assertQuantityAllowed($service, $total);

        $accumulated = 0;
        foreach ($serviceLines as $line) {
            $lineQuantity = ($line->getIdDetail() === $updatedDetailPk && $updatedQuantity !== null)
                ? $updatedQuantity
                : $line->getQuantityDetail();
            $discount = $this->serviceService->lineDiscount($service, $lineQuantity, $accumulated);
            if (!$this->detailRepo->updateServiceLine($bookingPk, $line->getIdDetail(), $lineQuantity, $discount)) {
                throw new BusinessRuleException('No se pudieron actualizar las cantidades del servicio.');
            }
            $accumulated += $lineQuantity;
        }
    }

    private function isRealDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
