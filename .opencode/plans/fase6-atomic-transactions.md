# Fase 6 — Transacciones atómicas PDO + registro seguro de errores

## Objetivo
Dotar de atomicidad a los flujos de escritura compuesta (reservas, facturación,
créditos) mediante `beginTransaction`/`commit`/`rollBack` de PDO, y estandarizar
el registro seguro de errores técnicos (detalle + traza en `error_log`, mensaje
genérico al usuario).

## Decisiones aprobadas
1. **Alcance completo**: incluir servicios de negocio **y** orquestadores
   (`BookingActionService`, `BookingAdminService`, `BookingTicketService`).
   Requiere un **gestor de transacciones anidable** (SAVEPOINT) en `DataBase`.
2. Envolver además `requestRefund`, `cancel` y `generate`.
3. Nuevo `App/Service/ErrorLogger.php` para el logging estandarizado.
4. Nueva `App/Service/ServiceException` (fallo técnico) vs `BusinessRuleException`
   (regla de negocio).
5. **Notificaciones best-effort**: NO se modifica `NotificationService::notify()`
   (captura `\Throwable` y devuelve 0); una notificación fallida no aborta la
   reserva. El **historial** (`HistoryService`) SÍ participa de la transacción.

## Gestor de transacciones anidable (`Configuration/DataBase.php`)
Se añade al Singleton existente (sin alterar `getConnection()`):

```php
private static int $transactionDepth = 0;
private static ?PDO $txConnection = null;

public static function beginTransaction(?PDO $connection = null): void
// depth 0 -> PDO::beginTransaction(); depth > 0 -> SAVEPOINT eventhall_sp_<depth>

public static function commit(): void
// depth-- ; 0 -> PDO::commit();     > 0 -> RELEASE SAVEPOINT eventhall_sp_<depth>

public static function rollBack(): void
// depth-- ; 0 -> PDO::rollBack();   > 0 -> ROLLBACK TO SAVEPOINT + RELEASE SAVEPOINT

public static function inTransaction(): bool
public static function transactionDepth(): int
public static function getActiveConnection(): ?PDO   // NO instancia conexión
public static function rollBackActive(): void        // rollback total + reset depth
```

Nota de diseño: los servicios llaman `DataBase::beginTransaction($this->connection)`
en lugar de `$this->connection->beginTransaction()` para que la composición
orquestador→servicio use SAVEPOINTs y no falle con "ya hay una transacción activa".

## Patrón estándar por método
```php
DataBase::beginTransaction($this->connection);
try {
    // ...escrituras compuestas...
    DataBase::commit();
} catch (\Throwable $e) {
    DataBase::rollBack();  // seguro a cualquier profundidad
    if ($e instanceof BusinessRuleException || $e instanceof ServiceException) {
        throw $e;          // error ya limpio (el origen ya lo registró)
    }
    ErrorLogger::log($e, __METHOD__);
    throw new ServiceException('No se pudo completar la operación. Intenta de nuevo.', 0, $e);
}
```

## Archivos nuevos
- `App/Service/ServiceException.php` — `class ServiceException extends \RuntimeException {}`.
- `App/Service/ErrorLogger.php` — `final class ErrorLogger { public static function log(\Throwable $e, string $context): void }`
  que escribe una línea `error_log()` con contexto, clase, mensaje, `file:line` y `getTraceAsString()`.

## Archivos editados
- `Configuration/DataBase.php` — gestor + accesores anteriores.
- `Public/index.php` — dentro de `set_exception_handler`, antes del `error_log`:
  `try { DataBase::rollBackActive(); } catch (\Throwable) {}` (sin enmascarar el error original).
- `App/Service/BookingService.php` — `createBooking` (reemplazar tx cruda + catch débil), `cancel`, `requestRefund`.
- `App/Service/CreditService.php` — `requestCredit`, `registerPayment` (cubre `settleCredit` + `EarningService::recordEarning`).
- `App/Service/InvoiceService.php` — añadir `private PDO $connection;` (firma del ctor sin cambios); envolver `generate`, `approve`, `reject`, `updatePaymentStatus`.
- `App/Service/BookingTicketService.php` — `upload`, `approve`, `reject`.
- `App/Service/BookingActionService.php` — `create`, `cancel`, `requestRefund`, `pay`, `uploadTicket`, `approveTicket`, `rejectTicket`. **Excluir `addLine`** (llega a `DetailRepository::save`, que abre su propia transacción PDO cruda).
- `App/Service/BookingAdminService.php` — `cancel`, `reschedule`, `changeVenue`, `approveRefund`, `rejectRefund`.
- Añadir `require_once __DIR__ . '/ErrorLogger.php';` y `'/ServiceException.php';` a cada servicio editado.

## Riesgos / límites conocidos
- Repos con transacción cruda (`Admin/Owner/Client::save` atragan `PDOException` y
  devuelven `false`; `DetailRepository::save` relanza) NO se refactorizan. Ningún
  flujo envuelto los invoca (salvo `addLine`, que queda fuera).
- Secuencias a nivel de controlador (p. ej. `AdminBookingController.approvePayment`
  → `invoiceService->approve()` y luego `earningService->recordEarning()`) quedan
  fuera del alcance; no son un único servicio.

## Verificación
1. `php -l` en todos los archivos creados/editados.
2. **Fallo forzado con triggers `SIGNAL`** temporales:
   - `BEFORE INSERT ON tbdetail` → `tbbooking` sin fila huérfana (creación + cadena de savepoints).
   - `BEFORE UPDATE ON tbbooking` en `requestCredit` → sin `tbcredit` y estado intacto.
   - `BEFORE UPDATE ON tbcredit` en `registerPayment` → sin `tbcreditpayment`.
   - `BEFORE UPDATE ON tbbooking` en `InvoiceService::approve` → estado de `tbinvoice` intacto.
   - Eliminar los triggers al terminar.
3. **Flujos normales** por HTTP (`200/302`): crear reserva, `requestCredit`, `registerPayment`, aprobar/rechazar factura, cancelar.
4. **Rollback del handler global**: script CLI iniciando tx, insertando `tbbooking`, llamando `DataBase::rollBackActive()` → sin fila.
5. **Log / no fuga**: `php -S ... -d error_log=/tmp/opencode/eh_err.log`, provocar fallo, comprobar traza en el log y respuesta `500` sin `SQLSTATE`/SQL.
