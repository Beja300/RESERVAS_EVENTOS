<?php

require_once __DIR__ . '/../Service/NotificationService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class NotificationController
{
  private NotificationService $notificationService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->notificationService = new NotificationService(
      new NotificationRepository($connection)
    );
  }

  // =========================================================
  // LISTAR MIS NOTIFICACIONES
  // =========================================================
  public function list(): void
  {
    session_start();
    $this->requireLogin();

    $role = $_SESSION['user'];

    $notifications = $this->notificationService->listForRole($role->getIdRol());
    $unreadCount = $this->notificationService->countUnread($role->getIdRol());

    require_once __DIR__ . '/../View/Notification/List.php';
  }

  // =========================================================
  // MARCAR COMO LEÍDA
  // =========================================================
  public function markAsRead(): void
  {
    session_start();
    $this->requireLogin();

    $role = $_SESSION['user'];
    $idNotification = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

    try {

      $this->notificationService->markAsRead($idNotification, $role->getIdRol());

      header('Location: ../../Public/index.php?controller=notification&action=list');
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ../../Public/index.php?controller=notification&action=list');
      exit;
    }
  }

  // =========================================================
  // MARCAR TODAS COMO LEÍDAS
  // =========================================================
  public function markAllAsRead(): void
  {
    session_start();
    $this->requireLogin();

    $role = $_SESSION['user'];

    $this->notificationService->markAllAsRead($role->getIdRol());

    header('Location: ../../Public/index.php?controller=notification&action=list');
    exit;
  }

  // =========================================================
  // GUARDIA: SOLO USUARIO LOGEADO
  // =========================================================
  private function requireLogin(): void
  {
    if (($_SESSION['type'] ?? null) === null) {
      header('Location: ../../Public/index.php?controller=auth&action=showLogin');
      exit;
    }
  }
}
