<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Booking.php';

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
  public function save(Booking $booking): int
  {
    $sql = "
            INSERT INTO tbbooking (
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingactive
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
      ':idClient'         => $booking->getIdClient(),
      ':idLocal'          => $booking->getIdLocal(),
      ':bookingDate'      => $booking->getBookingDate(),
      ':bookingState'     => $booking->getBookingState(),
      ':isBookingActive'  => $this->toDb($booking->getIsBookingActive())
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idBooking): ?Booking
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingactive

            FROM tbbooking

            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking' => $idBooking
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // BUSCAR POR CLIENTE
  // =========================================================
  public function findByClient(int $idClient): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingactive

            FROM tbbooking

            WHERE tbbookingclientid = :idClient

            ORDER BY tbbookingdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClient' => $idClient
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // BUSCAR POR LOCAL (VENUE)
  // =========================================================
  public function findByVenue(int $idLocal): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingactive

            FROM tbbooking

            WHERE tbbookinglocalid = :idLocal

            ORDER BY tbbookingdate DESC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal' => $idLocal
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // BUSCAR POR MES ('YYYY-MM' -- para estadísticas del Admin)
  // =========================================================
  public function findByMonth(string $yearMonth): array
  {
    $sql = "
            SELECT
                tbbookingid,
                tbbookingclientid,
                tbbookinglocalid,
                tbbookingdate,
                tbbookingstate,
                tbbookingactive

            FROM tbbooking

            WHERE tbbookingdate LIKE :yearMonth
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':yearMonth' => $yearMonth . '%'
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // RESERVAS DEL MES DE UN OWNER (todos sus locales)
  // =========================================================
  public function countByOwnerForMonth(int $idOwner, string $yearMonth): int
  {
    $sql = "
            SELECT COUNT(*)

            FROM tbbooking b

            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid

            WHERE v.tbvenueownerid = :idOwner
              AND b.tbbookingdate LIKE :yearMonth
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner'   => $idOwner,
      ':yearMonth' => $yearMonth . '%'
    ]);

    return (int) $stmt->fetchColumn();
  }

  // =========================================================
  // PRÓXIMA RESERVA DE UN OWNER (fecha más cercana >= hoy)
  // =========================================================
  public function nextBookingByOwner(int $idOwner, string $today): ?array
  {
    $sql = "
            SELECT
                b.tbbookingdate,
                v.tbvenuename

            FROM tbbooking b

            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid

            WHERE v.tbvenueownerid = :idOwner
              AND b.tbbookingdate >= :today
              AND b.tbbookingstate IN ('pendiente', 'confirmado')
              AND b.tbbookingactive = true

            ORDER BY b.tbbookingdate ASC

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner,
      ':today'   => $today
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $row : null;
  }


  // =========================================================
  // LOCALES MÁS ACTIVOS (venues con más reservas)
  // =========================================================
  public function topActiveVenues(int $limit = 5): array
  {
    $sql = "
            SELECT
                v.tbvenuename AS name,
                COUNT(*) AS bookingCount

            FROM tbbooking b

            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid

            GROUP BY b.tbbookinglocalid, v.tbvenuename

            ORDER BY bookingCount DESC

            LIMIT :limit
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }


  // =========================================================
  // CAMBIAR ESTADO
  // =========================================================
  public function updateStatus(int $idBooking, string $bookingState): bool
  {
    $sql = "
            UPDATE tbbooking
            SET tbbookingstate = :bookingState
            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':bookingState' => $bookingState,
      ':idBooking'    => $idBooking
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function delete(int $idBooking): bool
  {
    $sql = "
            DELETE FROM tbbooking
            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idBooking' => $idBooking
    ]);
  }


  // =========================================================
  // VERIFICAR SI YA HAY UNA RESERVA ACTIVA EN ESA FECHA
  // =========================================================
  public function hasActiveBookingOnDate(int $idLocal, string $bookingDate): bool
  {
    $sql = "
            SELECT COUNT(*)
            FROM tbbooking
            WHERE tbbookinglocalid = :idLocal
              AND tbbookingdate = :bookingDate
              AND tbbookingstate IN ('pendiente', 'confirmado')
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal'     => $idLocal,
      ':bookingDate' => $bookingDate
    ]);

    return ((int) $stmt->fetchColumn()) > 0;
  }

  // =========================================================
  // ¿EL DUEÑO TIENE RESERVAS FUTURAS (hoy o después) EN SUS LOCALES?
  // Se usa para saber si puede desactivar su perfil: solo si NO hay
  // reservas pendientes/confirmadas cuya fecha sea hoy o futura.
  // =========================================================
  public function hasUpcomingActiveByOwner(int $idOwner): bool
  {
    $sql = "
            SELECT COUNT(*)
            FROM tbbooking b
            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid
            WHERE v.tbvenueownerid = :idOwner
              AND b.tbbookingdate >= :today
              AND b.tbbookingstate IN ('pendiente', 'confirmado')
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner,
      ':today'   => date('Y-m-d')
    ]);

    return ((int) $stmt->fetchColumn()) > 0;
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Booking
  {
    return new Booking(
      idBooking: (int) $row['tbbookingid'],
      idClient: (int) $row['tbbookingclientid'],
      idLocal: (int) $row['tbbookinglocalid'],
      bookingDate: $row['tbbookingdate'],
      bookingState: $row['tbbookingstate'],
      isBookingActive: $this->toBool($row['tbbookingactive'])
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
