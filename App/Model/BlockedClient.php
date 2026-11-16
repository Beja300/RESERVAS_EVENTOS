<?php

/**
 * ENTIDAD: BlockedClient — cliente bloqueado por un propietario.
 */
class BlockedClient
{
  private int $idBlock;
  private int $idOwner;
  private int $idClient;
  private string $reason;
  private string $date;
  private bool $isActive;

  public function __construct(
    int $idBlock = 0,
    int $idOwner = 0,
    int $idClient = 0,
    string $reason = '',
    string $date = '',
    bool $isActive = true
  ) {
    $this->idBlock = $idBlock;
    $this->idOwner = $idOwner;
    $this->idClient = $idClient;
    $this->reason = $reason;
    $this->date = $date;
    $this->isActive = $isActive;
  }

  public function getIdBlock(): int { return $this->idBlock; }
  public function getIdOwner(): int { return $this->idOwner; }
  public function getIdClient(): int { return $this->idClient; }
  public function getReason(): string { return $this->reason; }
  public function getDate(): string { return $this->date; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdBlock(int $idBlock): void { $this->idBlock = $idBlock; }
  public function setIdOwner(int $idOwner): void { $this->idOwner = $idOwner; }
  public function setIdClient(int $idClient): void { $this->idClient = $idClient; }
  public function setReason(string $reason): void { $this->reason = $reason; }
  public function setDate(string $date): void { $this->date = $date; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
