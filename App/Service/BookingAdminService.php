<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/EarningRepository.php';
require_once __DIR__ . '/../Model/BookingHistory.php';
require_once __DIR__ . '/../Model/BookingRefund.php';
require_once __DIR__ . '/../Model/Booking.php';

/**
 * BookingAdminService — operaciones del Administrador sobre reservas
 * (cancelar, reprogramar, cambiar local y tramitar reembolsos).
 *
 * Toda acción deja una traza en tbbookinghistory con el rol del
 * administrador que la ejecutó.
 */
class BookingAdminService
{
  private BookingRepository $bookingRepo;
  private BookingHistoryRepository $historyRepo;
  private BookingRefundRepository $refundRepo;
  private DetailRepository $detailRepo;
  private VenueRepository $venueRepo;
  private InvoiceRepository $invoiceRepo;
  private EarningRepository $earningRepo;

  public function __construct(PDO $connection)
  {
    $this->bookingRepo = new BookingRepository($connection);
    $this->historyRepo = new BookingHistoryRepository($connection);
    $this->refundRepo = new BookingRefundRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->invoiceRepo = new InvoiceRepository($connection);
    $this->earningRepo = new EarningRepository($connection);
  }

  // =========================================================
  // CANCELAR UNA RESERVA
  // =========================================================
  public function cancel(int $bookingPk, int $adminRoleId, ?string $note = null): void
  {
    $booking = $this->requireBooking($bookingPk);

    if (!in_array($booking->getBookingState(), ['pendiente', 'confirmado'], true)) {
      throw new BusinessRuleException(
        'Solo se pueden cancelar reservas pendientes o confirmadas.'
      );
    }

    $this->bookingRepo->updateStatus($bookingPk, 'cancelado');
    $this->log($bookingPk, $adminRoleId, 'CANCELAR', $this->text($note, 'Cancelada por el administrador.'));
  }

  // =========================================================
  // REPROGRAMAR (cambiar la fecha)
  // =========================================================
  public function reschedule(int $bookingPk, int $adminRoleId, string $newDate, ?string $note = null): void
  {
    $booking = $this->requireBooking($bookingPk);

    if ($booking->getBookingState() === 'cancelado' || $booking->getBookingState() === 'rechazado') {
      throw new BusinessRuleException('No se puede reprogramar una reserva cancelada o rechazada.');
    }

    if ($newDate < date('Y-m-d')) {
      throw new BusinessRuleException('La fecha de la reserva no puede ser anterior a hoy.');
    }

    if ($newDate === $booking->getBookingDate()) {
      throw new BusinessRuleException('La nueva fecha es la misma que la actual.');
    }

    if ($this->bookingRepo->hasActiveBookingOnDate($booking->getIdLocal(), $newDate, $bookingPk)) {
      throw new BusinessRuleException('El local ya tiene una reserva para esa fecha. Elige otra fecha.');
    }

    $oldDate = $booking->getBookingDate();
    $this->bookingRepo->reschedule($bookingPk, $newDate);

    $this->log(
      $bookingPk,
      $adminRoleId,
      'REPROGRAMAR',
      'Fecha anterior: ' . $oldDate . ' -> nueva fecha: ' . $newDate
      . ($this->text($note) ? ' | ' . $note : '')
    );
  }

  // =========================================================
  // CAMBIAR LOCAL (actualiza la reserva y la línea de renta)
  // =========================================================
  public function changeVenue(int $bookingPk, int $adminRoleId, int $newVenueId, ?string $note = null): void
  {
    $booking = $this->requireBooking($bookingPk);

    if ($booking->getBookingState() === 'cancelado' || $booking->getBookingState() === 'rechazado') {
      throw new BusinessRuleException('No se puede cambiar el local de una reserva cancelada o rechazada.');
    }

    if ($newVenueId === $booking->getIdLocal()) {
      throw new BusinessRuleException('La reserva ya está asignada a ese local.');
    }

    $venue = $this->venueRepo->findById($newVenueId);

    if ($venue === null || !$venue->getIsActive()) {
      throw new BusinessRuleException('El local seleccionado no está disponible.');
    }

    if ($venue->getPriceVenue() <= 0) {
      throw new BusinessRuleException('El local seleccionado no tiene un precio de renta configurado.');
    }

    if ($this->bookingRepo->hasActiveBookingOnDate($newVenueId, $booking->getBookingDate(), $bookingPk)) {
      throw new BusinessRuleException('El local seleccionado ya tiene una reserva para esa fecha.');
    }

    $this->bookingRepo->changeVenue($bookingPk, $newVenueId);
    $this->updateVenueLine($bookingPk, $newVenueId, $venue->getPriceVenue());

    $this->log(
      $bookingPk,
      $adminRoleId,
      'CAMBIAR_LOCAL',
      'Local anterior: #' . $booking->getIdLocal() . ' -> nuevo local: #' . $newVenueId
      . ($this->text($note) ? ' | ' . $note : '')
    );
  }

  private function updateVenueLine(int $bookingPk, int $newVenueId, float $unitPrice): void
  {
    $venueLine = $this->detailRepo->findVenueLine($bookingPk);

    if ($venueLine !== null) {
      $this->detailRepo->updateVenueLine($venueLine->getIdDetail(), $newVenueId, $unitPrice);
    }
  }

  // =========================================================
  // APROBAR REEMBOLSO (el admin valida la solicitud del cliente)
  // =========================================================
  public function approveRefund(int $bookingPk, int $adminRoleId, int $refundRequestId, ?string $note = null): void
  {
    $booking = $this->requireBooking($bookingPk);
    $refund = $this->requirePendingRefund($refundRequestId);

    if ($refund->getIdBooking() !== $bookingPk) {
      throw new BusinessRuleException('La solicitud de reembolso no corresponde a esta reserva.');
    }

    if ($booking->getBookingState() === 'cancelado' || $booking->getBookingState() === 'rechazado') {
      throw new BusinessRuleException('La reserva ya no puede reembolsarse.');
    }

    $this->refundRepo->updateState($refundRequestId, 'aprobado');

    $invoice = $this->invoiceRepo->findByBooking($bookingPk);
    if ($invoice !== null && in_array($invoice->getStatusInvoice(), ['pendiente', 'pagado'], true)) {
      $this->invoiceRepo->updateStatus($invoice->getIdInvoice(), 'reembolsado');
    }

    // Reversar la ganancia registrada, si existe.
    if ($this->earningRepo->findByBooking($bookingPk) !== null) {
      $this->earningRepo->deactivateByBooking($bookingPk);
    }

    $this->bookingRepo->updateStatus($bookingPk, 'cancelado');

    $this->log(
      $bookingPk,
      $adminRoleId,
      'REEMBOLSO_APROBADO',
      'Motivo del cliente: ' . $refund->getDetail()
      . ($this->text($note) ? ' | ' . $note : '')
    );
  }

  // =========================================================
  // RECHAZAR SOLICITUD DE REEMBOLSO
  // =========================================================
  public function rejectRefund(int $refundRequestId, int $adminRoleId): void
  {
    $refund = $this->requirePendingRefund($refundRequestId);

    $this->refundRepo->updateState($refundRequestId, 'rechazado');

    $this->log(
      $refund->getIdBooking(),
      $adminRoleId,
      'REEMBOLSO_RECHAZADO',
      'Se rechazó la solicitud de reembolso.'
    );
  }

  // =========================================================
  // HELPERS
  // =========================================================
  private function requireBooking(int $bookingPk): Booking
  {
    $booking = $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    return $booking;
  }

  private function requirePendingRefund(int $refundRequestId): BookingRefund
  {
    $refund = $this->refundRepo->findById($refundRequestId);

    if ($refund === null) {
      throw new BusinessRuleException('La solicitud de reembolso no existe.');
    }

    if ($refund->getState() !== 'pendiente') {
      throw new BusinessRuleException('Esta solicitud de reembolso ya fue procesada.');
    }

    return $refund;
  }

  private function log(int $bookingPk, ?int $roleId, string $action, ?string $detail): void
  {
    $this->historyRepo->save(
      new BookingHistory(
        id: 0,
        idBooking: $bookingPk,
        roleId: $roleId,
        action: $action,
        detail: $detail
      )
    );
  }

  private function text(?string $value): string
  {
    return trim((string) $value);
  }
}