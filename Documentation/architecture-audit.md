# Auditoría y Arquitectura — EventHall

> Documento de auditoría técnica del proyecto **RESERVAS_EVENTOS**.
> Describe la arquitectura real observada en el código fuente, las capas, el
> esquema de base de datos, el manejo de sesiones y un registro priorizado de
> deuda técnica y riesgos.
>
> **Estado de cierre (Fase 7):** la totalidad de la deuda técnica registrada en
> §4 (4.1–4.6) ha sido **resuelta**, y se completó la **Fase 6** (transacciones
> atómicas PDO + logging seguro), documentada en §4.7. Este documento conserva
> la descripción del estado inicial como contexto histórico y añade, sección por
> sección, el estado resuelto.

---

## 1. Resumen de Arquitectura y Ruteo

### 1.1 Front Controller

La aplicación sigue el patrón **Front Controller**: existe un único punto de
entrada, `Public/index.php`, por el que se despachan **todas** las peticiones.
No hay URL rewriting ni un router basado en rutas REST; el enrutamiento se
resuelve con parámetros de query string:

```
/Public/index.php?controller=X&action=Y
```

- `controller` → clave del controlador (`venue`, `auth`, `admin`, ...).
- `action` → método público de ese controlador (`catalog`, `login`, ...).

La ruta por defecto (acceso sin parámetros) es `venue/catalog`.

### 1.2 Mapas de rutas explícitos

El despacho se apoya en tres estructuras definidas en `Public/index.php`:

| Estructura | Ubicación | Propósito |
| --- | --- | --- |
| `$routeMap` | `index.php:121-210` | Mapea `"controller.action"` a un par `[ClaseControladora, método]`. Se usa cuando un mismo prefijo de controlador (`venue`, `service`, `booking`, `admin`, `client`, `owner`) se reparte entre **varias clases** según la función. |
| `$fallbackControllers` | `index.php:213-222` | Mapea un `controller` completo a **una sola clase** (`auth`, `invoice`, `paymentmethod`, `location`, `notification`, `promotion`, `api`, `credit`) donde la acción coincide con el método. Tiene prioridad sobre `$routeMap`. |
| `$allowedActions` | `index.php:228-243` | **Lista blanca** de acciones permitidas por controlador. Ninguna acción fuera de esta lista puede ejecutarse. |

Ejemplos:

```php
// $routeMap: un controller se reparte entre clases
'venue.catalog'   => ['VenueCatalogController', 'catalog'],
'venue.list'      => ['OwnerVenueController',  'list'],
'booking.detail'  => ['BookingDetailController','detail'],

// $fallbackControllers: clase única para todo el controller
'auth'            => 'AuthController',
'credit'          => 'CreditController',
```

> **Auditoría de rutas (Fase 7):** verificado que las tres estructuras son
> **consistentes** — 25 controladores referenciados (fallback + routeMap)
> coinciden con los 25 archivos de `App/Controller/`, `$routeMap` contiene 77
> rutas y no hay acciones **huérfanas** en `$allowedActions`, rutas
> **inalcanzables** ni claves duplicadas. No se requirieron cambios en el
> despachador.

### 1.3 Flujo de despacho

1. Se normaliza `controller` (`strtolower`/`trim`) y se lee `action`.
2. Se valida que el controlador exista en `$fallbackControllers` o
   `$allowedActions`; si no → `404`.
3. Se valida que la acción esté en `$allowedActions[$controllerKey]`; si no → `404`.
4. Se resuelve la clase y el método (fallback → routeMap → error `404`).
5. Se comprueba con `class_exists()` que la clase exista (el autoloader la
   resuelve desde `App/Controller/`); si no se define → `404`.
6. Se instancia la controladora y se invoca `$controller->$method()`.
   **La propia controladora es la que valida sesión y roles** (vía guards).

### 1.4 Manejo de respuestas y renderizado de errores

- La salida **nunca** expone detalles de PHP/SQL: `display_errors=0` y
  `log_errors=1` (`index.php:60-62`).
- `set_error_handler` (`index.php:276-289`) convierte warnings en
  `ErrorException`, **excepto** notices/deprecations, que solo se registran.
- `set_exception_handler` (`index.php:291-336`), antes de responder, revierte
  cualquier transacción PDO activa (`DataBase::rollBackActive()`) y clasifica el
  error:
  - `BusinessRuleException` → HTTP `422` (error de validación, mensaje visible).
  - Cualquier otro `Throwable` → HTTP `500` con mensaje genérico.
  - Si la petición es AJAX y las cabeceras no se han enviado, responde JSON
    mediante `respond_json()`.
- `renderError(int $code, string $message)` (`index.php:250-266`) genera una
  página HTML ligera y autocontenida (sin dependencia de BD ni vistas) para
  `404`, `500`, etc.
- Respuestas AJAX se detectan con `is_ajax()` (cabecera `X-Requested-With` o
  `Accept: application/json`) y se emiten con `respond_json()`.

### 1.5 Renombrado histórico

Cada acción conserva su **URL histórica** aunque la lógica se haya dividido en
nuevas clases. Por ejemplo, `venue/catalog` vive en `VenueCatalogController`
mientras `venue/list` vive en `OwnerVenueController`, manteniendo idénticos los
parámetros de la petición.

---

## 2. Estructura del Proyecto y Capas

### 2.1 Jerarquía de capas

```
Petición HTTP
     │
     ▼
Public/index.php            Front Controller (ruteo + handlers globales)
     │
     ▼
App/Controller/*            Controladora: valida sesión/rol/CSRF, orquesta,
     │                      arma variables y renderiza la vista.
     ▼
App/Service/*               Servicio: reglas de negocio y validaciones.
     │                      (p. ej. AuthService, BookingService, CreditService)
     ▼
App/Repository/*            Repositorio: acceso a datos con PDO (SQL a mano).
     │
     ▼
App/Model/*                 Modelo: entidad de dominio + getters/setters.
     │
     ▼
Configuration/DataBase.php  PDO Singleton (conexión compartida única)
```

### 2.2 Responsabilidades por capa

| Capa | Carpeta | Responsabilidad |
| --- | --- | --- |
| Controladora | `App/Controller/` | Punto de entrada por caso de uso. Aplica guards (`require_login`, `require_role`, `require_post`), lee `$_GET`/`$_POST`, invoca servicios y delega el render (`App/View/`). |
| Servicio | `App/Service/` | Reglas de negocio y validaciones. Lanza `BusinessRuleException` ante datos inválidos. No conoce HTTP. |
| Repositorio | `App/Repository/` | Persistencia con PDO y SQL explícito. Traduce filas ↔ modelos. |
| Modelo | `App/Model/` | Entidad de dominio con atributos tipados y accesores. |
| Vista | `App/View/` | Plantillas PHP que reciben variables y hacen `echo`; se apoyan en `_helpers.php`. |
| Configuración | `Configuration/` | Conexión a BD y utilidades transversales. |

### 2.3 Convenciones observadas

- **Autoloader**: `Configuration/Autoloader.php` (mapeo PSR-4 preparado con
  *fallback* por carpeta/nombre de archivo) resuelve las clases de `App/` y
  `Configuration/`; se registra en `index.php:67-68` (ver §4.2). Los
  `require_once` existentes se conservan como respaldo idempotente.
- **Nombres por dominio**: las clases siguen el sufijo de su capa
  (`*Controller`, `*Service`, `*Repository`).
- **Vistas por rol**: `App/View/<Dominio>/<Accion>.php`
  (`Admin/`, `Client/`, `Owner/`, `Booking/`, `Venue/`, `Credit/`, ...).

---

## 3. Base de Datos y Sesiones

### 3.1 Conexión PDO Singleton

`Configuration/DataBase.php` implementa el patrón **Singleton**:

- Propiedad `private static ?PDO $instance = null;`.
- Constructor `private __construct()` — impide `new DataBase()` externo.
- Único punto de entrada: `DataBase::getConnection(): PDO`, que crea la conexión
  la primera vez y la reutiliza en llamadas posteriores durante la petición.

Características de la conexión:

- DSN MySQL en `127.0.0.1:3306`, base de datos `dbeventhall`, `charset=utf8mb4`.
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.
- En fallo de conexión, registra en `error_log` y responde `500` genérico
  **sin** exponer detalles.

> Las credenciales se leen de un archivo `.env` (raíz del proyecto, fuera del
> control de versiones) mediante `Configuration/DotEnv.php`, con fallbacks
> locales. Ver §4.4.

### 3.2 Esquema de tablas por dominio

Fuente: `DataBase/ScriptsSQL/dbeventhall.sql`.

#### Identidad

| Tabla | Descripción |
| --- | --- |
| `tbrole` | Cuenta base: nombre, email, password, activo. |
| `tbroleadmin` | Vínculo rol ↔ administrador. |
| `tbroleclient` | Vínculo rol ↔ cliente. |
| `tbroleowner` | Vínculo rol ↔ propietario. |
| `tbadmin` | Datos del administrador. |
| `tbclient` | Datos del cliente. |
| `tbowner` | Datos del propietario (nombre, alias, identificación). |
| `tbrolpasswordhistorical` | Historial de contraseñas del rol. |
| `tbrolphonehistorical` | Historial de teléfonos del rol. |
| `tbrolemailhistorical` | Historial de correos del rol. |

#### Catálogo

| Tabla | Descripción |
| --- | --- |
| `tblocation` | Provincia / cantón / distrito (y coordenadas). |
| `tbvenue` | Local (venue) publicado por propietario. |
| `tbvenueimage` | Galería de imágenes del local. |
| `tbservice` | Servicios ofrecidos sobre un local. |
| `tbservicehistory` | Historial de cambios de servicio. |
| `tbvenuerating` | Calificaciones/comentarios de locales. |
| `tbservicerating` | Calificaciones/comentarios de servicios. |
| `tbpromotion` | Promociones comerciales. |
| `tbpromotionservice` | Servicios asociados a una promoción. |

#### Reservas

| Tabla | Descripción |
| --- | --- |
| `tbbooking` | Reserva (relación cliente / local / fechas / estado). |
| `tbdetail` | Líneas de detalle de una reserva. |
| `tbbookingdetail` | Vínculo reserva ↔ detalle. |
| `tbbookingticket` | Comprobante subido por el cliente. |
| `tbbookinghistory` | Historial de estados de la reserva. |
| `tbbookingrefund` | Solicitudes de reembolso. |

#### Cobros

| Tabla | Descripción |
| --- | --- |
| `tbpaymentmethod` | Métodos de pago. |
| `tbownerpayment` | Datos de cobro del propietario. |
| `tbinvoice` | Facturas. |
| `tbcommissionconfig` | Configuración de comisión de plataforma. |
| `tbeearning` | Ganancias / liquidaciones. |

#### Crédito

| Tabla | Descripción |
| --- | --- |
| `tbcreditconfig` | Política de crédito del propietario (días, umbral). |
| `tbcredit` | Cuenta por cobrar generada por una reserva. |
| `tbcreditpayment` | Abonos parciales de un crédito. |

> Bloque único y canónico (la duplicación fue eliminada). Ver §4.5.

#### Auditoría y notificaciones

| Tabla | Descripción |
| --- | --- |
| `tbnotification` | Notificaciones a usuarios. |
| `tbuserhistory` | Historial de actividad del usuario. |
| `tbownerhistory` | Historial de actividad del propietario. |

### 3.3 Manejo de sesiones y autenticación

- La sesión se inicia en `Public/index.php` con `session_start()` solo si no
  hay una sesión activa.
- `$_SESSION['type']` guarda el rol (`'admin' | 'client' | 'owner'`) y
  `$_SESSION['user']` guarda un **DTO liviano** (`UserSessionDTO`, en
  `App/Model/UserSessionDTO.php`) con identificadores, rol y datos mínimos de
  perfil, **sin credenciales**.
- Ya no se serializan entidades de dominio: el autoloader (registrado antes de
  `session_start()`) resuelve la clase del DTO durante la deserialización, por
  lo que tampoco se precargan modelos. Ver §4.1.

#### Autenticación iterativa por rol (`AuthService`)

`App/Service/AuthService.php` implementa `login()` probando **secuencialmente**
cada repositorio hasta encontrar coincidencia:

```php
$finders = [
    'admin'  => fn() => $this->adminRepo->findByEmail($email),
    'client' => fn() => $this->clientRepo->findByEmail($email),
    'owner'  => fn() => $this->ownerRepo->findByEmail($email),
];
```

Para cada usuario encontrado:

1. `password_verify($password, $user->getPassword())`; si falla →
   `BusinessRuleException("Correo o contraseña incorrectos.")`.
2. Si la cuenta está inactiva (`!$user->getIsActive()`) → excepción
   "Esta cuenta está desactivada.".
3. Si todo es válido, devuelve `['type' => $type, 'user' => UserSessionDTO::fromObject($user)]`
   (un DTO liviano **sin credenciales**).

`AuthService` también centraliza validaciones de registro
(`validateEmailIsUnique`, `validateNameNotEmpty`, `validatePasswordStrength`,
`validatePhoneFormat`, `validateIdentificationIsUnique`, ...).

#### Guards

Definidos en `App/View/_helpers.php`, disponibles para todas las controladoras:

| Guard | Comportamiento |
| --- | --- |
| `require_login()` | Exige sesión de cualquier rol. Sin sesión: AJAX → `401` JSON; normal → redirige a `auth/showLogin`. |
| `require_role(string $type)` | Exige un rol concreto. Sin sesión → `require_login()`; rol distinto: AJAX → `403` JSON; normal → redirige al login. |
| `require_post()` | Exige método POST para operaciones que mutan estado. AJAX → `405` JSON; normal → `405` texto. |

#### Tokens CSRF

- `csrf_token()` genera (si no existe) `$_SESSION['csrf_token']` con
  `bin2hex(random_bytes(32))`.
- `csrf_field()` inyecta el campo oculto en los formularios.
- Validación global en `index.php:104-112`: **todo** POST debe traer un
  `csrf_token` que coincida, comparado con `hash_equals`; en caso contrario
  responde `403` y corta la ejecución.
- `csrf_validate()` expone la misma comprobación para uso puntual en
  controladoras.

---

## 4. Registro de Deuda Técnica y Riesgos

### 4.1 Serialización de objetos completos en `$_SESSION['user']` — RESUELTA

**Severidad: Alta — resuelta.**

`$_SESSION['user']` almacenaba el objeto de dominio serializado (no un id ni un
DTO mínimo). Consecuencias:

- Obligaba a precargar las clases `Client`/`Owner`/`Admin` **antes** de
  `session_start()`, acoplando el arranque a esas clases.
- Cualquier cambio de estructura de la clase rompía el `unserialize()` de
  sesiones ya emitidas (`__PHP_Incomplete_Class`).
- La contraseña (hash) viajaba dentro del objeto guardado en sesión.
- Los datos de perfil quedaban "congelados" hasta que se refrescara la sesión.

**Recomendación:** guardar solo el identificador y el rol en sesión, y
recuperar el perfil desde el repositorio cuando se necesite (o un DTO sin
credenciales).

**Resolución (Fase 4.1):** `$_SESSION['user']` ahora almacena
`UserSessionDTO` (`App/Model/UserSessionDTO.php`), un objeto plano con
identificadores (`idRol`, `idClient`, `idOwner`, `idAdmin`), rol y datos
mínimos de perfil, **sin contraseña ni entidades de dominio**.
`AuthService::login()` devuelve el DTO (`UserSessionDTO::fromObject()`),
`_helpers.php::current_user()` lo expone a las vistas y los controladores de
perfil lo rehidratan desde el repositorio cuando necesitan datos frescos. Se
eliminó la precarga de `Client`/`Owner`/`Admin` antes de `session_start()`.

### 4.2 Ausencia de autoloader PSR-4 (carga manual con `require_once`) — RESUELTA

**Severidad: Media — resuelta.**

No existe `composer.json` ni autoload. Cada archivo debe declarar manualmente
sus dependencias con `require_once` y rutas relativas a `__DIR__`. Efectos:

- Alto acoplamiento a la ubicación de los archivos.
- Riesgo de `require` faltantes u órdenes de carga incorrectos.
- Duplicación y mantenimiento tedioso al añadir clases.

**Recomendación:** introducir Composer con `autoload` PSR-4 (`App\` →
`App/`) y migrar progresivamente.

**Resolución (Fase 4.2):** se añadió `Configuration/Autoloader.php`, un
autoloader propio (sin Composer) que mapea los prefijos PSR-4 de `App\` y
`Configuration\` al sistema de archivos y aplica un *fallback* por
carpeta/nombre de archivo para las clases sin namespace. Se registra con
`Autoloader::register()` en `index.php:67-68`.

### 4.3 Inexistencia de claves foráneas (FK) a nivel de MySQL — RESUELTA

**Severidad: Alta — resuelta.**

El script `DataBase/ScriptsSQL/dbeventhall.sql` **no define ninguna**
`FOREIGN KEY`, `REFERENCES` ni `CONSTRAINT`. Las relaciones
(p. ej. `tbcredit.tbbookingid`, `tbvenueid`, `tbclientid`) son enteros sueltos
sin integridad referencial en la base de datos.

**Riesgo:** filas huérfanas, inconsistencias y borrados que dejan referencias
colgantes; la integridad depende exclusivamente del código de la aplicación.

**Recomendación:** añadir FKs con políticas `ON DELETE`/`ON UPDATE` explícitas
y ejecutar un proceso de saneamiento previo a la migración.

**Resolución (Fase 4.3):** el script canónico
`DataBase/ScriptsSQL/dbeventhall.sql` define ahora **50 claves foráneas** con
políticas `ON DELETE`/`ON UPDATE` explícitas. Además se creó la migración
`DataBase/ScriptsSQL/migrations/001_add_foreign_keys.sql`, que incluye
comprobaciones previas de huérfanos y `ALTER TABLE` idempotentes por tabla.
Políticas destacadas: *junctions* `ON DELETE CASCADE`;
`tbclient`/`tbvenue.tblocationid` y `tbdetail.tbserviceid/tbvenueid`
`ON DELETE SET NULL`; `tbbooking` (cliente/local), `tbcredit` y auditoría
`ON DELETE RESTRICT`; todas con `ON UPDATE CASCADE`.

### 4.4 Credenciales de base de datos hardcodeadas — RESUELTA

**Severidad: Alta — resuelta.**

`Configuration/DataBase.php` define las credenciales como constantes de clase:

```php
private const HOST    = '127.0.0.1';
private const DB_NAME = 'dbeventhall';
private const USER    = 'root';
private const PASS    = '';
```

**Riesgo:** secretos versionados en el repositorio, uso de `root` sin
contraseña como usuario de aplicación y nula capacidad de configurar entornos
(desarrollo/producción) sin editar el código.

**Recomendación:** mover credenciales a variables de entorno (`.env` fuera del
control de versiones), crear un usuario de BD dedicado con privilegios mínimos
y documentar el `.env.example`.

**Resolución (Fase 4.4):** las credenciales se externalizaron a un archivo
`.env` (raíz del proyecto, incluido en `.gitignore`), leído por
`Configuration/DotEnv.php`. `Configuration/DataBase.php` obtiene `DB_HOST`,
`DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` desde `$_ENV` con fallbacks
seguros. Se documentó `.env.example`.

### 4.5 Tablas de crédito duplicadas en `DataBase/ScriptsSQL/dbeventhall.sql` — RESUELTA

**Severidad: Media — resuelta.**

El script define **dos veces** el bloque de crédito: una primera versión
(`CREATE TABLE tbcreditconfig`, `tbcredit`, `tbcreditpayment`, líneas ~405-475)
y una segunda con `CREATE TABLE IF NOT EXISTS` (líneas ~446-476). La
duplicación no rompe la importación (la segunda usa `IF NOT EXISTS`), pero:

- Dificulta saber cuál es la definición canónica.
- Puede ocultar divergencias entre ambas copias.
- Incrementa el riesgo de migraciones inconsistentes.

**Recomendación:** conservar una única definición canónica y eliminar el bloque
redundante.

**Resolución (Fase 4.5):** se conservó una única definición canónica de
`tbcreditconfig`, `tbcredit` y `tbcreditpayment` en `dbeventhall.sql`; el
bloque redundante fue eliminado.

### 4.6 Archivo inconsistente `App/Repository/Service/HistoryService.php` y temporales `*.php.tmp` — RESUELTA

**Severidad: Media — resuelta.**

- Existen **dos** clases `HistoryService`:
  - `App/Service/HistoryService.php` — la versión en uso, con
    `Recommendation/HybridEngine`.
  - `App/Repository/Service/HistoryService.php` — archivo **huérfano**: vive
    en una ruta (`Repository/Service/`) que no respeta la estructura de capas y
    **no es referenciado** por ningún `require_once` del proyecto.
- Hay archivos temporales de edición:
  - `App/View/Admin/AdminForm.php.tmp`
  - `App/View/Admin/ClientForm.php.tmp`
  - `App/View/Admin/OwnerForm.php.tmp`
  - `tmp_v.txt` (raíz del repositorio).

**Riesgo:** confusión sobre cuál es la fuente de verdad, código muerto y
residuos que ensucian el control de versiones.

**Recomendación:** eliminar el `HistoryService` huérfano y los `.tmp`/`tmp_v.txt`,
y añadir `*.tmp` y `tmp_*` al `.gitignore`.

**Resolución (Fase 4.6):** se eliminó el `HistoryService` huérfano
(`App/Repository/Service/HistoryService.php`), los temporales
`App/View/Admin/AdminForm.php.tmp`, `App/View/Admin/ClientForm.php.tmp` y
`App/View/Admin/OwnerForm.php.tmp`, y `tmp_v.txt`. Se conserva la versión en uso
`App/Service/HistoryService.php`. `.gitignore` incorpora `*.tmp`, `tmp_*` y
`*.log`.

### 4.7 Escrituras no atómicas y errores técnicos sin registro uniforme (Fase 6) — RESUELTA

**Severidad: Alta — resuelta.**

Existían flujos con **escrituras compuestas** (reserva + detalle + historial,
facturación, créditos y abonos) que no eran atómicos, y los errores técnicos no
se registraban de forma uniforme ni se presentaban al usuario de forma genérica.

**Resolución (Fase 6):**

- **Gestor de transacciones anidable** en `Configuration/DataBase.php`
  (`beginTransaction`, `commit`, `rollBack`, `inTransaction`,
  `getActiveConnection`, `rollBackActive`). El primer `begin` abre la
  transacción real; los anidados usan `SAVEPOINT` (`eventhall_sp_<nivel>`), de
  modo que un orquestador y el servicio que invoca se componen sin el error de
  "transacción ya activa".
- **Logging seguro y excepción de servicio:** `App/Service/ErrorLogger.php`
  (contexto, clase, mensaje, `archivo:línea` y traza en `error_log`) y
  `App/Service/ServiceException.php` (fallo técnico con mensaje genérico). Las
  reglas de negocio siguen usando `BusinessRuleException`.
- **Servicios envueltos** con el patrón `begin` → `try`/escrituras/`commit` →
  `catch` (rollback + relanzado de excepciones limpias + log y
  `ServiceException`): `BookingService` (crear, cancelar, reembolso),
  `CreditService` (crédito, abono), `InvoiceService` (generar, aprobar,
  rechazar, actualizar estado), `BookingTicketService` (subir, aprobar,
  rechazar) y los orquestadores `BookingActionService` y `BookingAdminService`.
- **Red de seguridad global** en el `set_exception_handler` de
  `Public/index.php`: revierte cualquier transacción activa
  (`DataBase::rollBackActive()`) antes de responder el `500`, que nunca expone
  SQL.
- **Verificación:** pruebas de fallo forzado con triggers `SIGNAL` confirmaron
  cero filas huérfanas y respuesta `500` genérica con traza en el log.

---

## Anexo — Resumen de hallazgos y estado

| # | Hallazgo | Severidad | Estado | Ubicación / Resolución |
| --- | --- | --- | --- | --- |
| 4.1 | Objetos serializados en `$_SESSION['user']` | Alta | **Resuelta** | `App/Model/UserSessionDTO.php` |
| 4.2 | Sin autoloader PSR-4 (`require_once` manual) | Media | **Resuelta** | `Configuration/Autoloader.php` |
| 4.3 | Sin claves foráneas en MySQL | Alta | **Resuelta** | `DataBase/ScriptsSQL/dbeventhall.sql`, `DataBase/ScriptsSQL/migrations/001_add_foreign_keys.sql` |
| 4.4 | Credenciales de BD hardcodeadas | Alta | **Resuelta** | `Configuration/DotEnv.php`, `.env`, `.env.example` |
| 4.5 | Tablas de crédito duplicadas | Media | **Resuelta** | `DataBase/ScriptsSQL/dbeventhall.sql` |
| 4.6 | `HistoryService` huérfano y archivos `*.tmp` | Media | **Resuelta** | (archivos eliminados) |
| 4.7 | Escrituras no atómicas y errores sin registro uniforme (Fase 6) | Alta | **Resuelta** | `Configuration/DataBase.php`, `App/Service/ErrorLogger.php`, `App/Service/ServiceException.php` |
