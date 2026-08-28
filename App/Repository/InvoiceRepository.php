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
                tbinvoicebookingid,
                tbinvoicepaymentmethodid,
                tbinvoicedate,
                tbinvoicestatus,
                tbinvoiceisactive
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
      ':isActiveInvoice' => $invoice->getIsActiveInvoice()
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
                tbinvoicebookingid,
                tbinvoicepaymentmethodid,
                tbinvoicedate,
                tbinvoicestatus,
                tbinvoiceisactive

            FROM tbinvoice

            WHERE tbinvoicebookingid = :idClientBooking
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
      idClientBooking: (int) $row['tbinvoicebookingid'],
      idPaymentMethod: (int) $row['tbinvoicepaymentmethodid'],
      dateInvoice: $row['tbinvoicedate'],
      statusInvoice: $row['tbinvoicestatus'],
      isActiveInvoice: (bool) $row['tbinvoiceisactive']
    );
  }
}
