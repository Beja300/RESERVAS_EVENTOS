<?php

/**
 * ENTIDAD: Invoice — la factura generada para una reserva (1:1).
 */

class Invoice extends Model
{
    private int $idInvoice;
    private int $idClientBooking;
    private int $idPaymentMethod;
    private date $dateInvoice;
    private string $statusInvoice;
    private bool $isActiveInvoice; 


    public function __construct($idInvoice, $idClientBooking, $idPaymentMethod, $dateInvoice, $statusInvoice, $isActiveInvoice) {
        $this->idInvoice = $idInvoice;
        $this->idClientBooking = $idClientBooking;
        $this->idPaymentMethod = $idPaymentMethod;
        $this->dateInvoice = $dateInvoice;
        $this->statusInvoice = $statusInvoice;
        $this->isActiveInvoice = $isActiveInvoice;
    }


    //Getters

    public function getIdInvoice() {
        return $this->idInvoice;
    }

    public function getIdClientBooking() {
        return $this->idClientBooking;
    }

    public function getIdPaymentMethod() {
        return $this->idPaymentMethod;
    }

    public function getDateInvoice() {
        return $this->dateInvoice;
    }

    public function getStatusInvoice() {
        return $this->statusInvoice;
    }

    public function getIsActiveInvoice() {
        return $this->isActiveInvoice;
    }

    //Setters

    public function setIdInvoice($idInvoice) {
        $this->idInvoice = $idInvoice;  
    }

    public function setIdClientBooking($idClientBooking) {
        $this->idClientBooking = $idClientBooking;  
    }

    public function setIdPaymentMethod($idPaymentMethod) {
        $this->idPaymentMethod = $idPaymentMethod;  
    }

    public function setDateInvoice($dateInvoice) {
        $this->dateInvoice = $dateInvoice;  
    }

    public function setStatusInvoice($statusInvoice) {
        $this->statusInvoice = $statusInvoice;  
    }


    public function setIsActiveInvoice($isActiveInvoice) {
        $this->isActiveInvoice = $isActiveInvoice;  
    }

}