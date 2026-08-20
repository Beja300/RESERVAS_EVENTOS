<?php

require_once __DIR__ . '/../models/Invoice.php';

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
  public function saveInvoice(Invoice $invoice): bool
  {
    try {
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
        ':dateInvoice' => $invoice->getDateInvoice(),
        ':statusInvoice' => $invoice->getStatusInvoice(),
        ':isActiveInvoice' => $invoice->getIsActiveInvoice()
      ]);

      $invoice->setIdInvoice((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllInvoice(): array
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

            ORDER BY tbinvoiceid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $invoices = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $invoices[] = $this->mapRowToInvoice($row);
    }

    return $invoices;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdInvoice(int $idInvoice): ?Invoice
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

            WHERE tbinvoiceid = :idInvoice
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idInvoice' => $idInvoice
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToInvoice($row);
  }


  // =========================================================
  // OBTENER POR BOOKING (relación 1:1)
  // =========================================================
  public function getByBookingInvoice(int $idClientBooking): ?Invoice
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

    if (!$row) {
      return null;
    }

    return $this->mapRowToInvoice($row);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateInvoice(Invoice $invoice): bool
  {
    try {
      $sql = "
                UPDATE tbinvoice
                SET
                    tbinvoicebookingid = :idClientBooking,
                    tbinvoicepaymentmethodid = :idPaymentMethod,
                    tbinvoicedate = :dateInvoice,
                    tbinvoicestatus = :statusInvoice,
                    tbinvoiceisactive = :isActiveInvoice
                WHERE tbinvoiceid = :idInvoice
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idClientBooking' => $invoice->getIdClientBooking(),
        ':idPaymentMethod' => $invoice->getIdPaymentMethod(),
        ':dateInvoice' => $invoice->getDateInvoice(),
        ':statusInvoice' => $invoice->getStatusInvoice(),
        ':isActiveInvoice' => $invoice->getIsActiveInvoice(),
        ':idInvoice' => $invoice->getIdInvoice()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // CAMBIAR ESTADO (ej: pendiente, pagada, anulada)
  // =========================================================
  public function updateStatusInvoice(int $idInvoice, string $statusInvoice): bool
  {
    try {
      $sql = "
                UPDATE tbinvoice
                SET tbinvoicestatus = :statusInvoice
                WHERE tbinvoiceid = :idInvoice
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':statusInvoice' => $statusInvoice,
        ':idInvoice' => $idInvoice
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateInvoice(int $idInvoice): bool
  {
    $sql = "
            UPDATE tbinvoice
            SET tbinvoiceisactive = false
            WHERE tbinvoiceid = :idInvoice
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idInvoice' => $idInvoice
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteInvoice(int $idInvoice): bool
  {
    try {
      $sql = "
                DELETE FROM tbinvoice
                WHERE tbinvoiceid = :idInvoice
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idInvoice' => $idInvoice
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToInvoice(array $row): Invoice
  {
    return new Invoice(
      (int) $row['tbinvoiceid'],
      (int) $row['tbinvoicebookingid'],
      (int) $row['tbinvoicepaymentmethodid'],
      $row['tbinvoicedate'],
      $row['tbinvoicestatus'],
      (bool) $row['tbinvoiceisactive']
    );
  }
}
