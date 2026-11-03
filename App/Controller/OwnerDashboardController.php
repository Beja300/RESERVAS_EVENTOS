<?php

require_once __DIR__ . '/../Service/OwnerDashboardService.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class OwnerDashboardController
{
  private OwnerDashboardService $dashboardService;

  public function __construct()
  {
    $this->dashboardService = new OwnerDashboardService(DataBase::getConnection());
  }

  // =========================================================
  // DASHBOARD (sus locales + sus reservas)
  // =========================================================
  public function dashboard(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    extract($this->dashboardService->metrics($owner->getIdOwner(), $yearMonth), EXTR_SKIP);

    require_once __DIR__ . '/../View/Owner/Dashboard.php';
  }
}