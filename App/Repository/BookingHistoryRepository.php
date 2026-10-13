<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/BookingHistory.php';

class BookingHistoryRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(BookingHistory $history): int
  {
    $sql = "
            INSERT INTO tbbookinghistory (
                tbbookingid,
                tbroleid,
                tbbookinghistoryaction,
                tbbookinghistorydetail,
                tbbookinghistoryactive
            )
            VALUES (
                :idBooking,
                :roleId,
                :action,
                :detail,
                true
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking' => $history->getIdBooking(),
      ':roleId'    => $history->getRoleId(),
      ':action'    => $history->getAction(),
      ':detail'    => $history->getDetail()
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // HISTORIAL DE UNA RESERVA (con nombre del responsable)
  // =========================================================
  public function findByBooking(int $idBooking): array
  {
    $sql = "
            SELECT
                h.tbbookinghistoryid,
                h.tbbookingid,
                h.tbroleid,
                r.tbrolename AS responsibleName,
                h.tbbookinghistoryaction,
                h.tbbookinghistorydetail,
                h.tbbookinghistorydate
            FROM tbbookinghistory h
            LEFT JOIN tbrole r ON r.tbroleid = h.tbroleid
            WHERE h.tbbookingid = :idBooking
              AND h.tbbookinghistoryactive = true
            ORDER BY h.tbbookinghistorydate DESC, h.tbbookinghistoryid DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idBooking' => $idBooking]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // HISTORIAL GLOBAL (todas las reservas, con responsable y
  // nombres de cliente/local) para el panel del Admin.
  // =========================================================
  public function findAllWithDetails(): array
  {
    $sql = "
            SELECT
                h.tbbookinghistoryid,
                h.tbbookingid,
                b.tbbookingdate,
                c.tbclientname AS clientName,
                v.tbvenuename AS venueName,
                h.tbroleid,
                r.tbrolename AS responsibleName,
                h.tbbookinghistoryaction,
                h.tbbookinghistorydetail,
                h.tbbookinghistorydate
            FROM tbbookinghistory h
            LEFT JOIN tbbooking b ON b.tbbookingid = h.tbbookingid
            LEFT JOIN tbclient c ON c.tbclientid = b.tbclientid
            LEFT JOIN tbvenue v ON v.tbvenueid = b.tbvenueid
            LEFT JOIN tbrole r ON r.tbroleid = h.tbroleid
            WHERE h.tbbookinghistoryactive = true
            ORDER BY h.tbbookinghistorydate DESC, h.tbbookinghistoryid DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}