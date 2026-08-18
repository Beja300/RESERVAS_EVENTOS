<?php

/**
 * ENTIDAD: Notification — un mensaje dirigido a un Role (cualquier subtipo).
 */

class Notification
{

  private int $idNotification;
  private int $idRol;
  private string $messageNotification;
  private string $dateNotification;
  private bool $isRead;
  private bool $isActive;



  public function __construct(int $idNotification, int $idRol, string $messageNotification, string $dateNotification, bool $isActive, bool $isRead = false)
  {

    $this->idNotification = $idNotification;
    $this->idRol = $idRol;
    $this->messageNotification = $messageNotification;
    $this->dateNotification = $dateNotification;
    $this->isRead = $isRead;
    $this->isActive = $isActive;
  }

  // Getters

  public function getIdNotification(): int
  {
    return $this->idNotification;
  }

  public function getIdRol(): int
  {
    return $this->idRol;
  }

  public function getMessageNotification(): string
  {
    return $this->messageNotification;
  }

  public function getDateNotification(): string
  {
    return $this->dateNotification;
  }

  public function getIsRead(): bool
  {
    return $this->isRead;
  }

  public function getIsActive(): bool
  {
    return $this->isActive;
  }

  // Setters

  public function setIdNotification(int $idNotification): void
  {
    $this->idNotification = $idNotification;
  }

  public function setIdRol(int $idRol): void
  {
    $this->idRol = $idRol;
  }

  public function setMessageNotification(string $messageNotification): void
  {
    $this->messageNotification = $messageNotification;
  }

  public function setDateNotification(string $dateNotification): void
  {
    $this->dateNotification = $dateNotification;
  }

  public function setIsRead(bool $isRead): void
  {
    $this->isRead = $isRead;
  }

  public function setIsActive(bool $isActive): void
  {
    $this->isActive = $isActive;
  }
}
