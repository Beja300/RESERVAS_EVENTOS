<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';

/**
 * DemoDataService — Operaciones de "datos de demostración":
 *
 *  - cleanGeneratedData(): vacía SOLO las tablas de datos generados por el
 *    uso (botón "limpiar" del panel Admin). No toca datos maestros.
 *  - cleanAndReseed(): vacía TODAS las tablas y re-ejecuta seed_test_data.sql
 *    (botón "restaurar datos de prueba" del login).
 *
 * Centraliza la manipulación de tablas fuera de los controladores y evita
 * inyección SQL: los nombres de tabla se validan contra una lista blanca
 * (regex ^tb...) antes de ser interpolados entre backticks.
 */
class DemoDataService
{
  // Tablas generadas por uso (demo). Se vacían solas; NO datos maestros:
  // roles, perfiles, ubicaciones, métodos de pago ni comisión.
  private const GENERATED_TABLES = [
    'tbeearning',
    'tbinvoice',
    'tbbookingticket',
    'tbbookingdetail',
    'tbbooking',
    'tbvenuerating',
    'tbservicerating',
    'tbpromotionservice',
    'tbpromotion',
    'tbservicehistory',
    'tbservice',
    'tbvenue',
    'tbnotification',
    'tbuserhistory',
    'tbownerhistory',
    'tbownerpayment',
  ];

  private const TABLE_NAME_PATTERN = '/^tb[a-z0-9_]+$/i';

  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function cleanGeneratedData(): void
  {
    $this->wipeTables(self::GENERATED_TABLES);
  }

  public function cleanAndReseed(): void
  {
    $tables = $this->connection->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $this->wipeTables($tables);

    $seedFile = __DIR__ . '/../../DataBase/ScriptsSQL/seed_test_data.sql';
    $sql = file_get_contents($seedFile);

    if ($sql === false) {
      throw new \RuntimeException('No se pudo leer el archivo de datos de prueba.');
    }

    $this->connection->exec($sql);
  }

  // -----------------------------------------------------------
  // Vacía las tablas dadas desactivando las comprobaciones de FK.
  // Los nombres se filtran con una lista blanca antes de usarse.
  // -----------------------------------------------------------
  private function wipeTables(array $tables): void
  {
    $safe = array_values(array_filter(
      $tables,
      static fn($name): bool => is_string($name) && preg_match(self::TABLE_NAME_PATTERN, $name) === 1
    ));

    $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');

    try {
      foreach ($safe as $table) {
        $this->connection->exec('DELETE FROM `' . $table . '`');
      }
    } finally {
      $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
  }
}