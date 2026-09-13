<?php

require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/ServiceService.php';
require_once __DIR__ . '/OwnerPaymentService.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Repository/DetailRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Repository/BookingTicketRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/BookingRefundRepository.php';
require_once __DIR__ . '/../Repository/PaymentMethodRepository.php';
require_once __DIR__ . '/../Repository/OwnerRepository.php';

/**
 * Ensambla el detalle de una reserva (cliente/owner): cabecera, líneas,
 * totales, local, cliente, propietario, comprobante, métodos de pago del
 * dueño, servicios disponibles y estado modificable de la reserva.
 */
class BookingDetailService
{
  private BookingService $bookingService;
  private ServiceService $serviceService;
  private OwnerPaymentService $ownerPaymentService;
  private BookingRepository $bookingRepo;
  private DetailRepository $detailRepo;
  private ServiceRepository $serviceRepo;
  private VenueRepository $venueRepo;
  private BookingTicketRepository $ticketRepo;
  private ClientRepository $clientRepo;
  private BookingRefundRepository $refundRepo;
  private PaymentMethodRepository $paymentMethodRepo;
  private OwnerRepository $ownerRepo;

  public function __construct(PDO $connection)
  {
    $this->bookingService = new BookingService();
    $this->serviceService = new ServiceService(new ServiceRepository($connection));
    $this->ownerPaymentService = new OwnerPaymentService($connection);
    $this->bookingRepo = new BookingRepository($connection);
    $this->detailRepo = new DetailRepository($connection);
    $this->serviceRepo = new ServiceRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
    $this->ticketRepo = new BookingTicketRepository($connection);
    $this->clientRepo = new ClientRepository($connection);
    $this->refundRepo = new BookingRefundRepository($connection);
    $this->paymentMethodRepo = new PaymentMethodRepository($connection);
    $this->ownerRepo = new OwnerRepository($connection);
  }

  /**
   * Devuelve null si la reserva no existe; si existe, un array plano con
   * las variables que consume la vista Detail.php.
   */
  public function assemble(int $idBooking): ?array
  {
    $booking = $this->bookingRepo->findById($idBooking);

    if ($booking === null) {
      return null;
    }

    $details = $this->detailRepo->findByBooking($idBooking);
    $totals = $this->bookingService->calculateTotals($idBooking);
    $venue = $this->venueRepo->findById($booking->getIdLocal());
    $client = $this->clientRepo->findByClientPk($booking->getIdClient());
    $owner = $venue !== null ? $this->ownerRepo->findByOwnerPk($venue->getIdOwner()) : null;
    $ticket = $this->ticketRepo->findByBooking($idBooking);
    $paymentMethods = $this->paymentMethodRepo->findActive();

    $serviceMap = [];
    foreach ($details as $d) {
      if ($d->getIdLocalService() > 0
          && !isset($serviceMap[$d->getIdLocalService()])) {
        $service = $this->serviceRepo->findById($d->getIdLocalService());
        if ($service !== null) {
          $serviceMap[$d->getIdLocalService()] = $service;
        }
      }
    }

    // Métodos de pago configurados por el dueño de ESTE local.
    $ownerPaymentMethods = [];
    if ($venue !== null) {
      foreach ($this->ownerPaymentService->findByOwner($venue->getIdOwner()) as $op) {
        if (!$op->getIsActive()) {
          continue;
        }
        $ownerPaymentMethods[] = [
          'idPaymentMethod' => $op->getIdPaymentMethod(),
          'paymentMethod'   => $op->getPaymentMethod(),
          'holder'          => $op->getHolder(),
          'account'         => $op->getAccount(),
          'instructions'    => $op->getInstructions(),
        ];
      }
    }

    // Servicios disponibles del local, excluyendo los ya agregados.
    $bookedServiceIds = [];
    foreach ($details as $d) {
      if ($d->getIdLocalService() > 0) {
        $bookedServiceIds[] = $d->getIdLocalService();
      }
    }

    $availableServices = [];
    foreach ($this->serviceService->findAvailableByLocal($booking->getIdLocal()) as $s) {
      if (!in_array($s->getIdService(), $bookedServiceIds, true)) {
        $availableServices[] = $s;
      }
    }

    // El cliente solo puede modificar (agregar servicios / pagar)
    // mientras la reserva esté pendiente y no haya subido comprobante.
    $isPending = $booking->getBookingState() === 'pendiente';
    $isModifiable = $isPending && $ticket === null;
    $isClient = ($_SESSION['type'] ?? null) === 'client';
    $hasTicket = $ticket !== null;

    $refundRequest = $isClient ? $this->refundRepo->findByBooking($idBooking) : null;

    return [
      'booking'              => $booking,
      'details'              => $details,
      'total'                => $totals['total'],
      'venue'                => $venue,
      'client'               => $client,
      'owner'                => $owner,
      'ticket'               => $ticket,
      'paymentMethods'       => $paymentMethods,
      'serviceMap'           => $serviceMap,
      'ownerPaymentMethods'  => $ownerPaymentMethods,
      'availableServices'    => $availableServices,
      'isModifiable'         => $isModifiable,
      'isClient'             => $isClient,
      'hasTicket'            => $hasTicket,
      'refundRequest'        => $refundRequest,
    ];
  }
}