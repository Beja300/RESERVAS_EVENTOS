<?php


require_once __DIR__ . '/Role.php';

/**
 * ENTIDAD: Client
 * Mismo patrón que Admin: hereda de Role, agrega lo propio de tbrolcliente.
 */
class Client extends Role {

 private int $idClient;
 private bool $isClientActive;
 private int $idRol;

  public function __construct($id, $name, $email, $password, $phoneNumber = null, $isActive, $idClient, $isClientActive, $idRol) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idClient = $idClient;
    $this->isClientActive = $isClientActive;
    $this->idRol = $idRol;
  }

 //Getters
  public function getIdClient() {
    return $this->idClient;
  }
  public function getIsClientActive() {
    return $this->isClientActive;
  }

  public function getIdRol() {
    return $this->idRol;
  }

  //Setters
  public function setIdClient($idClient) {
    $this->idClient = $idClient;
  }

  public function setIsClientActive($isClientActive) {
    $this->isClientActive = $isClientActive;
  }

  public function setIdRol($idRol) {
    $this->idRol = $idRol;
  }

}