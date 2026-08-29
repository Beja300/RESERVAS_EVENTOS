<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Model/BookingTicket.php';
require_once __DIR__ . '/InvoiceService.php';
require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/EarningService.php';

class BookingTicketService
{
  private BookingTicketRepository $ticketRepo;
  private BookingRepository $bookingRepo;
  private InvoiceService $invoiceService;
  private BookingService $bookingService;
  private EarningService $earningService;

  private const TYPES = ['png', 'jpg', 'jpeg', 'pdf'];

  public function __construct(PDO $connection)
  {
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->invoiceService = new InvoiceService();
    $this->bookingService = new BookingService();
    $this->earningService = new EarningService($connection);
  }

  // =========================================================
  // SUBIR COMPROBANTE DE PAGO (PNG/PDF).
  // El CLIENTE elige el método de pago y sube el comprobante.
  // Al subirlo se crea la FACTURA (pendiente) con ese método.
  // =========================================================
  public function upload(int $bookingPk, string $filePath, string $type, int $paymentMethodPk): int
  {
    $type = strtolower(trim($type));

    if (!in_array($type, self::TYPES, true)) {
      throw new BusinessRuleException('El comprobante debe ser una imagen PNG/JPG o un PDF.');
    }

    if ($paymentMethodPk <= 0) {
      throw new BusinessRuleException('Debes seleccionar un método de pago antes de subir el comprobante.');
    }

    $booking = $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    if ($booking->getBookingState() !== 'pendiente') {
      throw new BusinessRuleException('Solo se puede subir el comprobante de una reserva pendiente.');
    }

    $existing = $this->ticketRepo->findByBooking($bookingPk);

    if ($existing !== null && in_array($existing->getState(), ['pendiente', 'aprobado'], true)) {
      throw new BusinessRuleException('Esta reserva ya tiene un comprobante en proceso.');
    }

    $ticket = new BookingTicket(
      idTicket: 0,
      idBooking: $bookingPk,
      file: $filePath,
      type: $type,
      paymentMethodId: $paymentMethodPk,
      state: 'pendiente'
    );

    $idTicket = $this->ticketRepo->save($ticket);

    // La factura se genera aquí (pendiente), con el método elegido por el cliente.
    if ($this->invoiceService->findByBooking($bookingPk) === null) {
      $this->invoiceService->generate($bookingPk, $paymentMethodPk, date('Y-m-d'));
    }

    $this->bookingRepo->updateStatus($bookingPk, 'pendiente');

    return $idTicket;
  }

  // =========================================================
  // APROBAR COMPROBANTE (lo hace el propietario).
  // La factura YA se generó al subir el comprobante; aquí solo se
  // marca como PAGADA, se registra la ganancia y se confirma la reserva.
  // El método de pago es el que eligió el CLIENTE (nunca el propietario).
  // =========================================================
  public function approve(int $ticketPk, ?int $reviewedByRole = null): void
  {
    $ticket = $this->ticketRepo->findById($ticketPk);

    if ($ticket === null) {
      throw new BusinessRuleException('El comprobante no existe.');
    }

    if ($ticket->getState() !== 'pendiente') {
      throw new BusinessRuleException('Este comprobante ya fue procesado.');
    }

    $bookingPk = $ticket->getIdBooking();

    $this->ticketRepo->updateState($ticketPk, 'aprobado');

    $this->invoiceService->approve($bookingPk);

    $total = $this->bookingService->calculateTotal($bookingPk);

    $this->earningService->recordEarning($bookingPk, $total, $reviewedByRole);

    $this->bookingRepo->updateStatus($bookingPk, 'confirmado');
  }

  // =========================================================
  // RECHAZAR COMPROBANTE (lo hace el propietario)
  // =========================================================
  public function reject(int $ticketPk): void
  {
    $ticket = $this->ticketRepo->findById($ticketPk);

    if ($ticket === null) {
      throw new BusinessRuleException('El comprobante no existe.');
    }

    if ($ticket->getState() !== 'pendiente') {
      throw new BusinessRuleException('Este comprobante ya fue procesado.');
    }

    $this->ticketRepo->updateState($ticketPk, 'rechazado');

    $this->invoiceService->reject($ticket->getIdBooking());

    $this->bookingRepo->updateStatus($ticket->getIdBooking(), 'rechazado');
  }

  // =========================================================
  // COMPROBANTE DE UNA RESERVA
  // =========================================================
  public function findByBooking(int $bookingPk): ?BookingTicket
  {
    return $this->ticketRepo->findByBooking($bookingPk);
  }
}
