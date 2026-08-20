<?php

require_once __DIR__ . '/../models/Notification.php';

class NotificationRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveNotification(Notification $notification): bool
  {
    try {
      $sql = "
                INSERT INTO tbnotification (
                    tbnotificationroleid,
                    tbnotificationmessage,
                    tbnotificationdate,
                    tbnotificationisread,
                    tbnotificationisactive
                )
                VALUES (
                    :idRol,
                    :message,
                    :date,
                    :isRead,
                    :isActive
                )
            ";

      $stmt = $this->connection->prepare($sql);

      $stmt->execute([
        ':idRol' => $notification->getIdRol(),
        ':message' => $notification->getMessageNotification(),
        ':date' => $notification->getDateNotification(),
        ':isRead' => $notification->getIsRead(),
        ':isActive' => $notification->getIsActive()
      ]);

      $notification->setIdNotification((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllNotification(): array
  {
    $sql = "
            SELECT
                tbnotificationid,
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationdate,
                tbnotificationisread,
                tbnotificationisactive

            FROM tbnotification

            ORDER BY tbnotificationdate DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $notifications = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $notifications[] = $this->mapRowToNotification($row);
    }

    return $notifications;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdNotification(int $idNotification): ?Notification
  {
    $sql = "
            SELECT
                tbnotificationid,
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationdate,
                tbnotificationisread,
                tbnotificationisactive

            FROM tbnotification

            WHERE tbnotificationid = :idNotification
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idNotification' => $idNotification
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToNotification($row);
  }


  // =========================================================
  // OBTENER POR ROLE (bandeja de un usuario, solo activas)
  // =========================================================
  public function getByRoleNotification(int $idRol): array
  {
    $sql = "
            SELECT
                tbnotificationid,
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationdate,
                tbnotificationisread,
                tbnotificationisactive

            FROM tbnotification

            WHERE tbnotificationroleid = :idRol
              AND tbnotificationisactive = true

            ORDER BY tbnotificationdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRol' => $idRol
    ]);

    $notifications = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $notifications[] = $this->mapRowToNotification($row);
    }

    return $notifications;
  }


  // =========================================================
  // CONTAR NO LEÍDAS DE UN ROLE
  // =========================================================
  public function countUnreadByRoleNotification(int $idRol): int
  {
    $sql = "
            SELECT COUNT(*) AS total

            FROM tbnotification

            WHERE tbnotificationroleid = :idRol
              AND tbnotificationisread = false
              AND tbnotificationisactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRol' => $idRol
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) ($row['total'] ?? 0);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateNotification(Notification $notification): bool
  {
    try {
      $sql = "
                UPDATE tbnotification
                SET
                    tbnotificationroleid = :idRol,
                    tbnotificationmessage = :message,
                    tbnotificationdate = :date,
                    tbnotificationisread = :isRead,
                    tbnotificationisactive = :isActive
                WHERE tbnotificationid = :idNotification
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idRol' => $notification->getIdRol(),
        ':message' => $notification->getMessageNotification(),
        ':date' => $notification->getDateNotification(),
        ':isRead' => $notification->getIsRead(),
        ':isActive' => $notification->getIsActive(),
        ':idNotification' => $notification->getIdNotification()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MARCAR COMO LEÍDA
  // =========================================================
  public function markAsReadNotification(int $idNotification): bool
  {
    $sql = "
            UPDATE tbnotification
            SET tbnotificationisread = true
            WHERE tbnotificationid = :idNotification
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idNotification' => $idNotification
    ]);
  }


  // =========================================================
  // MARCAR TODAS COMO LEÍDAS (de un role)
  // =========================================================
  public function markAllAsReadByRoleNotification(int $idRol): bool
  {
    $sql = "
            UPDATE tbnotification
            SET tbnotificationisread = true
            WHERE tbnotificationroleid = :idRol
              AND tbnotificationisread = false
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idRol' => $idRol
    ]);
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateNotification(int $idNotification): bool
  {
    $sql = "
            UPDATE tbnotification
            SET tbnotificationisactive = false
            WHERE tbnotificationid = :idNotification
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idNotification' => $idNotification
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteNotification(int $idNotification): bool
  {
    try {
      $sql = "
                DELETE FROM tbnotification
                WHERE tbnotificationid = :idNotification
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idNotification' => $idNotification
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToNotification(array $row): Notification
  {
    return new Notification(
      (int) $row['tbnotificationid'],
      (int) $row['tbnotificationroleid'],
      $row['tbnotificationmessage'],
      $row['tbnotificationdate'],
      (bool) $row['tbnotificationisactive'],
      (bool) $row['tbnotificationisread']
    );
  }
}
