<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceException.php';
require_once __DIR__ . '/ErrorLogger.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/VenueRentalBlockRepository.php';
require_once __DIR__ . '/../Repository/BlockedClientRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/../Model/Booking.php';
require_once __DIR__ . '/../Model/BookingHistory.php';
require_once __DIR__ . '/../Model/BookingRefund.php';
require_once __DIR__ . '/CommissionConfigService.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
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
    private CommissionConfigService $configService;
    private VenueRentalBlockRepository $blockRepo;
    private BlockedClientRepository $blockedClientRepo;
    private ServiceService $serviceService;

    public function __construct()
    {
        $this->connection = DataBase::getConnection();

        $this->bookingRepo = new BookingRepository($this->connection);
        $this->detailRepo = new DetailRepository($this->connection);
        $this->venueRepo = new VenueRepository($this->connection);
        $this->invoiceRepo = new InvoiceRepository($this->connection);
        $this->historyRepo = new BookingHistoryRepository($this->connection);
        $this->refundRepo = new BookingRefundRepository($this->connection);
        $this->configService = new CommissionConfigService(
            new CommissionConfigRepository($this->connection)
        );
        $this->blockRepo = new VenueRentalBlockRepository($this->connection);
        $this->blockedClientRepo = new BlockedClientRepository($this->connection);
        $this->serviceService = new ServiceService(new ServiceRepository($this->connection));
    }

    public function createBooking(
        int $clientPk,
        int $venuePk,
        string $date,
        ?string $endDate = null,
        ?string $eventType = null,
        ?string $eventDetail = null,
        ?int $attendees = null,
        ?int $rentalBlockId = null,
        array $serviceQuantities = []
    ): int {
        // Primero se valida el formato de las fechas; recién después las
        // comparaciones, para no confundir una fecha inválida con una fuera
        // de rango.
        if (!$this->isRealDate($date)) {
            throw new BusinessRuleException(
                'La fecha de inicio no tiene un formato válido (AAAA-MM-DD).'
            );
        }

        if ($endDate === null || $endDate === '') {
            throw new BusinessRuleException(
                'Debes indicar la fecha final de la reserva.'
            );
        }

        if (!$this->isRealDate($endDate)) {
            throw new BusinessRuleException(
                'La fecha final no tiene un formato válido (AAAA-MM-DD).'
            );
        }

        if ($date < date('Y-m-d')) {
            throw new BusinessRuleException(
                'La fecha de inicio de la reserva no puede ser anterior a hoy.'
            );
        }

        if ($endDate < $date) {
            throw new BusinessRuleException(
                'La fecha de finalización debe ser igual o posterior a la fecha de inicio.'
            );
        }

        $maxDate = date('Y-m-d', strtotime('+1 year'));

        if ($date > $maxDate || $endDate > $maxDate) {
            throw new BusinessRuleException(
                'No se pueden realizar reservas con más de 1 año de anticipación.'
            );
        }

        $days = (int) ((strtotime($endDate) - strtotime($date)) / 86400) + 1;

        if ($days < 1) {
            throw new BusinessRuleException(
                'La reserva debe cubrir al menos un día.'
            );
        }

        $venue = $this->venueRepo->findById($venuePk);

        if ($venue === null || !$venue->getIsActive()) {
            throw new BusinessRuleException(
                'Este local no está disponible para reservas.'
            );
        }

        if ($venue->getPriceVenue() <= 0 && ($rentalBlockId === null || $rentalBlockId <= 0)) {
            throw new BusinessRuleException(
                'Este local no tiene un precio de renta configurado. Contacta al propietario.'
            );
        }

        if ($this->blockedClientRepo->isBlocked($venue->getIdOwner(), $clientPk)) {
            throw new BusinessRuleException(
                'El propietario de este local no acepta nuevas reservas de tu cuenta.'
            );
        }

        if ($attendees !== null) {
            $minCapacity = $venue->getMinCapacity() ?? 1;
            if ($attendees < $minCapacity) {
                throw new BusinessRuleException(
                    'La cantidad de asistentes debe ser al menos ' . $minCapacity . '.'
                );
            }
            if ($attendees > $venue->getCapacityVenue()) {
                throw new BusinessRuleException(
                    'La cantidad de asistentes no puede superar la capacidad máxima de ' . $venue->getCapacityVenue() . '.'
                );
            }
        }

        $rentalBlock = null;
        if ($rentalBlockId !== null && $rentalBlockId > 0) {
            $rentalBlock = $this->blockRepo->findById($rentalBlockId);
            if ($rentalBlock === null || !$rentalBlock->getIsActive() || $rentalBlock->getIdVenue() !== $venuePk) {
                throw new BusinessRuleException('El bloque de alquiler seleccionado no está disponible.');
            }
        }

        if (
            $this->bookingRepo->hasActiveRangeConflict(
                $venuePk,
                $date,
                $endDate,
                0,
                $rentalBlock !== null ? $rentalBlock->getIdBlock() : null
            )
        ) {
            throw new BusinessRuleException(
                'Este local ya tiene una reserva que se cruza con ese rango de fechas. Elige otro rango.'
            );
        }

        $booking = new Booking(
            0,
            $clientPk,
            $venuePk,
            $date,
            'pendiente',
            true,
            $endDate,
            $eventType,
            $eventDetail,
            $attendees,
            $rentalBlock !== null ? $rentalBlock->getIdBlock() : null
        );

        DataBase::beginTransaction($this->connection);

        try {

            $idBooking = $this->bookingRepo->save($booking);

            // Línea base: renta del local por el número de días. Si se eligió
            // un bloque horario, se usa su precio y se identifica el bloque.
            $unitPrice = $rentalBlock !== null ? $rentalBlock->getPrice() : $venue->getPriceVenue();
            $concept = $rentalBlock !== null
                ? 'Renta del local — ' . $rentalBlock->getName()
                : null;
            $this->detailRepo->addVenueLine($idBooking, $venue, $days, $unitPrice, $concept);

            if ($venue->getCleaningFee() > 0) {
                $this->detailRepo->addConceptLine(
                    $idBooking,
                    $venue->getIdVenue(),
                    'Limpieza del local',
                    1,
                    $venue->getCleaningFee()
                );
            }

            // Servicios elegidos por el cliente al crear la reserva.
            // Se insertan en la misma transacción para que un error en
            // cualquiera revierta también la reserva.
            foreach ($serviceQuantities as $serviceId => $requestedQuantity) {
                $requestedQuantity = (int) $requestedQuantity;

                if ($requestedQuantity <= 0) {
                    continue;
                }

                $service = $this->serviceService->findById((int) $serviceId);

                if ($service === null || !$service->getIsActive()) {
                    throw new BusinessRuleException('Este servicio ya no está disponible.');
                }

                if ($service->getIdLocal() !== $venuePk) {
                    throw new BusinessRuleException('Este servicio no pertenece al local de la reserva.');
                }

                $this->serviceService->assertQuantityAllowed($service, $requestedQuantity, 0);
                $discount = $this->serviceService->lineDiscount($service, $requestedQuantity, 0);

                $this->detailRepo->addServiceLine($idBooking, $service, $requestedQuantity, $discount);
            }

            DataBase::commit();

            return $idBooking;
        } catch (\Throwable $e) {

            DataBase::rollBack();

            if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
                throw $e;
            }

            ErrorLogger::log($e, __METHOD__);
            throw new ServiceException(
                'No se pudo completar la operación. Intenta de nuevo.',
                0,
                $e
            );
        }
    }

    /**
     * Desglose de una reserva (fórmula única de la plataforma):
     *   - subtotal   = suma de las líneas (cantidad x precio - descuento)
     *   - commission = comisión de la plataforma sobre el subtotal
     *   - tax        = IVA que paga el cliente sobre el subtotal
     *   - total      = lo que paga el cliente (subtotal + IVA)
     *   - ownerAmount = total - comisión - IVA = subtotal - comisión
     * Las tasas se leen de la configuración vigente (tbcommissionconfig).
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

        $config = $this->configService->getActive();

        $commission = round($subtotal * ($config->getPercentage() / 100), 2);
        $tax        = round($subtotal * ($config->getTax() / 100), 2);
        $total      = round($subtotal + $tax, 2);

        return [
            'subtotal'     => $subtotal,
            'commission'   => $commission,
            'tax'          => $tax,
            'total'        => $total,
            'commissionPct' => $config->getPercentage(),
            'taxPct'       => $config->getTax(),
        ];
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

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingRepo->updateStatus(
        $bookingPk,
        'cancelado'
      );

      DataBase::commit();
    } catch (\Throwable $e) {
      DataBase::rollBack();

      if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
        throw $e;
      }

      ErrorLogger::log($e, __METHOD__);
      throw new ServiceException(
        'No se pudo completar la operación. Intenta de nuevo.',
        0,
        $e
      );
    }
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

    DataBase::beginTransaction($this->connection);

    try {
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

      DataBase::commit();
    } catch (\Throwable $e) {
      DataBase::rollBack();

      if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
        throw $e;
      }

      ErrorLogger::log($e, __METHOD__);
      throw new ServiceException(
        'No se pudo completar la operación. Intenta de nuevo.',
        0,
        $e
      );
    }
  }

  /**
   * Comprueba que una cadena sea una fecha real en formato AAAA-MM-DD
   * (rechaza "2026-02-31", "2050-13-01", etc.).
   */
  private function isRealDate(string $value): bool
  {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
      return false;
    }

    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
  }
}
