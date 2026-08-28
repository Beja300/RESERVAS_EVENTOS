<?php

/**
 * ENTIDAD: Promotion — promoción vigente de un local.
 * La etiqueta (label) se muestra en el catálogo; la vigencia y el
 * mínimo de servicios definen cuándo aplica.
 */
class Promotion
{
  private int $idPromotion;
  private int $idVenue;
  private string $description;
  private string $label;
  private ?string $startDate;
  private ?string $endDate;
  private int $minServices;
  private bool $isActive;

  public function __construct(
    int $idPromotion = 0,
    int $idVenue = 0,
    string $description = '',
    string $label = '',
    ?string $startDate = null,
    ?string $endDate = null,
    int $minServices = 1,
    bool $isActive = true
  ) {
    $this->idPromotion = $idPromotion;
    $this->idVenue = $idVenue;
    $this->description = $description;
    $this->label = $label;
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->minServices = $minServices;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdPromotion(): int
  {
    return $this->idPromotion;
  }

  public function getIdVenue(): int
  {
    return $this->idVenue;
  }

  public function getDescription(): string
  {
    return $this->description;
  }

  public function getLabel(): string
  {
    return $this->label;
  }

  public function getStartDate(): ?string
  {
    return $this->startDate;
  }

  public function getEndDate(): ?string
  {
    return $this->endDate;
  }

  public function getMinServices(): int
  {
    return $this->minServices;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdPromotion(int $idPromotion): void
  {
    $this->idPromotion = $idPromotion;
  }

  public function setIdVenue(int $idVenue): void
  {
    $this->idVenue = $idVenue;
  }

  public function setDescription(string $description): void
  {
    $this->description = $description;
  }

  public function setLabel(string $label): void
  {
    $this->label = $label;
  }

  public function setStartDate(?string $startDate): void
  {
    $this->startDate = $startDate;
  }

  public function setEndDate(?string $endDate): void
  {
    $this->endDate = $endDate;
  }

  public function setMinServices(int $minServices): void
  {
    $this->minServices = $minServices;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
