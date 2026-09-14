<?php

require_once __DIR__ . '/../Service/InvoiceService.php';
require_once __DIR__ . '/../Service/EarningService.php';
require_once __DIR__ . '/../Service/BookingService.php';
require_once __DIR__ . '/../Service/BookingAdminService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/HistoryService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/BookingHistoryRepository.php';
require_once __DIR__ . '/../Repository/HistoryRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminBookingController
{
  private InvoiceService $invoiceService;
  private EarningService $earningService;
  private BookingService $bookingService;
  private BookingAdminService $bookingAdminService;
  private BookingRepository $bookingRepo;
  private BookingHistoryRepository $bookingHistoryRepo;
  private BookingRefundRepository $bookingRefundRepo;
  private BookingTicketRepository $bookingTicketRepo;
  private DetailRepository $detailRepo;
  private VenueRepository $venueRepo;
  private ClientRepository $clientRepo;
  private NotificationService $notificationService;
  private HistoryService $historyService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->invoiceService = new InvoiceService();
    $this->earningService = new EarningService($connection);
    $this->bookingService = new BookingService();
    $this->bookingAdminService = new BookingAdminService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->bookingHistoryRepo = new BookingHistoryRepository($connection);
    $this->bookingRefundRepo = new BookingRefundRepository($connection);
    $this->bookingTicketRepo = new BookingTicketRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
    $this->historyService = new HistoryService($connection);
  }

  // =========================================================
  // RESERVAS DEL MES (para verificación de pagos)
  // =========================================================
  public function bookings(): void
  {
    require_role('admin');

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $bookings = $this->bookingRepo->findByMonthWithDetails($yearMonth);
    $history = $this->bookingHistoryRepo->findAllWithDetails();
    $refundsPending = $this->bookingRefundRepo->findPending();
    $prevMonth = date('Y-m', strtotime($yearMonth . '-01 first day of last month'));
    $nextMonth = date('Y-m', strtotime($yearMonth . '-01 first day of next month'));

    require_once __DIR__ . '/../View/Admin/List.php';
  }

  // =========================================================
  // HISTORIAL GLOBAL DE ACCIONES DE USUARIOS
  // =========================================================
  public function userHistory(): void
  {
    require_role('admin');

    $historyRepo = new HistoryRepository();
    $history = $historyRepo->listAll();

    require_once __DIR__ . '/../View/Admin/UserHistory.php';
  }

  // =========================================================
  // DETALLE DE UNA RESERVA (panel del Admin)
  // =========================================================
  public function bookingDetail(): void
  {
    require_role('admin');

    $idBooking = (int) ($_GET['id'] ?? 0);
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      redirect_to('admin', 'bookings');
    }

    $client = $this->clientRepo->findByClientPk($booking->getIdClient());
    $venue = $this->venueRepo->findById($booking->getIdLocal());

    $lines = $this->detailRepo->findByBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);

    $invoice = $this->invoiceService->findByBooking($idBooking);
    $ticket = $this->bookingTicketRepo->findByBooking($idBooking);
    $earning = $this->earningService->findByBooking($idBooking);
    $refundRequest = $this->bookingRefundRepo->findByBooking($idBooking);
    $history = $this->bookingHistoryRepo->findByBooking($idBooking);
    $venues = $this->venueRepo->findActive();
    $bookedDates = $this->bookingRepo->bookedDatesByVenue($booking->getIdLocal());

    require_once __DIR__ . '/../View/Admin/BookingDetail.php';
  }

  // =========================================================
  // APROBAR PAGO DE UNA RESERVA
  // =========================================================
  public function approvePayment(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {
      $this->invoiceService->approve($idBooking);

      $totals = $this->bookingService->calculateTotals($idBooking);
      $this->earningService->recordEarning($idBooking, $totals, $this->currentAdminRoleId());

      $approvedBooking = $this->bookingRepo->findById($idBooking);
      if ($approvedBooking !== null) {
        $this->notificationService->notifyClientPaymentApproved((int) $approvedBooking->getIdClient(), (int) $idBooking);

        $approvedVenue = $this->venueRepo->findById($approvedBooking->getIdLocal());
        if ($approvedVenue !== null) {
          $this->notificationService->notifyOwnerPaymentReceived((int) $approvedVenue->getIdOwner(), (int) $idBooking);
        }
      }

      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'payment_approved']);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $error]);
    }
  }

  // =========================================================
  // RECHAZAR PAGO DE UNA RESERVA
  // =========================================================
  public function rejectPayment(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {
      $this->invoiceService->reject($idBooking);

      $rejectedBooking = $this->bookingRepo->findById($idBooking);
      if ($rejectedBooking !== null) {
        $this->notificationService->notifyClientPaymentRejected((int) $rejectedBooking->getIdClient(), (int) $idBooking);
      }

      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'payment_rejected']);
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $error]);
    }
  }

  // =========================================================
  // CANCELAR RESERVA (admin)
  // =========================================================
  public function cancelBooking(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentAdminRoleId();

    try {
      $this->bookingAdminService->cancel($idBooking, $adminRoleId, $note);
      $cancelledBooking = $this->bookingRepo->findById($idBooking);
      if ($cancelledBooking !== null) {
        $this->historyService->logVenueCancel($adminRoleId, (int) $cancelledBooking->getIdLocal());
        $this->notificationService->notifyClientBookingCancelled((int) $cancelledBooking->getIdClient(), (int) $idBooking);
      }
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'cancelled']);
    } catch (BusinessRuleException $e) {
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // REPROGRAMAR (cambiar fecha) — admin
  // =========================================================
  public function rescheduleBooking(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? 0);
    $newDate = trim($_POST['date'] ?? '');
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentAdminRoleId();

    try {
      $this->bookingAdminService->reschedule($idBooking, $adminRoleId, $newDate, $note);
      $rescheduledBooking = $this->bookingRepo->findById($idBooking);
      if ($rescheduledBooking !== null) {
        $this->notificationService->notifyClientBookingRescheduled((int) $rescheduledBooking->getIdClient(), (int) $idBooking);
      }
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'rescheduled']);
    } catch (BusinessRuleException $e) {
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // CAMBIAR LOCAL — admin
  // =========================================================
  public function changeBookingVenue(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? 0);
    $newVenueId = (int) ($_POST['venueId'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentAdminRoleId();

    try {
      $this->bookingAdminService->changeVenue($idBooking, $adminRoleId, $newVenueId, $note);
      $venueChangedBooking = $this->bookingRepo->findById($idBooking);
      if ($venueChangedBooking !== null) {
        $this->notificationService->notifyClientVenueChanged((int) $venueChangedBooking->getIdClient(), (int) $idBooking);
      }
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'venue_changed']);
    } catch (BusinessRuleException $e) {
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // APROBAR REEMBOLSO (admin valida la solicitud del cliente)
  // =========================================================
  public function refundBooking(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? 0);
    $refundRequestId = (int) ($_POST['refundId'] ?? 0);
    $note = trim($_POST['note'] ?? '') ?: null;
    $adminRoleId = $this->currentAdminRoleId();

    try {
      $this->bookingAdminService->approveRefund($idBooking, $adminRoleId, $refundRequestId, $note);
      $refundedBooking = $this->bookingRepo->findById($idBooking);
      if ($refundedBooking !== null) {
        $this->historyService->logVenueCancel($adminRoleId, (int) $refundedBooking->getIdLocal());
        $this->notificationService->notifyClientRefundApproved((int) $refundedBooking->getIdClient(), (int) $idBooking);
      }
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'refunded']);
    } catch (BusinessRuleException $e) {
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // RECHAZAR SOLICITUD DE REEMBOLSO
  // =========================================================
  public function rejectRefundBooking(): void
  {
    require_role('admin');

    $idBooking = (int) ($_POST['id'] ?? 0);
    $refundRequestId = (int) ($_POST['refundId'] ?? 0);
    $adminRoleId = $this->currentAdminRoleId();

    try {
      $this->bookingAdminService->rejectRefund($refundRequestId, $adminRoleId);
      $refundRejectedBooking = $this->bookingRepo->findById($idBooking);
      if ($refundRejectedBooking !== null) {
        $this->notificationService->notifyClientRefundRejected((int) $refundRejectedBooking->getIdClient(), (int) $idBooking);
      }
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'msg' => 'refund_rejected']);
    } catch (BusinessRuleException $e) {
      redirect_to('admin', 'bookingDetail', ['id' => $idBooking, 'error' => $e->getMessage()]);
    }
  }

  // =========================================================
  // ROL ID DEL ADMIN EN SESIÓN (para la auditoría)
  // =========================================================
  private function currentAdminRoleId(): int
  {
    $user = $_SESSION['user'] ?? null;
    return $user instanceof Admin && method_exists($user, 'getIdRol') ? (int) $user->getIdRol() : 0;
  }
}