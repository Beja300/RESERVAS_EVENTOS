<?php

/**
 * ENTIDAD: VenueRentalBlock — bloque horario de alquiler de un local.
 */
class VenueRentalBlock
{
  private int $idBlock;
  private int $idVenue;
  private string $name;
  private string $startTime;
  private string $endTime;
  private float $price;
  private int $sort;
  private bool $isActive;

  public function __construct(
    int $idBlock = 0,
    int $idVenue = 0,
    string $name = '',
    string $startTime = '',
    string $endTime = '',
    float $price = 0.0,
    int $sort = 0,
    bool $isActive = true
  ) {
    $this->idBlock = $idBlock;
    $this->idVenue = $idVenue;
    $this->name = $name;
    $this->startTime = $startTime;
    $this->endTime = $endTime;
    $this->price = $price;
    $this->sort = $sort;
    $this->isActive = $isActive;
  }

  public function getIdBlock(): int { return $this->idBlock; }
  public function getIdVenue(): int { return $this->idVenue; }
  public function getName(): string { return $this->name; }
  public function getStartTime(): string { return $this->startTime; }
  public function getEndTime(): string { return $this->endTime; }
  public function getPrice(): float { return $this->price; }
  public function getSort(): int { return $this->sort; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdBlock(int $idBlock): void { $this->idBlock = $idBlock; }
  public function setIdVenue(int $idVenue): void { $this->idVenue = $idVenue; }
  public function setName(string $name): void { $this->name = $name; }
  public function setStartTime(string $startTime): void { $this->startTime = $startTime; }
  public function setEndTime(string $endTime): void { $this->endTime = $endTime; }
  public function setPrice(float $price): void { $this->price = $price; }
  public function setSort(int $sort): void { $this->sort = $sort; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
