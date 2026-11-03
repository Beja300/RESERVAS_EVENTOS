# Fase 7 — Cierre: enrutamiento, documentación de arquitectura y verificación

## Resultado de la auditoría de rutas (`Public/index.php`) — SIN HALLAZGOS
Verificado de forma estática (solo lectura):

- `$routeMap` (líneas 121-210): **77 rutas**. Todos los controladores referenciados existen en `App/Controller/` y todos los métodos referenciados existen.
- `$fallbackControllers` (213-222): 8 controladores (`auth`, `invoice`, `paymentmethod`, `location`, `notification`, `promotion`, `api`, `credit`). Todos existen y todas las acciones de la lista blanca existen como métodos.
- `$allowedActions` (228-243): 14 grupos (8 fallback + 6 divididos `service`, `venue`, `booking`, `admin`, `client`, `owner`).
- Comprobaciones cruzadas:
  - Archivos de controlador presentes (25) == clases referenciadas (25).
  - Sin **acciones huérfanas** en `$allowedActions` (toda acción permitida tiene ruta resoluble).
  - Sin rutas **inalcanzables** en `$routeMap` (toda entrada está en la lista blanca).
  - Sin **claves duplicadas** en `$routeMap`.
  - Sin métodos ni controladores **inexistentes**.

**Conclusión:** no hay acciones huérfanas ni rutas hacia controladores inexistentes. → `Public/index.php` **no requiere cambios de código** (paso de verificación satisfecho).

> Pendiente de decisión (ver preguntas): el comentario de cabecera "ÍNDICE COMPLETO DE RUTAS (las 30 vistas)" (líneas 15-52) está desactualizado (lista 29 entradas y usa notación mixta), aunque es solo un comentario y no afecta al despacho.

## Actualización de `Documentation/architecture-audit.md`
El documento describe el **estado inicial** (deuda abierta) y tiene referencias de línea obsoletas. Se actualizará para certificar la resolución, manteniendo la auditoría inicial como contexto histórico y añadiendo el estado resuelto.

### Ajustes de contenido
- **Introducción**: reflejar que es la auditoría actualizada tras resolver la deuda (§4) e implementar Fase 6.
- **§1.2** (tabla de estructuras): actualizar referencias a líneas actuales:
  - `$routeMap` → `index.php:121-210`; `$fallbackControllers` → `213-222`; `$allowedActions` → `228-243`.
- **§1.4**: actualizar referencias: `renderError` → `250-266`; `set_error_handler` → `276-289`; `set_exception_handler` → `291-336` (mencionar rollback de transacción activa).
- **§2.3**: reemplazar la viñeta "**Sin autoloader**" por la existencia de `Configuration/Autoloader.php` (PSR-4 + fallback), registrado en `index.php:67-68`.
- **§3.1**: reemplazar la nota de credenciales hardcodeadas por la lectura desde `.env` vía `Configuration/DotEnv.php` (`DataBase.php:54-60`).
- **§3.2**: eliminar la nota "> Este bloque aparece duplicado… Ver §4.5".
- **§3.3**: reemplazar la descripción de objeto serializado en `$_SESSION['user']` por el `UserSessionDTO` (`App/Model/UserSessionDTO.php`); eliminar la referencia a precarga `index.php:67-73`.
- **§4.1–4.6**: convertir cada una a **RESUELTA**, añadiendo un bloque "Resolución" con archivos/acciones:
  - 4.1 → `UserSessionDTO` (login devuelve DTO; `_helpers::current_user()`; rehidratación en perfiles).
  - 4.2 → `Configuration/Autoloader.php` (PSR-4 + fallback), registrado en el front controller.
  - 4.3 → **50 FK** en `dbeventhall.sql` + migración `DataBase/ScriptsSQL/migrations/001_add_foreign_keys.sql` (con pre-chequeos de huérfanos).
  - 4.4 → `.env`/`.env.example` + `Configuration/DotEnv.php`; `.env` en `.gitignore`.
  - 4.5 → bloque de crédito único en `dbeventhall.sql`.
  - 4.6 → eliminados `App/Repository/Service/HistoryService.php`, `App/View/Admin/*.tmp`, `tmp_v.txt`; `.gitignore` con `*.tmp`/`tmp_*`.
- **Nueva §4.7 "Transacciones atómicas y logging seguro (Fase 6) — RESUELTA"**: gestor anidable con `SAVEPOINT` en `Configuration/DataBase.php` (`beginTransaction`/`commit`/`rollBack`/`inTransaction`/`getActiveConnection`/`rollBackActive`), `App/Service/ErrorLogger.php`, `App/Service/ServiceException.php`, servicios envueltos (BookingService, CreditService, InvoiceService, BookingTicketService, BookingActionService, BookingAdminService) y rollback de red en el `set_exception_handler`.
- **Anexo**: añadir columna **Estado** (todas "Resuelta") y una fila para Fase 6.

## Verificación integral de cierre
1. `php -l` sobre los archivos modificados/creados de la fase (`Documentation` es .md; index.php si se toca).
2. Prueba de humo HTTP (servidor `php -S`, `error_log` a archivo):
   - `auth/showLogin` (GET 200) + login cliente (POST 302).
   - Catálogo `venue/catalog` (GET 200).
   - Creación de reserva `booking/create` (POST 302) y `booking/detail`.
   - Perfil `client/profile` (GET 200).
   - Revisar `error_log` sin warnings/errores no capturados.
3. Confirmar que `Documentation/architecture-audit.md` refleja el estado real (FK=50, migración existente, `.env`, autoloader, DTO, Fase 6).

## Notas
- No se modificará `DataBase/ScriptsSQL/dbeventhall.sql` ni la migración (ya completados en 4.3).
- No se tocarán otros archivos de fases previas.

## Preguntas abiertas
1. Enfoque del documento: **aditivo** (mantener auditoría inicial + marcar resoluciones) —recomendado— o reescritura completa al estado actual.
2. ¿Actualizar también el comentario de cabecera "ÍNDICE DE RUTAS" de `Public/index.php` (solo comentario) o dejar `index.php` intacto?
