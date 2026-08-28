<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/BookingTicket.php';

class BookingTicketRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(BookingTicket $ticket): int
  {
    $sql = "
      INSERT INTO tbbookingticket (
        tbbookingticketbookingid,
        tbbookingticketfile,
        tbbookingtickettype,
        tbbookingticketstate
      )
      VALUES (
        :idBooking,
        :file,
        :type,
        :state
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking' => $ticket->getIdBooking(),
      ':file'      => $ticket->getFile(),
      ':type'      => $ticket->getType(),
      ':state'     => $ticket->getState(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idTicket): ?BookingTicket
  {
    $sql = "
      SELECT
        tbbookingticketid,
        tbbookingticketbookingid,
        tbbookingticketfile,
        tbbookingtickettype,
        tbbookingticketstate
      FROM tbbookingticket
      WHERE tbbookingticketid = :idTicket
        AND tbbookingticketactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idTicket' => $idTicket]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // BUSCAR POR RESERVA
  // =========================================================
  public function findByBooking(int $idBooking): ?BookingTicket
  {
    $sql = "
      SELECT
        tbbookingticketid,
        tbbookingticketbookingid,
        tbbookingticketfile,
        tbbookingtickettype,
        tbbookingticketstate
      FROM tbbookingticket
      WHERE tbbookingticketbookingid = :idBooking
        AND tbbookingticketactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idBooking' => $idBooking]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // CAMBIAR ESTADO DEL COMPROBANTE
  // =========================================================
  public function updateState(int $idTicket, string $state): bool
  {
    $sql = "
      UPDATE tbbookingticket
      SET tbbookingticketstate = :state
      WHERE tbbookingticketid = :idTicket
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':state'    => $state,
      ':idTicket' => $idTicket,
    ]);
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): BookingTicket
  {
    return new BookingTicket(
      idTicket: (int) $row['tbbookingticketid'],
      idBooking: (int) $row['tbbookingticketbookingid'],
      file: $row['tbbookingticketfile'],
      type: $row['tbbookingtickettype'],
      state: $row['tbbookingticketstate']
    );
  }
}
