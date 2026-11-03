<?php

/**
 * ENTIDAD: Service — un servicio ofrecido por un Venue.
 */
class Service
{

  private int $idService;
  private int $idLocal;
  private string $nameService;
  private ?string $typeService;
  private float $priceService;
  private string $stateService;  // 'solicitado' | 'aprobado' | 'rechazado'
  private bool $isActive;
  private ?int $approvedBy;      // tbroleid del administrador que aprobó
  private ?string $approvedOn;   // fecha/hora de la aprobación


  public function __construct(int $idService, int $idLocal, string $nameService, ?string $typeService, float $priceService, string $stateService, bool $isActive, ?int $approvedBy = null, ?string $approvedOn = null)
  {
    $this->idService = $idService;
    $this->idLocal = $idLocal;
    $this->nameService = $nameService;
    $this->typeService = $typeService;
    $this->priceService = $priceService;
    $this->stateService = $stateService;
    $this->isActive = $isActive;
    $this->approvedBy = $approvedBy;
    $this->approvedOn = $approvedOn;
  }

  // Getters

  public function getIdService(): int
  {
    return $this->idService;
  }

  public function getIdLocal(): int
  {
    return $this->idLocal;
  }

  public function getNameService(): string
  {
    return $this->nameService;
  }

  public function getTypeService(): ?string
  {
    return $this->typeService;
  }

  public function getPriceService(): float
  {
    return $this->priceService;
  }

  public function getStateService(): string
  {
    return $this->stateService;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  public function getApprovedBy(): ?int
  {
    return $this->approvedBy;
  }

  public function getApprovedOn(): ?string
  {
    return $this->approvedOn;
  }

  // Setters

  public function setIdService(int $idService): void
  {
    $this->idService = $idService;
  }

  public function setIdLocal(int $idLocal): void
  {
    $this->idLocal = $idLocal;
  }

  public function setNameService(string $nameService): void
  {
    $this->nameService = $nameService;
  }

  public function setTypeService(?string $typeService): void
  {
    $this->typeService = $typeService;
  }

  public function setPriceService(float $priceService): void
  {
    $this->priceService = $priceService;
  }

  public function setStateService(string $stateService): void
  {
    $this->stateService = $stateService;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }

  public function setApprovedBy(?int $approvedBy): void
  {
    $this->approvedBy = $approvedBy;
  }

  public function setApprovedOn(?string $approvedOn): void
  {
    $this->approvedOn = $approvedOn;
  }
}
