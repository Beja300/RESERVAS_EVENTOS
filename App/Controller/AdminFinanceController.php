<?php

require_once __DIR__ . '/../Service/CommissionConfigService.php';
require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/CommissionConfigRepository.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class AdminFinanceController
{
  private CommissionConfigService $commissionConfigService;
  private NotificationService $notificationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->commissionConfigService = new CommissionConfigService(new CommissionConfigRepository($connection));
    $this->notificationService = new NotificationService(new NotificationRepository($connection));
  }

  // =========================================================
  // CONFIGURACIÓN DE COMISIÓN E IVA (solo admin)
  // =========================================================
  public function commissionConfig(): void
  {
    require_role('admin');

    $config = $this->commissionConfigService->getActive();

    require_once __DIR__ . '/../View/Admin/CommissionConfig.php';
  }

  // =========================================================
  // GUARDAR COMISIÓN E IVA (solo admin) — AJAX o POST normal
  // =========================================================
  public function saveCommissionConfig(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'commissionConfig');
    }

    $percentage = (float) trim($_POST['percentage'] ?? '');
    $tax        = (float) trim($_POST['tax'] ?? '');

    try {
      $this->commissionConfigService->setPercentage($percentage);
      $this->commissionConfigService->setTax($tax);

      $this->notificationService->notifyCommissionConfigChanged($percentage, $tax);

      respond_or_redirect(
        ['ok' => true, 'message' => 'Comisión e IVA actualizados correctamente.'],
        'admin',
        'commissionConfig',
        ['saved' => 1]
      );
    } catch (BusinessRuleException $e) {
      $error = $e->getMessage();

      respond_or_redirect(['ok' => false, 'message' => $error], 'admin', 'commissionConfig', ['error' => $error], 422);
    }
  }

  // =========================================================
  // LIMPIAR DATOS DE PRUEBA (botón cleaner del admin)
  // Envuelto en transacción: si algo falla se descarta todo.
  // =========================================================
  public function cleanTestData(): void
  {
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      redirect_to('admin', 'dashboard');
    }

    $connection = DataBase::getConnection();

    // Se vacían solo tablas de datos generados por el uso (prueba/demo).
    // NO se tocan datos maestros: roles, perfiles, ubicaciones,
    // métodos de pago ni configuración de comisión.
    $tables = [
      'tbeearning',
      'tbinvoice',
      'tbbookingticket',
      'tbbookingdetail',
      'tbbooking',
      'tbvenuerating',
      'tbservicerating',
      'tbpromotionservice',
      'tbpromotion',
      'tbservicehistory',
      'tbservice',
      'tbvenue',
      'tbnotification',
      'tbuserhistory',
      'tbownerhistory',
      'tbownerpayment',
    ];

    try {
      $connection->beginTransaction();

      foreach ($tables as $table) {
        $connection->exec('DELETE FROM ' . $table);
      }

      $connection->commit();
    } catch (\PDOException $e) {
      if ($connection->inTransaction()) {
        $connection->rollBack();
      }
      redirect_to('admin', 'dashboard', ['error' => 'No se pudieron limpiar los datos.']);
    }

    redirect_to('admin', 'dashboard', ['cleaned' => 1]);
  }
}