<?php

/**
 * ENTIDAD: PromotionServiceLink — servicio incluido en una promoción.
 * Relaciona un servicio con la promoción que lo cubre.
 */
class PromotionServiceLink
{
  private int $idPromotionService;
  private int $idPromotion;
  private int $idService;
  private bool $isActive;

  public function __construct(
    int $idPromotionService = 0,
    int $idPromotion = 0,
    int $idService = 0,
    bool $isActive = true
  ) {
    $this->idPromotionService = $idPromotionService;
    $this->idPromotion = $idPromotion;
    $this->idService = $idService;
    $this->isActive = $isActive;
  }

  // Getters
  public function getIdPromotionService(): int
  {
    return $this->idPromotionService;
  }

  public function getIdPromotion(): int
  {
    return $this->idPromotion;
  }

  public function getIdService(): int
  {
    return $this->idService;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters
  public function setIdPromotionService(int $idPromotionService): void
  {
    $this->idPromotionService = $idPromotionService;
  }

  public function setIdPromotion(int $idPromotion): void
  {
    $this->idPromotion = $idPromotion;
  }

  public function setIdService(int $idService): void
  {
    $this->idService = $idService;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
