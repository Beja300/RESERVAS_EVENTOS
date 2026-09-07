<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/BookingRefund.php';

class BookingRefundRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(BookingRefund $refund): int
  {
    $sql = "
            INSERT INTO tbbookingrefund (
                tbbookingid,
                tbroleid,
                tbbookingrefunddetail,
                tbbookingrefundstate,
                tbbookingrefundactive
            )
            VALUES (
                :idBooking,
                :clientRoleId,
                :detail,
                :state,
                true
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking'    => $refund->getIdBooking(),
      ':clientRoleId' => $refund->getClientRoleId(),
      ':detail'       => $refund->getDetail(),
      ':state'        => $refund->getState()
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function findById(int $idRefund): ?BookingRefund
  {
    $sql = "
            SELECT
                tbbookingrefundid,
                tbbookingid,
                tbroleid,
                tbbookingrefunddetail,
                tbbookingrefundstate,
                tbbookingrefunddate
            FROM tbbookingrefund
            WHERE tbbookingrefundid = :idRefund
              AND tbbookingrefundactive = true
            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idRefund' => $idRefund]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // SOLICITUD DE UNA RESERVA (la más reciente)
  // =========================================================
  public function findByBooking(int $idBooking): ?BookingRefund
  {
    $sql = "
            SELECT
                tbbookingrefundid,
                tbbookingid,
                tbroleid,
                tbbookingrefunddetail,
                tbbookingrefundstate,
                tbbookingrefunddate
            FROM tbbookingrefund
            WHERE tbbookingid = :idBooking
              AND tbbookingrefundactive = true
            ORDER BY tbbookingrefundid DESC
            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idBooking' => $idBooking]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // SOLICITUDES PENDIENTES (para el panel del Admin)
  // =========================================================
  public function findPending(): array
  {
    $sql = "
            SELECT
                r.tbbookingrefundid,
                r.tbbookingid,
                b.tbbookingdate,
                c.tbclientname AS clientName,
                v.tbvenuename AS venueName,
                r.tbbookingrefunddetail,
                r.tbbookingrefundstate,
                r.tbbookingrefunddate
            FROM tbbookingrefund r
            LEFT JOIN tbbooking b ON b.tbbookingid = r.tbbookingid
            LEFT JOIN tbclient c ON c.tbclientid = b.tbclientid
            LEFT JOIN tbvenue v ON v.tbvenueid = b.tbvenueid
            WHERE r.tbbookingrefundstate = 'pendiente'
              AND r.tbbookingrefundactive = true
            ORDER BY r.tbbookingrefunddate DESC, r.tbbookingrefundid DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // CAMBIAR ESTADO DE LA SOLICITUD
  // =========================================================
  public function updateState(int $idRefund, string $state): bool
  {
    $sql = "
            UPDATE tbbookingrefund
            SET tbbookingrefundstate = :state
            WHERE tbbookingrefundid = :idRefund
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':state'    => $state,
      ':idRefund' => $idRefund
    ]);
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): BookingRefund
  {
    return new BookingRefund(
      id: (int) $row['tbbookingrefundid'],
      idBooking: (int) $row['tbbookingid'],
      clientRoleId: (int) $row['tbroleid'],
      detail: $row['tbbookingrefunddetail'],
      state: $row['tbbookingrefundstate'],
      date: $row['tbbookingrefunddate'] ?? null
    );
  }
}