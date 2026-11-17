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
  private ?string $descriptionService;
  private string $unitService;
  private int $minQuantity;
  private ?int $maxQuantity;
  private int $includedQuantity;
  private ?int $idGroup;


  public function __construct(int $idService, int $idLocal, string $nameService, ?string $typeService, float $priceService, string $stateService, bool $isActive, ?int $approvedBy = null, ?string $approvedOn = null, ?string $descriptionService = null, string $unitService = 'unidad', int $minQuantity = 1, ?int $maxQuantity = null, int $includedQuantity = 0, ?int $idGroup = null)
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
    $this->descriptionService = $descriptionService;
    $this->unitService = $unitService;
    $this->minQuantity = $minQuantity;
    $this->maxQuantity = $maxQuantity;
    $this->includedQuantity = $includedQuantity;
    $this->idGroup = $idGroup;
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

  public function getDescriptionService(): ?string
  {
    return $this->descriptionService;
  }

  public function getUnitService(): string
  {
    return $this->unitService;
  }

  public function getMinQuantity(): int
  {
    return $this->minQuantity;
  }

  public function getMaxQuantity(): ?int
  {
    return $this->maxQuantity;
  }

  public function getIncludedQuantity(): int
  {
    return $this->includedQuantity;
  }

  public function getIdGroup(): ?int
  {
    return $this->idGroup;
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

  public function setDescriptionService(?string $descriptionService): void
  {
    $this->descriptionService = $descriptionService;
  }

  public function setUnitService(string $unitService): void
  {
    $this->unitService = $unitService;
  }

  public function setMinQuantity(int $minQuantity): void
  {
    $this->minQuantity = $minQuantity;
  }

  public function setMaxQuantity(?int $maxQuantity): void
  {
    $this->maxQuantity = $maxQuantity;
  }

  public function setIncludedQuantity(int $includedQuantity): void
  {
    $this->includedQuantity = $includedQuantity;
  }

  public function setIdGroup(?int $idGroup): void
  {
    $this->idGroup = $idGroup;
  }
}
