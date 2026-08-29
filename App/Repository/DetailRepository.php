<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Detail.php';

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

      $sqlDetail = "
                INSERT INTO tbdetail (
                    tbdetailserviceid,
                    tbdetailquantity,
                    tbdetailunitprice,
                    tbdetaildiscount,
                    tbdetailactive
                )
                VALUES (
                    :idLocalService,
                    :quantityDetail,
                    :unitPrice,
                    :discount,
                    :isActiveDetail
                )
            ";

      $stmtDetail = $this->connection->prepare($sqlDetail);

      $stmtDetail->execute([
        ':idLocalService' => $detail->getIdLocalService(),
        ':quantityDetail' => $detail->getQuantityDetail(),
        ':unitPrice'      => $detail->getUnitPrice(),
        ':discount'       => $detail->getDiscount(),
        ':isActiveDetail' => $this->toDb($detail->getIsActiveDetail())
      ]);

      $idDetail = (int) $this->connection->lastInsertId();

      $sqlJunction = "
                INSERT INTO tbbookingdetail (
                    tbbookingdetailbookingid,
                    tbbookingdetaildetailid,
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

      $this->connection->commit();

      return $idDetail;
    } catch (\Throwable $e) {

      $this->connection->rollBack();

      throw $e;
    }
  }


  // =========================================================
  // OBTENER POR BOOKING (detalles activos, via junction)
  // =========================================================
  public function findByBooking(int $idClientBooking): array
  {
    $sql = "
            SELECT
                d.tbdetailid AS tbbookingdetailid,
                b.tbbookingdetailbookingid AS tbbookingdetailbookingid,
                d.tbdetailserviceid AS tbbookingdetaildetailid,
                d.tbdetailquantity AS tbbookingdetailquantity,
                d.tbdetailunitprice AS tbbookingdetailunitprice,
                d.tbdetaildiscount AS tbbookingdetaildiscount,
                d.tbdetailactive AS tbbookingdetailactive

            FROM tbbookingdetail b

            INNER JOIN tbdetail d
                ON d.tbdetailid = b.tbbookingdetaildetailid

            WHERE b.tbbookingdetailbookingid = :idClientBooking
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
                ON d.tbdetailid = b.tbbookingdetaildetailid

            INNER JOIN tbservice s
                ON s.tbserviceid = d.tbdetailserviceid

            WHERE b.tbbookingdetailactive = true
              AND d.tbdetailactive = true

            GROUP BY d.tbdetailserviceid, s.tbservicename

            ORDER BY totalQuantity DESC

            LIMIT :limit
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
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
          idClientBooking: (int) $row['tbbookingdetailbookingid'],
          idLocalService: (int) $row['tbbookingdetaildetailid'],
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