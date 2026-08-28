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
            INSERT INTO tbdetail (
                tbdetailbookingid,
                tbdetaillocalserviceid,
                tbdetailquantity,
                tbdetailunitprice,
                tbdetaildiscount,
                tbdetailactive
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
                tbdetailid,
                tbdetailbookingid,
                tbdetaillocalserviceid,
                tbdetailquantity,
                tbdetailunitprice,
                tbdetaildiscount,
                tbdetailactive

            FROM tbdetail

            WHERE tbdetailbookingid = :idClientBooking
              AND tbdetailactive = true

            ORDER BY tbdetailid ASC
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

            FROM tbdetail d

            INNER JOIN tbservice s
                ON s.tbserviceid = d.tbdetaillocalserviceid

            GROUP BY d.tbdetaillocalserviceid, s.tbservicename

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
      idDetail: (int) $row['tbdetailid'],
      idClientBooking: (int) $row['tbdetailbookingid'],
      idLocalService: (int) $row['tbdetaillocalserviceid'],
      quantityDetail: (int) $row['tbdetailquantity'],
      unitPrice: (float) $row['tbdetailunitprice'],
      discount: (float) $row['tbdetaildiscount'],
      isActiveDetail: $this->toBool($row['tbdetailactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
