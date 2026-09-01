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
  // RESERVAS PENDIENTES DE UN OWNER (todos sus locales)
  // =========================================================
  public function findPendingByOwner(int $idOwner): array
  {
    $sql = "
            SELECT
                b.tbbookingid,
                b.tbbookingclientid,
                b.tbbookinglocalid,
                b.tbbookingdate,
                b.tbbookingstate,
                b.tbbookingactive

            FROM tbbooking b

            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid

            WHERE v.tbvenueownerid = :idOwner
              AND b.tbbookingstate = 'pendiente'

            ORDER BY b.tbbookingdate ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // PRÓXIMA RESERVA CONFIRMADA DE UN OWNER (fecha más cercana >= hoy)
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
              AND b.tbbookingstate = 'confirmado'

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
  // LOCALES MÁS SOLICITADOS DE UN OWNER (en un mes dado)
  // -- para el dashboard del propietario
  // =========================================================
  public function topVenuesByOwner(int $idOwner, string $yearMonth, int $limit = 1): array
  {
    $sql = "
            SELECT
                v.tbvenuename AS name,
                COUNT(*) AS bookingCount

            FROM tbbooking b

            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid

            WHERE v.tbvenueownerid = :idOwner
              AND b.tbbookingdate LIKE :yearMonth

            GROUP BY b.tbbookinglocalid, v.tbvenuename

            ORDER BY bookingCount DESC

            LIMIT :limit
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->bindValue(':idOwner',   $idOwner,   PDO::PARAM_INT);
    $stmt->bindValue(':yearMonth', $yearMonth . '%');
    $stmt->bindValue(':limit',     $limit,     PDO::PARAM_INT);

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
  // REPROGRAMAR (cambiar la fecha de la reserva)
  // =========================================================
  public function reschedule(int $idBooking, string $newDate): bool
  {
    $sql = "
            UPDATE tbbooking
            SET tbbookingdate = :newDate
            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':newDate'   => $newDate,
      ':idBooking' => $idBooking
    ]);
  }

  // =========================================================
  // CAMBIAR LOCAL (asignar la reserva a otro venue)
  // =========================================================
  public function changeVenue(int $idBooking, int $newVenueId): bool
  {
    $sql = "
            UPDATE tbbooking
            SET tbbookinglocalid = :newVenueId
            WHERE tbbookingid = :idBooking
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':newVenueId' => $newVenueId,
      ':idBooking'  => $idBooking
    ]);
  }

  // =========================================================
  // FECHAS OCUPADAS DE UN LOCAL (reservas activas)
  // =========================================================
  public function bookedDatesByVenue(int $idLocal): array
  {
    $sql = "
            SELECT tbbookingdate
            FROM tbbooking
            WHERE tbbookinglocalid = :idLocal
              AND tbbookingstate IN ('pendiente', 'confirmado')
              AND tbbookingactive = true
            ORDER BY tbbookingdate
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idLocal' => $idLocal]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  // =========================================================
  // OBTENER POR MES CON NOMBRES (panel del Admin)
  // Devuelve filas asociativas con nombre del cliente y del local.
  // =========================================================
  public function findByMonthWithDetails(string $yearMonth): array
  {
    $sql = "
            SELECT
                b.tbbookingid,
                b.tbbookingclientid,
                c.tbclientname AS clientName,
                b.tbbookinglocalid,
                v.tbvenuename AS venueName,
                b.tbbookingdate,
                b.tbbookingeventtype,
                b.tbbookingstate,
                b.tbbookingactive
            FROM tbbooking b
            LEFT JOIN tbclient c ON c.tbclientid = b.tbbookingclientid
            LEFT JOIN tbvenue v ON v.tbvenueid = b.tbbookinglocalid
            WHERE b.tbbookingdate LIKE :yearMonth
            ORDER BY b.tbbookingdate DESC, b.tbbookingid DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':yearMonth' => $yearMonth . '%']);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  public function hasActiveBookingOnDate(int $idLocal, string $bookingDate, int $excludeBookingId = 0): bool
  {
    $sql = "
            SELECT COUNT(*)
            FROM tbbooking
            WHERE tbbookinglocalid = :idLocal
              AND tbbookingdate = :bookingDate
              AND tbbookingstate IN ('pendiente', 'confirmado')
        ";

    $params = [
      ':idLocal'     => $idLocal,
      ':bookingDate' => $bookingDate
    ];

    if ($excludeBookingId > 0) {
      $sql .= " AND tbbookingid <> :excludeId";
      $params[':excludeId'] = $excludeBookingId;
    }

    $stmt = $this->connection->prepare($sql);

    $stmt->execute($params);

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
  // RESERVAS DEL MES AGRUPADAS POR ESTADO
  // Devuelve ['pendiente' => n, 'confirmado' => n, 'cancelado' => n, 'rechazado' => n]
  // =========================================================
  public function countByState(string $yearMonth): array
  {
    $sql = "
            SELECT
                tbbookingstate AS state,
                COUNT(*) AS total
            FROM tbbooking
            WHERE tbbookingdate LIKE :yearMonth
            GROUP BY tbbookingstate
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':yearMonth' => $yearMonth . '%']);

    $counts = ['pendiente' => 0, 'confirmado' => 0, 'cancelado' => 0, 'rechazado' => 0];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $state = $row['state'];
      if (isset($counts[$state])) {
        $counts[$state] = (int) $row['total'];
      }
    }

    return $counts;
  }

  // =========================================================
  // TASA DE OCUPACIÓN POR LOCAL EN UN MES
  // Por cada local: total de reservas y confirmadas del mes.
  // =========================================================
  public function occupancyByVenue(string $yearMonth): array
  {
    $sql = "
            SELECT
                b.tbbookinglocalid AS idLocal,
                v.tbvenuename AS name,
                COUNT(*) AS total,
                SUM(CASE WHEN b.tbbookingstate = 'confirmado' THEN 1 ELSE 0 END) AS confirmed
            FROM tbbooking b
            INNER JOIN tbvenue v
                ON v.tbvenueid = b.tbbookinglocalid
            WHERE b.tbbookingdate LIKE :yearMonth
            GROUP BY b.tbbookinglocalid, v.tbvenuename
            ORDER BY name ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':yearMonth' => $yearMonth . '%']);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
