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
  // SUBIR COMPROBANTE DE PAGO (PNG/PDF)
  // =========================================================
  public function upload(int $bookingPk, string $filePath, string $type): int
  {
    $type = strtolower(trim($type));

    if (!in_array($type, self::TYPES, true)) {
      throw new BusinessRuleException('El comprobante debe ser una imagen PNG/JPG o un PDF.');
    }

    $booking = $this->bookingRepo->findById($bookingPk);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    $ticket = new BookingTicket(
      idTicket: 0,
      idBooking: $bookingPk,
      file: $filePath,
      type: $type,
      state: 'pendiente'
    );

    $idTicket = $this->ticketRepo->save($ticket);

    $this->bookingRepo->updateStatus($bookingPk, 'pendiente');

    return $idTicket;
  }

  // =========================================================
  // APROBAR COMPROBANTE (lo hace el propietario)
  // La factura se genera DESPUÉS de la aprobación.
  // =========================================================
  public function approve(int $ticketPk, int $paymentMethodPk, ?int $reviewedByRole = null): void
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

    $this->invoiceService->generate($bookingPk, $paymentMethodPk, date('Y-m-d'));

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
