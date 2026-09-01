<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
require_once __DIR__ . '/../Model/CommissionConfig.php';

class CommissionConfigService
{
  private CommissionConfigRepository $configRepo;

  public function __construct(CommissionConfigRepository $configRepo)
  {
    $this->configRepo = $configRepo;
  }

  // =========================================================
  // OBTENER CONFIGURACIÓN ACTIVA (la única vigente)
  // =========================================================
  public function getActive(): CommissionConfig
  {
    $config = $this->configRepo->findActive();

    if ($config === null) {
      $id = $this->configRepo->save(new CommissionConfig(0, 5.00, 13.00, true));
      $config = new CommissionConfig($id, 5.00, 13.00, true);
    }

    return $config;
  }

  // =========================================================
  // ACTUALIZAR PORCENTAJE DE COMISIÓN (solo admin)
  // =========================================================
  public function setPercentage(float $percentage): CommissionConfig
  {
    if ($percentage < 0 || $percentage > 100) {
      throw new BusinessRuleException('El porcentaje de comisión debe estar entre 0 y 100.');
    }

    $config = $this->getActive();

    if ($config->getPercentage() !== $percentage) {
      $config->setPercentage($percentage);
      $this->configRepo->update($config);
    }

    return $config;
  }

  // =========================================================
  // ACTUALIZAR PORCENTAJE DE IVA (solo admin)
  // =========================================================
  public function setTax(float $tax): CommissionConfig
  {
    if ($tax < 0 || $tax > 100) {
      throw new BusinessRuleException('El porcentaje de IVA debe estar entre 0 y 100.');
    }

    $config = $this->getActive();

    if ($config->getTax() !== $tax) {
      $config->setTax($tax);
      $this->configRepo->update($config);
    }

    return $config;
  }
}
