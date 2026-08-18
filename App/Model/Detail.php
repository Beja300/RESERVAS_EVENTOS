<?php

/**
 * ENTIDAD: Detail — una línea del "carrito" de una reserva.
 */
class Detail {

 private int $idDetail;
 private int $idClientBooking;
 private int $idLocalService;
 private int $quantityDetail; 
 private float $unitPrice;
 private float $discount;
 private bool $isActiveDetail;

  public function __construct($idDetail, $idClientBooking, $idLocalService, $quantityDetail, $unitPrice, $discount, $isActiveDetail) {
    $this->idDetail = $idDetail;
    $this->idClientBooking = $idClientBooking;
    $this->idLocalService = $idLocalService;
    $this->quantityDetail = $quantityDetail;
    $this->unitPrice = $unitPrice;
    $this->discount = $discount;
    $this->isActiveDetail = $isActiveDetail;
  }


  //Getters
  public function getIdDetail() {
    return $this->idDetail;
  }

  public function getIdClientBooking() {
    return $this->idClientBooking;
  }

  public function getIdLocalService() {
    return $this->idLocalService;
  }

  public function getQuantityDetail() {
    return $this->quantityDetail;
  }

  public function getUnitPrice() {
    return $this->unitPrice;
  }

  public function getDiscount() {
    return $this->discount;
  }

  public function getIsActiveDetail() {
    return $this->isActiveDetail;
  }

  //Setters

  public function setIdDetail($idDetail) {
    $this->idDetail = $idDetail;  
  }

  public function setIdClientBooking($idClientBooking) {
    $this->idClientBooking = $idClientBooking;
  }

  public function setIdLocalService($idLocalService) {
    $this->idLocalService = $idLocalService;
  }

  public function setQuantityDetail($quantityDetail) {
    $this->quantityDetail = $quantityDetail;
  }

  public function setUnitPrice($unitPrice) {
    $this->unitPrice = $unitPrice;
  }

  public function setDiscount($discount) {
    $this->discount = $discount;
  }

  public function setIsActiveDetail($isActiveDetail) {
    $this->isActiveDetail = $isActiveDetail;
  }

  
}