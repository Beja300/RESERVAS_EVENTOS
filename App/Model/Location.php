<?php
/**
 * ENTIDAD: Location — sin FK, tabla independiente.
 */

class Location{

  private int $idLocation;
  private string $provinceLocation;
  private string $cantonLocation;
  private string $districtLocation;
  private string $addressLocation; //Detalle de la dirección, ej: "Casa de Juan Pérez, frente a la iglesia"


  public function __construct($idLocation, $provinceLocation, $cantonLocation, $districtLocation, $addressLocation) {
    $this->idLocation = $idLocation;
    $this->provinceLocation = $provinceLocation;
    $this->cantonLocation = $cantonLocation;
    $this->districtLocation = $districtLocation;
    $this->addressLocation = $addressLocation;
  } 


  //Getters

  public function getIdLocation() {
    return $this->idLocation;
  }

  public function getProvinceLocation(){
    return this ->provinceLocation;
  }

  public function getCantonLocation(){
    return this ->cantonLocation;
  }

   public function getDistrictLocation(){
    return this ->districtLocation;
  }

   public function getAddressLocation(){
    return this ->addressLocation;
  }
  

  //Setter

  public function setIdLocation( $idLocation)
  { 
    $this->idLocation = $idLocation; 
  }

  public function setProvinceLocation($provinceLocation){
    $this->provinceLocation = provinceLocation;
  }

  public function setDistrictLocation($districtLocation){
    $this->districtLocation = districtLocation;
  }

  public function setAddressLocation($addressLocation){
    $this->addressLocation = addressLocation;
  }
}