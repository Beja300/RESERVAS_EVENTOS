<?php

/**
 * Migrador idempotente del esquema.
 *
 * La aplicación no usa un framework de migraciones. Para que una base ya
 * instalada siga funcionando al actualizar el código, este migrador añade
 * columnas/tablas nuevas una sola vez y registra la versión aplicada en
 * tbschemaversion. En instalaciones nuevas el SQL canónico ya trae todo.
 */
class SchemaMigrator
{
  private const VERSION = '2026-11-services-groups-venues-credit';

  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public static function ensure(PDO $connection): void
  {
    try {
      (new self($connection))->run();
    } catch (\Throwable $e) {
      error_log('[SchemaMigrator] No se pudo aplicar la migración: ' . $e->getMessage());
    }
  }

  private function run(): void
  {
    $this->connection->exec(
      "CREATE TABLE IF NOT EXISTS tbschemaversion (
        tbschemaversionid INT AUTO_INCREMENT PRIMARY KEY,
        tbschemaversionname VARCHAR(100) NOT NULL,
        tbschemaversionappliedat DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB"
    );

    $stmt = $this->connection->prepare(
      'SELECT COUNT(*) FROM tbschemaversion WHERE tbschemaversionname = :name'
    );
    $stmt->execute([':name' => self::VERSION]);

    if ((int) $stmt->fetchColumn() > 0) {
      return;
    }

    $this->createTables();
    $this->addColumns();
    $this->backfillServiceGroups();
    $this->backfillLegacyServiceApproval();
    $this->backfillCreditInstallments();
    $this->backfillVenueGallery();

    $stmt = $this->connection->prepare(
      'INSERT INTO tbschemaversion (tbschemaversionname) VALUES (:name)'
    );
    $stmt->execute([':name' => self::VERSION]);
  }

  private function createTables(): void
  {
    $tables = [
      "CREATE TABLE IF NOT EXISTS tbservicegroup (
        tbservicegroupid INT AUTO_INCREMENT PRIMARY KEY,
        tbownerid INT NOT NULL,
        tbservicegroupname VARCHAR(200) NOT NULL,
        tbservicegroupcreatedat DATETIME DEFAULT CURRENT_TIMESTAMP,
        tbservicegroupactive BOOLEAN NOT NULL DEFAULT TRUE
      ) ENGINE=InnoDB",
      "CREATE TABLE IF NOT EXISTS tbserviceimage (
        tbserviceimageid INT AUTO_INCREMENT PRIMARY KEY,
        tbserviceid INT NOT NULL,
        tbserviceimagepath VARCHAR(255) NOT NULL,
        tbserviceimagesort INT NOT NULL DEFAULT 0,
        tbserviceimageactive BOOLEAN NOT NULL DEFAULT TRUE
      ) ENGINE=InnoDB",
      "CREATE TABLE IF NOT EXISTS tbvenuerentalblock (
        tbvenuerentalblockid INT AUTO_INCREMENT PRIMARY KEY,
        tbvenueid INT NOT NULL,
        tbvenuerentalblockname VARCHAR(100) NOT NULL,
        tbvenuerentalblockstart TIME NOT NULL,
        tbvenuerentalblockend TIME NOT NULL,
        tbvenuerentalblockprice DECIMAL(10,2) NOT NULL DEFAULT 0,
        tbvenuerentalblocksort INT NOT NULL DEFAULT 0,
        tbvenuerentalblockactive BOOLEAN NOT NULL DEFAULT TRUE
      ) ENGINE=InnoDB",
      "CREATE TABLE IF NOT EXISTS tbownerblockedclient (
        tbownerblockedclientid INT AUTO_INCREMENT PRIMARY KEY,
        tbownerid INT NOT NULL,
        tbclientid INT NOT NULL,
        tbownerblockedclientreason VARCHAR(500),
        tbownerblockedclientdate DATETIME DEFAULT CURRENT_TIMESTAMP,
        tbownerblockedclientactive BOOLEAN NOT NULL DEFAULT TRUE
      ) ENGINE=InnoDB",
      "CREATE TABLE IF NOT EXISTS tbcreditinstallment (
        tbcreditinstallmentid INT AUTO_INCREMENT PRIMARY KEY,
        tbcreditid INT NOT NULL,
        tbcreditinstallmentnumber INT NOT NULL,
        tbcreditinstallmentamount DECIMAL(12,2) NOT NULL,
        tbcreditinstallmentduedate DATE NOT NULL,
        tbcreditinstallmentpaiddate DATE NULL,
        tbcreditinstallmentstate VARCHAR(30) NOT NULL DEFAULT 'pendiente',
        tbcreditinstallmentactive BOOLEAN NOT NULL DEFAULT TRUE
      ) ENGINE=InnoDB",
      "CREATE TABLE IF NOT EXISTS tbvenueimage (
        tbvenueimageid INT AUTO_INCREMENT PRIMARY KEY,
        tbvenueid INT NOT NULL,
        tbvenueimagepath VARCHAR(255) NOT NULL,
        tbvenueimagesort INT NOT NULL DEFAULT 0,
        tbvenueimageactive TINYINT(1) NOT NULL DEFAULT 1
      ) ENGINE=InnoDB",
    ];

    foreach ($tables as $sql) {
      $this->connection->exec($sql);
    }
  }

  private function addColumns(): void
  {
    $columns = [
      'tbvenue' => [
        'tbvenuedescription' => 'VARCHAR(500) NULL',
        'tbvenuesize' => 'VARCHAR(100) NULL',
        'tbvenuebathrooms' => 'INT NULL',
        'tbvenueparking' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'tbvenueparkingcapacity' => 'INT NULL',
        'tbvenuemincapacity' => 'INT NULL',
        'tbvenuecleaningfee' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
      ],
      'tbservice' => [
        'tbservicedescription' => 'VARCHAR(500) NULL',
        'tbserviceunit' => "VARCHAR(20) NOT NULL DEFAULT 'unidad'",
        'tbserviceminquantity' => 'INT NOT NULL DEFAULT 1',
        'tbservicemaxquantity' => 'INT NULL',
        'tbserviceincludedquantity' => 'INT NOT NULL DEFAULT 0',
        'tbservicegroupid' => 'INT NULL',
      ],
      'tbbooking' => [
        'tbbookingattendees' => 'INT NULL',
        'tbbookingrentalblockid' => 'INT NULL',
      ],
      'tbdetail' => [
        'tbdetailconcept' => 'VARCHAR(255) NULL',
      ],
      'tblocation' => [
        'tblocationtype' => "VARCHAR(20) NOT NULL DEFAULT 'exacta'",
        'tblocationapproximate' => 'VARCHAR(300) NULL',
      ],
      'tbcreditconfig' => [
        'tbcreditconfiginstallments' => 'INT NOT NULL DEFAULT 1',
        'tbcreditconfigfrequency' => "VARCHAR(20) NOT NULL DEFAULT 'mensual'",
        'tbcreditconfiggracedays' => 'INT NOT NULL DEFAULT 0',
        'tbcreditconfigreminderdays' => 'INT NOT NULL DEFAULT 3',
      ],
    ];

    foreach ($columns as $table => $definitions) {
      foreach ($definitions as $column => $definition) {
        $this->addColumnIfMissing($table, $column, $definition);
      }
    }
  }

  private function addColumnIfMissing(string $table, string $column, string $definition): void
  {
    $stmt = $this->connection->prepare(
      "SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column"
    );
    $stmt->execute([':table' => $table, ':column' => $column]);

    if ((int) $stmt->fetchColumn() === 0) {
      $this->connection->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
  }

  private function backfillServiceGroups(): void
  {
    $stmt = $this->connection->query(
      "SELECT s.tbserviceid, s.tbservicename, v.tbownerid
       FROM tbservice s
       INNER JOIN tbvenue v ON v.tbvenueid = s.tbvenueid
       WHERE s.tbservicegroupid IS NULL"
    );

    $insertGroup = $this->connection->prepare(
      'INSERT INTO tbservicegroup (tbownerid, tbservicegroupname) VALUES (:ownerId, :name)'
    );
    $updateService = $this->connection->prepare(
      'UPDATE tbservice SET tbservicegroupid = :groupId WHERE tbserviceid = :serviceId'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $insertGroup->execute([
        ':ownerId' => (int) $row['tbownerid'],
        ':name' => (string) $row['tbservicename'],
      ]);
      $updateService->execute([
        ':groupId' => (int) $this->connection->lastInsertId(),
        ':serviceId' => (int) $row['tbserviceid'],
      ]);
    }
  }

  private function backfillLegacyServiceApproval(): void
  {
    $this->connection->exec(
      "UPDATE tbservice
       SET tbservicestate = 'aprobado', tbserviceactive = 0
       WHERE tbservicestate IN ('solicitado', 'rechazado')"
    );
  }

  private function backfillCreditInstallments(): void
  {
    $stmt = $this->connection->query(
      "SELECT c.* FROM tbcredit c
       WHERE c.tbcreditactive = true
         AND NOT EXISTS (
           SELECT 1 FROM tbcreditinstallment i WHERE i.tbcreditid = c.tbcreditid
         )"
    );

    $insert = $this->connection->prepare(
      'INSERT INTO tbcreditinstallment (
        tbcreditid, tbcreditinstallmentnumber, tbcreditinstallmentamount,
        tbcreditinstallmentduedate, tbcreditinstallmentstate
      ) VALUES (
        :creditId, 1, :amount, :dueDate, :state
      )'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $state = $row['tbcreditstate'] === 'pagado' ? 'pagado' : 'pendiente';
      $insert->execute([
        ':creditId' => (int) $row['tbcreditid'],
        ':amount' => (float) $row['tbcreditbalance'],
        ':dueDate' => (string) $row['tbcreditduedate'],
        ':state' => $state,
      ]);
    }
  }

  private function backfillVenueGallery(): void
  {
    $stmt = $this->connection->query(
      "SELECT v.tbvenueid, v.tbvenueimage FROM tbvenue v
       WHERE v.tbvenueimage IS NOT NULL AND v.tbvenueimage <> ''
         AND NOT EXISTS (
           SELECT 1 FROM tbvenueimage i
           WHERE i.tbvenueid = v.tbvenueid AND i.tbvenueimagepath = v.tbvenueimage
         )"
    );

    $insert = $this->connection->prepare(
      'INSERT INTO tbvenueimage (tbvenueid, tbvenueimagepath, tbvenueimagesort)
       VALUES (:venueId, :path, 0)'
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $insert->execute([
        ':venueId' => (int) $row['tbvenueid'],
        ':path' => (string) $row['tbvenueimage'],
      ]);
    }
  }
}
