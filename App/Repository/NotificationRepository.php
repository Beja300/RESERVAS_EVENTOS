<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Notification.php';

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
  public function save(Notification $notification): int
  {
    $sql = "
            INSERT INTO tbnotification (
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationlink,
                tbnotificationdate,
                tbnotificationread,
                tbnotificationactive
            )
            VALUES (
                :idRol,
                :message,
                :link,
                :date,
                :isRead,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRol'    => $notification->getIdRol(),
      ':message'  => $notification->getMessageNotification(),
      ':link'     => $notification->getLink(),
      ':date'     => $notification->getDateNotification(),
      ':isRead'   => $this->toDb($notification->getIsRead()),
      ':isActive' => $this->toDb($notification->getIsActive())
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER POR ROLE (bandeja de un usuario, solo activas)
  // =========================================================
  public function findByRole(int $idRol): array
  {
    $sql = "
            SELECT
                tbnotificationid,
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationlink,
                tbnotificationdate,
                tbnotificationread,
                tbnotificationactive

            FROM tbnotification

            WHERE tbnotificationroleid = :idRol
              AND tbnotificationactive = true

            ORDER BY tbnotificationdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRol' => $idRol
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // OBTENER POR ID (solo si está activa)
  // =========================================================
  public function findById(int $idNotification): ?Notification
  {
    $sql = "
            SELECT
                tbnotificationid,
                tbnotificationroleid,
                tbnotificationmessage,
                tbnotificationlink,
                tbnotificationdate,
                tbnotificationread,
                tbnotificationactive

            FROM tbnotification

            WHERE tbnotificationid = :idNotification
              AND tbnotificationactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idNotification' => $idNotification
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // MARCAR COMO LEÍDA
  // =========================================================
  public function markRead(int $idNotification): bool
  {
    $sql = "
            UPDATE tbnotification
            SET tbnotificationread = true
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
  public function markAllRead(int $idRol): bool
  {
    $sql = "
            UPDATE tbnotification
            SET tbnotificationread = true
            WHERE tbnotificationroleid = :idRol
              AND tbnotificationactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idRol' => $idRol
    ]);
  }


  // =========================================================
  // CONTAR NO LEÍDAS DE UN ROLE
  // =========================================================
  public function countUnread(int $idRol): int
  {
    $sql = "
            SELECT COUNT(*)
            FROM tbnotification
            WHERE tbnotificationroleid = :idRol
              AND tbnotificationread = false
              AND tbnotificationactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRol' => $idRol
    ]);

    return (int) $stmt->fetchColumn();
  }


  // =========================================================
  // OBTENER IDS DE ROLES ADMIN ACTIVOS
  // =========================================================
  public function findAdminRoleIds(): array
  {
    $sql = "
            SELECT tbroleadminrolid
            FROM tbroleadmin
            WHERE tbroleadminactive = true
        ";

    $stmt = $this->connection->query($sql);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
  }


  // =========================================================
  // OBTENER ROL ID DE UN PROPIETARIO (tbroleowner)
  // =========================================================
  public function findRoleIdByOwner(int $idOwner): ?int
  {
    $sql = "
            SELECT tbroleownerrolid
            FROM tbroleowner
            WHERE tbroleownerownerid = :idOwner
              AND tbroleowneractive = true
            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwner' => $idOwner]);

    $value = $stmt->fetchColumn();

    return $value !== false ? (int) $value : null;
  }

  // =========================================================
  // OBTENER ROL ID DE UN CLIENTE (tbroleclient)
  // =========================================================
  public function findRoleIdByClient(int $idClient): ?int
  {
    $sql = "
            SELECT tbroleclientrolid
            FROM tbroleclient
            WHERE tbroleclientclientid = :idClient
              AND tbroleclientactive = true
            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idClient' => $idClient]);

    $value = $stmt->fetchColumn();

    return $value !== false ? (int) $value : null;
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Notification
  {
    return new Notification(
      idNotification: (int) $row['tbnotificationid'],
      idRol: (int) $row['tbnotificationroleid'],
      messageNotification: $row['tbnotificationmessage'],
      dateNotification: $row['tbnotificationdate'],
      isActive: $this->toBool($row['tbnotificationactive']),
      isRead: $this->toBool($row['tbnotificationread']),
      link: $row['tbnotificationlink'] ?? null
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }

  private function toDb(bool $value): int
  {
    return $value ? 1 : 0;
  }
}
