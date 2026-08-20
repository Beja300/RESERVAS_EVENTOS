<?php

require_once __DIR__ . '/../models/Detail.php';

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
  public function saveDetail(Detail $detail): bool
  {
    try {
      $sql = "
                INSERT INTO tbdetail (
                    tbdetailbookingid,
                    tbdetaillocalserviceid,
                    tbdetailquantity,
                    tbdetailunitprice,
                    tbdetaildiscount,
                    tbdetailisactive
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
        ':idLocalService' => $detail->getIdLocalService(),
        ':quantityDetail' => $detail->getQuantityDetail(),
        ':unitPrice' => $detail->getUnitPrice(),
        ':discount' => $detail->getDiscount(),
        ':isActiveDetail' => $detail->getIsActiveDetail()
      ]);

      $detail->setIdDetail((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllDetail(): array
  {
    $sql = "
            SELECT
                tbdetailid,
                tbdetailbookingid,
                tbdetaillocalserviceid,
                tbdetailquantity,
                tbdetailunitprice,
                tbdetaildiscount,
                tbdetailisactive

            FROM tbdetail

            ORDER BY tbdetailid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $details = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $details[] = $this->mapRowToDetail($row);
    }

    return $details;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdDetail(int $idDetail): ?Detail
  {
    $sql = "
            SELECT
                tbdetailid,
                tbdetailbookingid,
                tbdetaillocalserviceid,
                tbdetailquantity,
                tbdetailunitprice,
                tbdetaildiscount,
                tbdetailisactive

            FROM tbdetail

            WHERE tbdetailid = :idDetail
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idDetail' => $idDetail
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToDetail($row);
  }


  // =========================================================
  // OBTENER POR BOOKING (todas las líneas del "carrito" de una reserva)
  // =========================================================
  public function getByBookingDetail(int $idClientBooking): array
  {
    $sql = "
            SELECT
                tbdetailid,
                tbdetailbookingid,
                tbdetaillocalserviceid,
                tbdetailquantity,
                tbdetailunitprice,
                tbdetaildiscount,
                tbdetailisactive

            FROM tbdetail

            WHERE tbdetailbookingid = :idClientBooking

            ORDER BY tbdetailid ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $idClientBooking
    ]);

    $details = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $details[] = $this->mapRowToDetail($row);
    }

    return $details;
  }


  // =========================================================
  // TOTAL DE UN BOOKING (suma de (cantidad * precio) - descuento)
  // =========================================================
  public function getTotalByBookingDetail(int $idClientBooking): float
  {
    $sql = "
            SELECT
                COALESCE(SUM((tbdetailquantity * tbdetailunitprice) - tbdetaildiscount), 0) AS total

            FROM tbdetail

            WHERE tbdetailbookingid = :idClientBooking
              AND tbdetailisactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $idClientBooking
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (float) ($row['total'] ?? 0);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateDetail(Detail $detail): bool
  {
    try {
      $sql = "
                UPDATE tbdetail
                SET
                    tbdetailbookingid = :idClientBooking,
                    tbdetaillocalserviceid = :idLocalService,
                    tbdetailquantity = :quantityDetail,
                    tbdetailunitprice = :unitPrice,
                    tbdetaildiscount = :discount,
                    tbdetailisactive = :isActiveDetail
                WHERE tbdetailid = :idDetail
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idClientBooking' => $detail->getIdClientBooking(),
        ':idLocalService' => $detail->getIdLocalService(),
        ':quantityDetail' => $detail->getQuantityDetail(),
        ':unitPrice' => $detail->getUnitPrice(),
        ':discount' => $detail->getDiscount(),
        ':isActiveDetail' => $detail->getIsActiveDetail(),
        ':idDetail' => $detail->getIdDetail()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateDetail(int $idDetail): bool
  {
    $sql = "
            UPDATE tbdetail
            SET tbdetailisactive = false
            WHERE tbdetailid = :idDetail
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idDetail' => $idDetail
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteDetail(int $idDetail): bool
  {
    try {
      $sql = "
                DELETE FROM tbdetail
                WHERE tbdetailid = :idDetail
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idDetail' => $idDetail
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // ELIMINAR TODOS LOS DETALLES DE UN BOOKING
  // =========================================================
  public function deleteByBookingDetail(int $idClientBooking): bool
  {
    try {
      $sql = "
                DELETE FROM tbdetail
                WHERE tbdetailbookingid = :idClientBooking
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idClientBooking' => $idClientBooking
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToDetail(array $row): Detail
  {
    return new Detail(
      (int) $row['tbdetailid'],
      (int) $row['tbdetailbookingid'],
      (int) $row['tbdetaillocalserviceid'],
      (int) $row['tbdetailquantity'],
      (float) $row['tbdetailunitprice'],
      (float) $row['tbdetaildiscount'],
      (bool) $row['tbdetailisactive']
    );
  }
}
