<?php

require_once __DIR__ . '/../models/Booking.php';

class BookingRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveBooking(Booking $booking): bool
  {
    try {
      $sql = "
                INSERT INTO tbbooking (
                    tbbookingclientid,
                    tbbookinglocalid,
                    tbbookingdate,
                    tbbookingstate,
                    tbbookingisactive
                )
                VALUES (
                    :idClient,
                    :idLocal,
                    :bookingDate,
                    :bookingState,
                    :isBookingActive
                )
            ";

      $stmt = $this->connection->prepare($sql);

      $stmt->execute([
        ':idClient' => $booking->getIdClient(),
        ':idLocal' => $booking->getIdLocal(),
        ':bookingDate' => $booking->getBookingDate(),
        ':bookingState' => $booking->getBookingState(),
        ':isBookingActive' => $booking->getIsBookingActive()
      ]);

      $booking->setIdBooking((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllBooking(): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingisactive

            FROM tbbooking

            ORDER BY tbbookingid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $bookings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $bookings[] = $this->mapRowToBooking($row);
    }

    return $bookings;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdBooking(int $idBooking): ?Booking
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingisactive

            FROM tbbooking

            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking' => $idBooking
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToBooking($row);
  }


  // =========================================================
  // OBTENER POR CLIENTE
  // =========================================================
  public function getByClient(int $idClient): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingisactive

            FROM tbbooking

            WHERE tbbookingclientid = :idClient

            ORDER BY tbbookingdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClient' => $idClient
    ]);

    $bookings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $bookings[] = $this->mapRowToBooking($row);
    }

    return $bookings;
  }


  // =========================================================
  // OBTENER POR LOCAL
  // =========================================================
  public function getByLocalBooking(int $idLocal): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingisactive

            FROM tbbooking

            WHERE tbbookinglocalid = :idLocal

            ORDER BY tbbookingdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal' => $idLocal
    ]);

    $bookings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $bookings[] = $this->mapRowToBooking($row);
    }

    return $bookings;
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateBooking(Booking $booking): bool
  {
    try {
      $sql = "
                UPDATE tbbooking
                SET
                    tbbookingclientid = :idClient,
                    tbbookinglocalid = :idLocal,
                    tbbookingdate = :bookingDate,
                    tbbookingstate = :bookingState,
                    tbbookingisactive = :isBookingActive
                WHERE tbbookingid = :idBooking
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idClient' => $booking->getIdClient(),
        ':idLocal' => $booking->getIdLocal(),
        ':bookingDate' => $booking->getBookingDate(),
        ':bookingState' => $booking->getBookingState(),
        ':isBookingActive' => $booking->getIsBookingActive(),
        ':idBooking' => $booking->getIdBooking()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // CAMBIAR ESTADO (ej: pendiente, confirmada, cancelada)
  // =========================================================
  public function updateStateBooking(int $idBooking, string $bookingState): bool
  {
    try {
      $sql = "
                UPDATE tbbooking
                SET tbbookingstate = :bookingState
                WHERE tbbookingid = :idBooking
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':bookingState' => $bookingState,
        ':idBooking' => $idBooking
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateBooking(int $idBooking): bool
  {
    $sql = "
            UPDATE tbbooking
            SET tbbookingisactive = false
            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idBooking' => $idBooking
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteBooking(int $idBooking): bool
  {
    try {
      $sql = "
                DELETE FROM tbbooking
                WHERE tbbookingid = :idBooking
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idBooking' => $idBooking
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToBooking(array $row): Booking
  {
    return new Booking(
      (int) $row['tbbookingid'],
      (int) $row['tbbookingclientid'],
      (int) $row['tbbookinglocalid'],
      $row['tbbookingdate'],
      $row['tbbookingstate'],
      (bool) $row['tbbookingisactive']
    );
  }
}
