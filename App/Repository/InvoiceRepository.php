<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Invoice.php';

class InvoiceRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(Invoice $invoice): int
  {
    $sql = "
            INSERT INTO tbinvoice (
                tbbookingid,
                tbpaymentmethodid,
                tbinvoicedate,
                tbinvoicestatus,
                tbinvoiceactive
            )
            VALUES (
                :idClientBooking,
                :idPaymentMethod,
                :dateInvoice,
                :statusInvoice,
                :isActiveInvoice
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $invoice->getIdClientBooking(),
      ':idPaymentMethod' => $invoice->getIdPaymentMethod(),
      ':dateInvoice'     => $invoice->getDateInvoice(),
      ':statusInvoice'   => $invoice->getStatusInvoice(),
      ':isActiveInvoice' => $this->toDb($invoice->getIsActiveInvoice())
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER POR BOOKING (relación 1:1)
  // =========================================================
  public function findByBooking(int $idClientBooking): ?Invoice
  {
    $sql = "
            SELECT
                tbinvoiceid,
                tbbookingid,
                tbpaymentmethodid,
                tbinvoicedate,
                tbinvoicestatus,
                tbinvoiceactive

            FROM tbinvoice

            WHERE tbbookingid = :idClientBooking
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClientBooking' => $idClientBooking
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // CAMBIAR ESTADO
  // =========================================================
  public function updateStatus(int $idInvoice, string $statusInvoice): bool
  {
    $sql = "
            UPDATE tbinvoice
            SET tbinvoicestatus = :statusInvoice
            WHERE tbinvoiceid = :idInvoice
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idInvoice'     => $idInvoice,
      ':statusInvoice' => $statusInvoice
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Invoice
  {
    return new Invoice(
      idInvoice: (int) $row['tbinvoiceid'],
      idClientBooking: (int) $row['tbbookingid'],
      idPaymentMethod: (int) $row['tbpaymentmethodid'],
      dateInvoice: $row['tbinvoicedate'],
      statusInvoice: $row['tbinvoicestatus'],
      isActiveInvoice: $this->toBool($row['tbinvoiceactive'])
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
