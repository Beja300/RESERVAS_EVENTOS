<?php


require_once __DIR__ . '/Role.php';
/**
 * ENTIDAD: Owner
 * Hereda de Role (correo/contrasena/login son los mismos para cualquier
 * rol), y agrega los datos propios de tbpropietario -- que incluyen su
 * PROPIO nombre/apellido, distintos del "nombre" genérico de tbrol.
 */

class Owner extends Rol {

  private int $idOwner;
  private string $firstNameOwner;
  private string $lastNameOwner;
  private string $aliasOwner;
  private string $identificationNumberOwner;
  private bool $isOwnerActive;
  private int $idRol;

  public function __construct($id, $name, $email, $password, $phoneNumber = null, $isActive, $idOwner, $firstName, $lastName, $alias, $identificationNumber, $isOwnerActive, $idRol) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idOwner = $idOwner;
    $this->firstNameOwner = $firstName;
    $this->lastNameOwner = $lastName;
    $this->aliasOwner = $alias;
    $this->identificationNumberOwner = $identificationNumber;
    $this->isOwnerActive = $isOwnerActive;
    $this->idRol = $idRol;
  }

  //Getters
  public function getIdOwner() {
    return $this->idOwner;
  }
  public function getFirstNameOwner() {
    return $this->firstNameOwner;
  }
  public function getLastNameOwner() {
    return $this->lastNameOwner;
  }
  public function getIsOwnerActive() {
    return $this->isOwnerActive;
  }
  public function getIdRol() {
    return $this->idRol;
  }

  public function getAliasOwner() {
    return $this->aliasOwner;
  }

  public function getIdentificationNumberOwner() {
    return $this->identificationNumberOwner;
  }

  //Setters
  public function setIdOwner($idOwner) {
    $this->idOwner = $idOwner;
  }

  public function setFirstNameOwner($firstNameOwner) {
    $this->firstNameOwner = $firstNameOwner;
  }

  public function setLastNameOwner($lastNameOwner) {
    $this->lastNameOwner = $lastNameOwner;
  }

  public function setIsOwnerActive($isOwnerActive) {
    $this->isOwnerActive = $isOwnerActive;
  }

  public function setIdRol($idRol) {
    $this->idRol = $idRol;
  }


  public function setAliasOwner($aliasOwner) {
    $this->aliasOwner = $aliasOwner;
  }

  public function setIdentificationNumberOwner($identificationNumberOwner) {
    $this->identificationNumberOwner = $identificationNumberOwner;
  }
}