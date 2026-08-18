<?php

/**
 * ENTIDAD BASE: Role
 *
 * Representa un registro de la tabla tbrol -- la identidad "raíz"
 * que comparten Admin, Client y Owner. Solo tiene los datos
 * comunes a cualquier persona con cuenta en el sistema.
 *
 * Admin, Client y Owner EXTIENDEN esta clase (herencia),
 * igual que en el diagrama de clases: cada uno hereda estos atributos
 * y agrega los suyos propios.
 */
class Role {


 protected $id;
 protected string $name;
 protected string $email;
 protected string $password;
 protected ?string $phoneNumber;
 protected ?string $isActive;

 public function __construct($id, $name, $email, $password, $phoneNumber = null, $isActive = null) {
     $this->id = $id;
     $this->name = $name;
     $this->email = $email;
     $this->password = $password;
     $this->phoneNumber = $phoneNumber;
     $this->isActive = $isActive;
 }


 //Getters
 public function getId(){
  return $this->id;
 }

 public function getName(){
  return $this->name;
 }

 public function getEmail(){
  return $this->email;
 }

 public function getPassword(){
  return $this->password;
 }
 public function getPhoneNumber(){
  return $this->phoneNumber;
 }
 public function getIsActive(){
  return $this->isActive;
 }
 //Setters
 public function setId($id){
  $this->id = $id;
 }
  public function setName($name){
  $this->name = $name;
  }
  public function setEmail($email){
  $this->email = $email;
  }
  public function setPassword($password){
  $this->password = $password;
  }
  public function setPhoneNumber($phoneNumber){
  $this->phoneNumber = $phoneNumber;
  }
  public function setIsActive($isActive){
  $this->isActive = $isActive;
  }

}