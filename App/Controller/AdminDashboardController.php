<?php

require_once __DIR__ . '/../Service/AdminDashboardService.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminDashboardController
{
  private AdminDashboardService $dashboardService;

  public function __construct()
  {
    $this->dashboardService = new AdminDashboardService(DataBase::getConnection());
  }

  // =========================================================
  // DASHBOARD (estadísticas)
  // =========================================================
  public function dashboard(): void
  {
    require_role('admin');

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    extract($this->dashboardService->metrics($yearMonth), EXTR_SKIP);

    require_once __DIR__ . '/../View/Admin/Dashboard.php';
  }
}