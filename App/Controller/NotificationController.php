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

      header('Location: ' . base_url('notification', 'list'));
      exit;
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      header('Location: ' . base_url('notification', 'list'));
      exit;
    }
  }

  // =========================================================
  // ABRIR (marca como leída y redirige al motivo)
  // =========================================================
  public function open(): void
  {
    session_start();
    $this->requireLogin();

    $role = $_SESSION['user'];
    $idNotification = (int) ($_GET['id'] ?? 0);
    $listUrl = base_url('notification', 'list');

    try {
      $link = $this->notificationService->open($idNotification, $role->getIdRol());

      header('Location: ' . ($link !== null && $link !== '' ? $link : $listUrl));
      exit;
    } catch (BusinessRuleException $e) {
      header('Location: ' . $listUrl);
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

    header('Location: ' . base_url('notification', 'list'));
    exit;
  }

  // =========================================================
  // GUARDIA: SOLO USUARIO LOGEADO
  // =========================================================
  private function requireLogin(): void
  {
    if (($_SESSION['type'] ?? null) === null) {
      header('Location: ' . base_url('auth', 'showLogin'));
      exit;
    }
  }
}
