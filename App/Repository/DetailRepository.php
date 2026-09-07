<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Detail.php';
require_once __DIR__ . '/../Model/Venue.php';

class DetailRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR (inserta tbdetail + junction tbbookingdetail)
  // Devuelve el id del detalle (tbdetailid).
  // =========================================================
  public function save(Detail $detail): int
  {
    $this->connection->beginTransaction();

    try {

      $idDetail = $this->insertRow($detail);

      $this->connection->commit();

      return $idDetail;
    } catch (\Throwable $e) {

      $this->connection->rollBack();

      throw $e;
    }
  }

  // =========================================================
  // INSERTAR LA RENTA DEL LOCAL como línea base de la reserva.
  // Sin transacción propia: el llamador (BookingService) la envuelve.
  // =========================================================
  public function addVenueLine(int $bookingPk, Venue $venue): int
  {
    $detail = new Detail(
      idDetail: 0,
      idClientBooking: $bookingPk,
      idLocalService: 0,
      idVenue: $venue->getIdVenue(),
      quantityDetail: 1,
      unitPrice: $venue->getPriceVenue(),
      discount: 0.0,
      isActiveDetail: true
    );

    return $this->insertRow($detail);
  }

  // =========================================================
  // NÚCLEO DE INSERCIÓN (tbdetail + junction). Sin transacción:
  // save() la gestiona; BookingService la usa dentro de la suya.
  // =========================================================
  private function insertRow(Detail $detail): int
  {
    $sqlDetail = "
                INSERT INTO tbdetail (
                    tbserviceid,
                    tbvenueid,
                    tbdetailquantity,
                    tbdetailunitprice,
                    tbdetaildiscount,
                    tbdetailactive
                )
                VALUES (
                    :idLocalService,
                    :idVenue,
                    :quantityDetail,
                    :unitPrice,
                    :discount,
                    :isActiveDetail
                )
            ";

    $stmtDetail = $this->connection->prepare($sqlDetail);

    $stmtDetail->execute([
      ':idLocalService' => $detail->getIdLocalService() > 0 ? $detail->getIdLocalService() : null,
      ':idVenue'        => $detail->getIdVenue() > 0 ? $detail->getIdVenue() : null,
      ':quantityDetail' => $detail->getQuantityDetail(),
      ':unitPrice'      => $detail->getUnitPrice(),
      ':discount'       => $detail->getDiscount(),
      ':isActiveDetail' => $this->toDb($detail->getIsActiveDetail())
    ]);

    $idDetail = (int) $this->connection->lastInsertId();

    $sqlJunction = "
                INSERT INTO tbbookingdetail (
                    tbbookingid,
                    tbdetailid,
                    tbbookingdetailactive
                )
                VALUES (
                    :idClientBooking,
                    :idDetail,
                    :isActiveDetail
                )
            ";

    $stmtJunction = $this->connection->prepare($sqlJunction);

    $stmtJunction->execute([
      ':idClientBooking' => $detail->getIdClientBooking(),
      ':idDetail'        => $idDetail,
      ':isActiveDetail'  => $this->toDb($detail->getIsActiveDetail())
    ]);

    return $idDetail;
  }


  // =========================================================
  // OBTENER POR BOOKING (detalles activos, via junction)
  // =========================================================
  public function findByBooking(int $idClientBooking): array
  {
    $sql = "
            SELECT
                d.tbdetailid AS tbbookingdetailid,
                b.tbbookingid AS tbbookingid,
                d.tbserviceid AS tbdetailid,
                d.tbvenueid AS tbbookingdetailvenueid,
                d.tbdetailquantity AS tbbookingdetailquantity,
                d.tbdetailunitprice AS tbbookingdetailunitprice,
                d.tbdetaildiscount AS tbbookingdetaildiscount,
                d.tbdetailactive AS tbbookingdetailactive

            FROM tbbookingdetail b

            INNER JOIN tbdetail d
                ON d.tbdetailid = b.tbdetailid

            WHERE b.tbbookingid = :idClientBooking
              AND b.tbbookingdetailactive = true
              AND d.tbdetailactive = true

            ORDER BY d.tbdetailid ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $idClientBooking
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // LÍNEA DE RENTA DEL LOCAL de una reserva (la que tiene
  // tbvenueid > 0). Devuelve null si no existe.
  // =========================================================
  public function findVenueLine(int $idClientBooking): ?Detail
  {
    $sql = "
            SELECT
                d.tbdetailid AS tbbookingdetailid,
                b.tbbookingid AS tbbookingid,
                d.tbserviceid AS tbdetailid,
                d.tbvenueid AS tbbookingdetailvenueid,
                d.tbdetailquantity AS tbbookingdetailquantity,
                d.tbdetailunitprice AS tbbookingdetailunitprice,
                d.tbdetaildiscount AS tbbookingdetaildiscount,
                d.tbdetailactive AS tbbookingdetailactive

            FROM tbbookingdetail b

            INNER JOIN tbdetail d
                ON d.tbdetailid = b.tbdetailid

            WHERE b.tbbookingid = :idClientBooking
              AND b.tbbookingdetailactive = true
              AND d.tbdetailactive = true
              AND d.tbvenueid IS NOT NULL
              AND d.tbvenueid > 0

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $idClientBooking
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // ACTUALIZAR LÍNEA DE RENTA (al cambiar de local)
  // =========================================================
  public function updateVenueLine(int $detailId, int $venueId, float $unitPrice): bool
  {
    $sql = "
            UPDATE tbdetail
            SET tbvenueid = :venueId,
                tbdetailunitprice = :unitPrice
            WHERE tbdetailid = :detailId
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':venueId'   => $venueId,
      ':unitPrice' => $unitPrice,
      ':detailId'  => $detailId
    ]);
  }


  // =========================================================
  // SERVICIOS MÁS SOLICITADOS (para estadísticas del Admin)
  // =========================================================
  public function topRequestedServices(int $limit = 5): array
  {
    $sql = "
            SELECT
                s.tbservicename AS name,
                SUM(d.tbdetailquantity) AS totalQuantity

            FROM tbbookingdetail b

            INNER JOIN tbdetail d
                ON d.tbdetailid = b.tbdetailid

            INNER JOIN tbservice s
                ON s.tbserviceid = d.tbserviceid

            WHERE b.tbbookingdetailactive = true
              AND d.tbdetailactive = true

            GROUP BY d.tbserviceid, s.tbservicename

            ORDER BY totalQuantity DESC

            LIMIT :limit
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // SERVICIOS MÁS SOLICITADOS DE UN OWNER (todos sus locales,
  // en un mes dado) -- para el dashboard del propietario
  // =========================================================
  public function topServicesByOwner(int $idOwner, string $yearMonth, int $limit = 3): array
  {
    $sql = "
            SELECT
                s.tbservicename AS name,
                SUM(d.tbdetailquantity) AS totalQuantity

            FROM tbbookingdetail bd

            INNER JOIN tbdetail d
                ON d.tbdetailid = bd.tbdetailid

            INNER JOIN tbservice s
                ON s.tbserviceid = d.tbserviceid

            INNER JOIN tbbooking bk
                ON bk.tbbookingid = bd.tbbookingid

            INNER JOIN tbvenue v
                ON v.tbvenueid = bk.tbvenueid

            WHERE v.tbownerid = :idOwner
              AND bk.tbbookingdate LIKE :yearMonth
              AND bd.tbbookingdetailactive = true
              AND d.tbdetailactive = true

            GROUP BY d.tbserviceid, s.tbservicename

            ORDER BY totalQuantity DESC

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
  // MAPEO FILA -> OBJETO
  // =========================================================
private function mapRow(array $row): Detail
    {
        return new Detail(
          idDetail: (int) $row['tbbookingdetailid'],
          idClientBooking: (int) $row['tbbookingid'],
          idLocalService: (int) $row['tbdetailid'],
          idVenue: (int) ($row['tbbookingdetailvenueid'] ?? 0),
          quantityDetail: (int) $row['tbbookingdetailquantity'],
          unitPrice: (float) $row['tbbookingdetailunitprice'],
          discount: (float) $row['tbbookingdetaildiscount'],
          isActiveDetail: $this->toBool($row['tbbookingdetailactive'])
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