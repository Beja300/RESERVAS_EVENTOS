<?php

require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/DetailService.php';
require_once __DIR__ . '/InvoiceService.php';
require_once __DIR__ . '/ClientService.php';
require_once __DIR__ . '/OwnerService.php';
require_once __DIR__ . '/BookingTicketService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/HistoryService.php';
require_once __DIR__ . '/ImageStorageService.php';
require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/ServiceException.php';
require_once __DIR__ . '/ErrorLogger.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';

/**
 * Acciones de reserva ejecutadas desde los controllers (cliente y owner).
 * Centraliza la lógica transaccional que vivía en BookingController:
 * crear, agregar línea, cancelar, solicitar reembolso, pagar, subir
 * comprobante y aprobar/rechazar comprobante.
 */
class BookingActionService
{
  private PDO $connection;
  private BookingService $bookingService;
  private DetailService $detailService;
  private InvoiceService $invoiceService;
  private ClientService $clientService;
  private OwnerService $ownerService;
  private BookingTicketService $bookingTicketService;
  private BookingRepository $bookingRepo;
  private VenueRepository $venueRepo;
  private BookingTicketRepository $ticketRepo;
  private NotificationService $notificationService;
  private HistoryService $historyService;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
    $this->bookingService = new BookingService();
    $this->detailService = new DetailService($connection);
    $this->invoiceService = new InvoiceService();
    $this->clientService = new ClientService();
    $this->ownerService = new OwnerService($connection);
    $this->bookingTicketService = new BookingTicketService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
    $this->historyService = new HistoryService($connection);
  }

  // =========================================================
  // CREAR UNA RESERVA (cliente)
  // =========================================================
  public function create(
    int $clientId,
    int $roleId,
    int $idVenue,
    string $date,
    ?string $endDate,
    ?string $eventType,
    ?string $eventDetail
  ): int
  {
    if ($eventType === 'otro' && ($eventDetail === null || $eventDetail === '')) {
      throw new BusinessRuleException(
        'Indica de qué trata tu evento para que el propietario del local lo conozca.'
      );
    }

    if ($eventDetail !== null && mb_strlen($eventDetail) > 255) {
      throw new BusinessRuleException(
        'La descripción del evento no puede superar los 255 caracteres.'
      );
    }

    $this->clientService->assertCanBook($clientId);

    DataBase::beginTransaction($this->connection);

    try {
      $idBooking = $this->bookingService->createBooking(
        $clientId,
        $idVenue,
        $date,
        $endDate,
        $eventType,
        $eventDetail
      );

      $this->historyService->logVenueBooking($roleId, $idVenue);

      $venue = $this->venueRepo->findById($idVenue);
      if ($venue !== null) {
        $this->notificationService->notifyOwnerOfNewBooking(
          (int) $venue->getIdOwner(),
          $venue->getNameVenue(),
          (int) $idBooking
        );
      }

      $this->notificationService->notifyNewBookingToAdmins((int) $idBooking);

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

  // =========================================================
  // AGREGAR SERVICIO AL DETALLE (cliente)
  // =========================================================
  public function addLine(int $clientId, int $idBooking, int $idService, int $quantity): void
  {
    $this->clientService->assertOwnsBooking($clientId, $idBooking);
    $this->detailService->addLine($idBooking, $idService, $quantity);
  }

  // =========================================================
  // CANCELAR RESERVA (cliente)
  // =========================================================
  public function cancel(int $clientId, int $roleId, int $idBooking): void
  {
    $this->clientService->assertOwnsBooking($clientId, $idBooking);

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingService->cancel($idBooking);

      $cancelledBooking = $this->bookingRepo->findById($idBooking);
      if ($cancelledBooking !== null) {
        $cancelledVenue = $this->venueRepo->findById($cancelledBooking->getIdLocal());
        if ($cancelledVenue !== null) {
          $this->historyService->logVenueCancel($roleId, (int) $cancelledBooking->getIdLocal());
          $this->notificationService->notifyOwnerBookingCancelled(
            (int) $cancelledVenue->getIdOwner(),
            (int) $idBooking
          );
        }
      }

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
  // SOLICITAR REEMBOLSO (cliente -> admin la valida)
  // =========================================================
  public function requestRefund(int $clientId, int $roleId, int $idBooking, string $motivo): void
  {
    $this->clientService->assertOwnsBooking($clientId, $idBooking);

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingService->requestRefund($idBooking, $roleId, $motivo);
      $this->notificationService->notifyAdminsRefundRequested($idBooking);

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
  // PAGAR CON FACTURA (cliente)
  // =========================================================
  public function pay(int $clientId, int $roleId, int $idBooking, int $idPaymentMethod): void
  {
    $this->clientService->assertOwnsBooking($clientId, $idBooking);

    DataBase::beginTransaction($this->connection);

    try {
      $this->invoiceService->generate($idBooking, $idPaymentMethod, date('Y-m-d'));

      $paidBooking = $this->bookingRepo->findById($idBooking);
      if ($paidBooking !== null) {
        $this->historyService->logVenuePurchase($roleId, (int) $paidBooking->getIdLocal());
      }

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
  // SUBIR COMPROBANTE DE PAGO (cliente)
  // Valida la extensión y el tamaño ANTES de mover el archivo.
  // =========================================================
  public function uploadTicket(int $clientId, int $roleId, int $idBooking, int $idPaymentMethod): void
  {
    $this->clientService->assertOwnsBooking($clientId, $idBooking);

    $file = $_FILES['ticket'] ?? null;

    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      throw new BusinessRuleException('Selecciona un archivo de comprobante.');
    }

    $storedPath = ImageStorageService::store(
      $file,
      'tickets',
      'ticket_' . $idBooking,
      ImageStorageService::TICKET_EXTENSIONS,
      2 * 1024 * 1024,
      'Formato de comprobante no válido (usa png, jpg, jpeg o pdf).'
    );

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingTicketService->upload($idBooking, $storedPath, $extension, $idPaymentMethod);

      $ticketBooking = $this->bookingRepo->findById($idBooking);
      if ($ticketBooking !== null) {
        $this->historyService->logVenuePurchase($roleId, (int) $ticketBooking->getIdLocal());

        $ticketVenue = $this->venueRepo->findById($ticketBooking->getIdLocal());
        if ($ticketVenue !== null) {
          $this->notificationService->notifyOwnerPaymentVerification(
            (int) $ticketVenue->getIdOwner(),
            (int) $idBooking
          );
        }
      }

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
  // APROBAR COMPROBANTE (owner) -> genera factura y ganancia
  // =========================================================
  public function approveTicket(int $ownerId, int $ownerRoleId, int $idBooking): void
  {
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    $this->ownerService->assertOwnsVenue($ownerId, $booking->getIdLocal());

    $ticket = $this->ticketRepo->findByBooking($idBooking);

    if ($ticket === null) {
      throw new BusinessRuleException('Esta reserva no tiene comprobante.');
    }

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingTicketService->approve($ticket->getIdTicket(), $ownerRoleId);

      $this->notificationService->notifyClientPaymentApproved((int) $booking->getIdClient(), (int) $idBooking);

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
  // RECHAZAR COMPROBANTE (owner)
  // =========================================================
  public function rejectTicket(int $ownerId, int $idBooking): void
  {
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      throw new BusinessRuleException('La reserva no existe.');
    }

    $this->ownerService->assertOwnsVenue($ownerId, $booking->getIdLocal());

    $ticket = $this->ticketRepo->findByBooking($idBooking);

    if ($ticket === null) {
      throw new BusinessRuleException('Esta reserva no tiene comprobante.');
    }

    DataBase::beginTransaction($this->connection);

    try {
      $this->bookingTicketService->reject($ticket->getIdTicket());

      $this->notificationService->notifyClientPaymentRejected((int) $booking->getIdClient(), (int) $idBooking);

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
}