<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/ServiceHistoryRepository.php';
require_once __DIR__ . '/../Model/ServiceHistory.php';

class ServiceHistoryService
{
  private ServiceHistoryRepository $historyRepo;

  public function __construct(ServiceHistoryRepository $historyRepo)
  {
    $this->historyRepo = $historyRepo;
  }

  // =========================================================
  // CONGELAR EL PRECIO ACTUAL DE UN SERVICIO
  // =========================================================
  public function snapshot(int $servicePk, float $price, ?string $date = null): int
  {
    if ($price < 0) {
      throw new BusinessRuleException('El precio a congelar no puede ser negativo.');
    }

    $effectiveDate = $date ?? date('Y-m-d');

    return $this->historyRepo->save(
      new ServiceHistory(
        idServiceHistory: 0,
        idService: $servicePk,
        price: $price,
        validFrom: $effectiveDate
      )
    );
  }

  // =========================================================
  // HISTORIAL COMPLETO DE UN SERVICIO
  // =========================================================
  public function getHistory(int $servicePk): array
  {
    return $this->historyRepo->findByService($servicePk);
  }

  // =========================================================
  // PRECIO VIGENTE EN UNA FECHA (para ganancias anuales)
  // =========================================================
  public function getPriceOnDate(int $servicePk, string $date): ?float
  {
    return $this->historyRepo->findPriceOnDate($servicePk, $date);
  }
}
