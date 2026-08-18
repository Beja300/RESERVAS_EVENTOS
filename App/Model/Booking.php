<?php
/**
 * ENTIDAD: Booking — la Booking hecha por un cliente en un local.
 */

class Booking{

  private int $idBooking;
  private int $idClient;
  private int $idLocal;
  private string $bookingDate;
  private string $bookingState;
  private bool $isBookingActive;


  public function __construct($idBooking, $idClient, $idLocal, $bookingDate, $bookingState, $isBookingActive) {
    $this->idBooking = $idBooking;
    $this->idClient = $idClient;
    $this->idLocal = $idLocal;
    $this->bookingDate = $bookingDate;
    $this->bookingState = $bookingState;
    $this->isBookingActive = $isBookingActive;
  }


  //Getters
  public function getIdBooking() {
    return $this->idBooking;
  }

  public function getIdClient() {
    return $this->idClient;
  }

  public function getIdLocal() {
    return $this->idLocal;
  }

  public function getBookingDate() {
    return $this->bookingDate;
  }

  public function getBookingState() {
    return $this->bookingState;
  }

  public function getIsBookingActive() {
    return $this->isBookingActive;
  } 

  //Setters
  public function setIdBooking($idBooking) {
    $this->idBooking = $idBooking;  
  }

  public function setIdClient($idClient) {
    $this->idClient = $idClient;
  }

  public function setIdLocal($idLocal) {
    $this->idLocal = $idLocal;
  }

  public function setBookingDate($bookingDate) {
    $this->bookingDate = $bookingDate;
  }

  public function setBookingState($bookingState) {
    $this->bookingState = $bookingState;
  }

  public function setIsBookingActive($isBookingActive) {
    $this->isBookingActive = $isBookingActive;
  }
}