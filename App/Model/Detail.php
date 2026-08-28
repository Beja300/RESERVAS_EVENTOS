<?php

/**
 * ENTIDAD: Detail — una línea del "carrito" de una reserva.
 */
class Detail
{
  private int $idDetail;
  private int $idClientBooking;
  private int $idLocalService;
  private int $quantityDetail;
  private float $unitPrice;
  private float $discount;
  private bool $isActiveDetail;

  public function __construct(
    int $idDetail,
    int $idClientBooking,
    int $idLocalService,
    int $quantityDetail,
    float $unitPrice,
    float $discount,
    bool $isActiveDetail
  ) {
    $this->idDetail = $idDetail;
    $this->idClientBooking = $idClientBooking;
    $this->idLocalService = $idLocalService;
    $this->quantityDetail = $quantityDetail;
    $this->unitPrice = $unitPrice;
    $this->discount = $discount;
    $this->isActiveDetail = $isActiveDetail;
  }

  // Getters
  public function getIdDetail(): int
  {
    return $this->idDetail;
  }

  public function getIdClientBooking(): int
  {
    return $this->idClientBooking;
  }

  public function getIdLocalService(): int
  {
    return $this->idLocalService;
  }

  public function getQuantityDetail(): int
  {
    return $this->quantityDetail;
  }

  public function getUnitPrice(): float
  {
    return $this->unitPrice;
  }

  public function getDiscount(): float
  {
    return $this->discount;
  }

  public function getIsActiveDetail(): bool
  {
    return $this->isActiveDetail;
  }

  // Subtotal de la línea: cantidad x precio unitario - descuento
  public function getSubtotal(): float
  {
    return $this->quantityDetail * $this->unitPrice - $this->discount;
  }

  // Setters
  public function setIdDetail(int $idDetail): void
  {
    $this->idDetail = $idDetail;
  }

  public function setIdClientBooking(int $idClientBooking): void
  {
    $this->idClientBooking = $idClientBooking;
  }

  public function setIdLocalService(int $idLocalService): void
  {
    $this->idLocalService = $idLocalService;
  }

  public function setQuantityDetail(int $quantityDetail): void
  {
    $this->quantityDetail = $quantityDetail;
  }

  public function setUnitPrice(float $unitPrice): void
  {
    $this->unitPrice = $unitPrice;
  }

  public function setDiscount(float $discount): void
  {
    $this->discount = $discount;
  }

  public function setIsActiveDetail(bool $isActiveDetail): void
  {
    $this->isActiveDetail = $isActiveDetail;
  }
}
