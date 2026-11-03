<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/PaymentMethod.php';

class PaymentMethodRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(PaymentMethod $paymentMethod): int
  {
    $sql = "
            INSERT INTO tbpaymentmethod (
                tbpaymentmethodtype,
                tbpaymentmethodactive
            )
            VALUES (
                :paymentMethod,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':paymentMethod' => $paymentMethod->getPaymentMethod(),
      ':isActive'      => (int) $paymentMethod->getIsActive()
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER TODOS (admin: muestra activos e inactivos)
  // =========================================================
  public const CATALOG = [
    'Efectivo',
    'Transferencia Bancaria',
    'SINPE Móvil',
  ];

  public function findAll(): array
  {
    $this->ensureCatalog();

    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodtype,
                tbpaymentmethodactive

            FROM tbpaymentmethod

            ORDER BY tbpaymentmethodactive DESC, tbpaymentmethodtype ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return $this->sortCatalog(array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC)));
  }

  // =========================================================
  // OBTENER ACTIVOS
  // =========================================================
  public function findActive(): array
  {
    return array_values(array_filter(
      $this->findAll(),
      static fn(PaymentMethod $method): bool => $method->getIsActive()
    ));
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idPaymentMethod): ?PaymentMethod
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodtype,
                tbpaymentmethodactive

            FROM tbpaymentmethod

            WHERE tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idPaymentMethod' => $idPaymentMethod
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(PaymentMethod $paymentMethod): void
  {
    $sql = "
            UPDATE tbpaymentmethod
            SET    tbpaymentmethodtype   = :type,
                   tbpaymentmethodactive = :isActive
            WHERE  tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':type'            => $paymentMethod->getPaymentMethod(),
      ':isActive'        => (int) $paymentMethod->getIsActive(),
      ':idPaymentMethod' => $paymentMethod->getIdPaymentMethod()
    ]);
  }

  // =========================================================
  // DESACTIVAR (soft delete: se oculta de los catálogos activos)
  // =========================================================
  public function deactivate(int $idPaymentMethod): void
  {
    $sql = "
            UPDATE tbpaymentmethod
            SET    tbpaymentmethodactive = 0
            WHERE  tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idPaymentMethod' => $idPaymentMethod
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): PaymentMethod
  {
    return new PaymentMethod(
      idPaymentMethod: (int) $row['tbpaymentmethodid'],
      paymentMethod: $row['tbpaymentmethodtype'],
      isActive: $this->toBool($row['tbpaymentmethodactive'])
    );
  }

  /**
   * Garantiza Efectivo, Transferencia Bancaria y SINPE Móvil en el catálogo.
   */
  private function ensureCatalog(): void
  {
    static $ensured = false;
    if ($ensured) {
      return;
    }
    $ensured = true;

    $sql = "
      SELECT tbpaymentmethodid, tbpaymentmethodtype, tbpaymentmethodactive
      FROM tbpaymentmethod
    ";
    $stmt = $this->connection->query($sql);
    $existing = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $existing[mb_strtolower(trim((string) $row['tbpaymentmethodtype']))] = $row;
    }

    foreach (self::CATALOG as $name) {
      $key = mb_strtolower($name);
      if (!isset($existing[$key])) {
        try {
          $this->save(new PaymentMethod(0, $name, true));
        } catch (PDOException $e) {
          // Ya existía (carrera o índice único); se continúa con el catálogo.
        }
        continue;
      }
      if (!$this->toBool($existing[$key]['tbpaymentmethodactive'])) {
        $method = $this->mapRow($existing[$key]);
        $method->setIsActive(true);
        $this->update($method);
      }
    }
  }

  /**
   * @param list<PaymentMethod> $methods
   * @return list<PaymentMethod>
   */
  private function sortCatalog(array $methods): array
  {
    $order = [];
    foreach (self::CATALOG as $index => $name) {
      $order[mb_strtolower($name)] = $index;
    }

    usort($methods, static function (PaymentMethod $a, PaymentMethod $b) use ($order): int {
      if ($a->getIsActive() !== $b->getIsActive()) {
        return $a->getIsActive() ? -1 : 1;
      }
      $oa = $order[mb_strtolower($a->getPaymentMethod())] ?? 100;
      $ob = $order[mb_strtolower($b->getPaymentMethod())] ?? 100;
      if ($oa !== $ob) {
        return $oa <=> $ob;
      }
      return strcasecmp($a->getPaymentMethod(), $b->getPaymentMethod());
    });

    return $methods;
  }

  private function toBool(mixed $value): bool
  {
    if (is_bool($value)) {
      return $value;
    }
    if (is_int($value) || is_float($value)) {
      return $value != 0;
    }
    if (is_string($value) && strlen($value) === 1 && ord($value) <= 1) {
      return ord($value) === 1;
    }
    $normalized = strtolower(trim((string) $value));
    if (in_array($normalized, ['0', 'false', 'f', 'no', 'off', ''], true)) {
      return false;
    }
    if (in_array($normalized, ['1', 'true', 't', 'yes', 'on'], true)) {
      return true;
    }
    return is_numeric($normalized) && (float) $normalized != 0.0;
  }
}
