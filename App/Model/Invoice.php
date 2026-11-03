<?php

/**
 * ENTIDAD: Invoice — la factura generada para una reserva (1:1).
 */
class Invoice
{
    private int $idInvoice;
    private int $idClientBooking;
    private int $idPaymentMethod;
    private string $dateInvoice;
    private string $statusInvoice;
    private bool $isActiveInvoice;

    public function __construct(
        int $idInvoice,
        int $idClientBooking,
        int $idPaymentMethod,
        string $dateInvoice,
        string $statusInvoice,
        bool $isActiveInvoice
    ) {
        $this->idInvoice = $idInvoice;
        $this->idClientBooking = $idClientBooking;
        $this->idPaymentMethod = $idPaymentMethod;
        $this->dateInvoice = $dateInvoice;
        $this->statusInvoice = $statusInvoice;
        $this->isActiveInvoice = $isActiveInvoice;
    }

    // Getters
    public function getIdInvoice(): int
    {
        return $this->idInvoice;
    }

    public function getIdClientBooking(): int
    {
        return $this->idClientBooking;
    }

    public function getIdPaymentMethod(): int
    {
        return $this->idPaymentMethod;
    }

    public function getDateInvoice(): string
    {
        return $this->dateInvoice;
    }

    public function getStatusInvoice(): string
    {
        return $this->statusInvoice;
    }

    public function getIsActiveInvoice(): bool
    {
        return $this->isActiveInvoice;
    }

    // Setters
    public function setIdInvoice(int $idInvoice): void
    {
        $this->idInvoice = $idInvoice;
    }

    public function setIdClientBooking(int $idClientBooking): void
    {
        $this->idClientBooking = $idClientBooking;
    }

    public function setIdPaymentMethod(int $idPaymentMethod): void
    {
        $this->idPaymentMethod = $idPaymentMethod;
    }

    public function setDateInvoice(string $dateInvoice): void
    {
        $this->dateInvoice = $dateInvoice;
    }

    public function setStatusInvoice(string $statusInvoice): void
    {
        $this->statusInvoice = $statusInvoice;
    }

    public function setIsActiveInvoice(bool $isActiveInvoice): void
    {
        $this->isActiveInvoice = $isActiveInvoice;
    }
}
