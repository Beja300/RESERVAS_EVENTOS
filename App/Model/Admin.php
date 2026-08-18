<?php

require_once __DIR__ . '/Role.php';
/**
 * ENTIDAD: Admin
 *
 * "extends Role" -- hereda pk, nombre, correo, contrasena, telefono y
 * activo de la clase Role, y agrega SOLO lo propio de tbroladmin.
 */
class Admin extends Rol {

  private int $idAdmin;
  private bool $isAdminActive;
  private int $idRol;

  public function __construct($id, $name, $email, $password, $phoneNumber = null, $isActive, $idAdmin, $isAdminActive, $idRol) {
    parent::__construct($id, $name, $email, $password, $phoneNumber, $isActive);
    $this->idAdmin = $idAdmin;
    $this->isAdminActive = $isAdminActive;
    $this->idRol = $idRol;
  }

  //Getters
  public function getIdAdmin() {
    return $this->idAdmin;
  }
  public function getIsAdminActive() {
    return $this->isAdminActive;
  }
  public function getIdRol() {
    return $this->idRol;
  }

  //Setters
  public function setIdAdmin($idAdmin) {
    $this->idAdmin = $idAdmin;
  }

  public function setIsAdminActive($isAdminActive) {
    $this->isAdminActive = $isAdminActive;
  }

  public function setIdRol($idRol) {
    $this->idRol = $idRol;
  }
}