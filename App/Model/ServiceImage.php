<?php

/**
 * ENTIDAD: ServiceImage — una foto de la galería de un servicio.
 */
class ServiceImage
{
  private int $idImage;
  private int $idService;
  private string $path;
  private int $sort;
  private bool $isActive;

  public function __construct(
    int $idImage = 0,
    int $idService = 0,
    string $path = '',
    int $sort = 0,
    bool $isActive = true
  ) {
    $this->idImage = $idImage;
    $this->idService = $idService;
    $this->path = $path;
    $this->sort = $sort;
    $this->isActive = $isActive;
  }

  public function getIdImage(): int { return $this->idImage; }
  public function getIdService(): int { return $this->idService; }
  public function getPath(): string { return $this->path; }
  public function getSort(): int { return $this->sort; }
  public function getIsActive(): bool { return $this->isActive; }

  public function setIdImage(int $idImage): void { $this->idImage = $idImage; }
  public function setIdService(int $idService): void { $this->idService = $idService; }
  public function setPath(string $path): void { $this->path = $path; }
  public function setSort(int $sort): void { $this->sort = $sort; }
  public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
}
