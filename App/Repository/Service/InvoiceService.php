<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/PaymentMethodService.php';
require_once __DIR__ . '/../Repository/InvoiceRepository.php';
require_once __DIR__ . '/../Repository/BookingRepository.php';
require_once __DIR__ . '/../Model/Invoice.php';

class InvoiceService
{
    private InvoiceRepository $invoiceRepo;
    private BookingRepository $bookingRepo;
    private PaymentMethodService $paymentMethodService;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->invoiceRepo = new InvoiceRepository($connection);
        $this->bookingRepo = new BookingRepository($connection);
        $this->paymentMethodService = new PaymentMethodService();
    }

    public function generate(
        int $bookingPk,
        int $paymentMethodPk,
        string $date
    ): int {
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
                'Solo se puede generar el pago para una reserva pendiente.'
            );
        }

        if (
            $this->invoiceRepo->findByBooking(
                $bookingPk
            ) !== null
        ) {
            throw new BusinessRuleException(
                'Esta reserva ya tiene un pago registrado.'
            );
        }

        $this->paymentMethodService
            ->assertIsSelectable(
                $paymentMethodPk
            );

        return $this->invoiceRepo->save(
            new Invoice(
                0,
                $booking->getIdBooking(),
                $paymentMethodPk,
                date('Y-m-d'),
                'pendiente',
                true
            )
        );
    }

    public function approve(int $bookingPk): void
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
                'Esta reserva ya no está pendiente.'
            );
        }

        $invoice =
            $this->invoiceRepo->findByBooking(
                $bookingPk
            );

        if ($invoice === null) {
            throw new BusinessRuleException(
                'La reserva no tiene un pago registrado.'
            );
        }

        if (
            $invoice->getStatusInvoice() !== 'pendiente'
        ) {
            throw new BusinessRuleException(
                'Este pago ya fue procesado.'
            );
        }

        $this->invoiceRepo->updateStatus(
            $invoice->getIdInvoice(),
            'pagado'
        );

        $this->bookingRepo->updateStatus(
            $bookingPk,
            'confirmado'
        );
    }

    public function reject(int $bookingPk): void
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
                'Esta reserva ya no está pendiente.'
            );
        }

        $invoice =
            $this->invoiceRepo->findByBooking(
                $bookingPk
            );

        if ($invoice === null) {
            throw new BusinessRuleException(
                'La reserva no tiene un pago registrado.'
            );
        }

        if (
            $invoice->getStatusInvoice() !== 'pendiente'
        ) {
            throw new BusinessRuleException(
                'Este pago ya fue procesado.'
            );
        }

        /*
         * El pago queda rechazado.
         */
        $this->invoiceRepo->updateStatus(
            $invoice->getIdInvoice(),
            'rechazado'
        );

        /*
         * La reserva NO se elimina.
         *
         * Se conserva para que el cliente pueda verla
         * en "Mis reservas" con estado "Rechazado".
         */
        $this->bookingRepo->updateStatus(
            $bookingPk,
            'rechazado'
        );
    }

  public function updatePaymentStatus(
    int $bookingPk,
    string $status
  ): void {
        if (
            !in_array(
                $status,
                ['pendiente', 'pagado'],
                true
            )
        ) {
            throw new BusinessRuleException(
                'Estado de pago no válido.'
            );
        }

        $booking =
            $this->bookingRepo->findById($bookingPk);

        if ($booking === null) {
            throw new BusinessRuleException(
                'La reserva no existe.'
            );
        }

        $invoice =
            $this->invoiceRepo->findByBooking(
                $bookingPk
            );

        if ($invoice === null) {
            throw new BusinessRuleException(
                'La reserva no tiene un pago registrado.'
            );
        }

        $this->invoiceRepo->updateStatus(
            $invoice->getIdInvoice(),
            $status
        );

        if ($status === 'pagado') {

            $this->bookingRepo->updateStatus(
                $bookingPk,
                'confirmado'
            );
        } else {

            $this->bookingRepo->updateStatus(
                $bookingPk,
                'pendiente'
            );
        }
    }

    // =========================================================
    // FACTURA DE UNA RESERVA (o null)
    // =========================================================
    public function findByBooking(int $bookingPk): ?Invoice
    {
        return $this->invoiceRepo->findByBooking($bookingPk);
    }
}
