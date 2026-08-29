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
  // GUARDAR
  // =========================================================
  public function save(Detail $detail): int
  {
    $sql = "
            INSERT INTO tbbookingdetail (
                tbbookingdetailbookingid,
                tbbookingdetaildetailid,
                tbbookingdetailquantity,
                tbbookingdetailunitprice,
                tbbookingdetaildiscount,
                tbbookingdetailactive
            )
            VALUES (
                :idClientBooking,
                :idLocalService,
                :quantityDetail,
                :unitPrice,
                :discount,
                :isActiveDetail
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $detail->getIdClientBooking(),
      ':idLocalService'  => $detail->getIdLocalService(),
      ':quantityDetail'  => $detail->getQuantityDetail(),
      ':unitPrice'       => $detail->getUnitPrice(),
      ':discount'        => $detail->getDiscount(),
      ':isActiveDetail'  => $detail->getIsActiveDetail()
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER POR BOOKING (solo detalles activos)
  // =========================================================
  public function findByBooking(int $idClientBooking): array
  {
    $sql = "
            SELECT
                tbbookingdetailid,
                tbbookingdetailbookingid,
                tbbookingdetaildetailid,
                tbbookingdetailquantity,
                tbbookingdetailunitprice,
                tbbookingdetaildiscount,
                tbbookingdetailactive

            FROM tbbookingdetail

            WHERE tbbookingdetailbookingid = :idClientBooking
              AND tbbookingdetailactive = true

            ORDER BY tbbookingdetailid ASC
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
                SUM(d.tbbookingdetailquantity) AS totalQuantity

            FROM tbbookingdetail d

            INNER JOIN tbservice s
                ON s.tbserviceid = d.tbbookingdetaildetailid

            GROUP BY d.tbbookingdetaildetailid, s.tbservicename

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
}
