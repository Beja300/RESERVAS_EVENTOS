# RESERVAS_EVENTOS# PARADIGMAS_PROYECTO_
/**
NOS FALTA HACER:

METODOS DEL CRUD ES CON PDO NO DOCTRINE
CONEXION A BD
MODELOS
CONTROLLERS
LOGICA
VISTAS




# ------------------LOS NOMBRES ESTAN EN ESPAÑOL PERO SE DEBEN DE CAMBIAR A INGLES----------------------------
# ESTRUCTURA MODELO: 

App/Model/
├── Rol.php                    (Entidad)
├── RolRepository.php          (acceso a datos de tbrol)
├── Admin.php
├── AdminRepository.php
├── Cliente.php 
├── ClienteRepository.php
├── Propietario.php
├── PropietarioRepository.php
├── Ubicacion.php
├── UbicacionRepository.php
├── Local.php
├── LocalRepository.php
├── Servicio.php
├── ServicioRepository.php
├── Reserva.php
├── ReservaRepository.php
├── Detalle.php
├── DetalleRepository.php
├── MetodoPago.php
├── MetodoPagoRepository.php
├── Factura.php
├── FacturaRepository.php
├── Notificacion.php
└── NotificacionRepository.php


# CONTROLLER Y VIEW:

App/
├── Controller/
│   ├── AuthController.php 
│   ├── RolController.php              (tbrol — login, registro base, sesión)
│   ├── AdminController.php         (tbroladmin — gestión de administradores)
│   ├── ClienteController.php       (tbrolcliente — perfil de cliente)
│   ├── PropietarioController.php      (tbpropietario — gestión de propietarios)
│   ├── UbicacionController.php        (ubicacion — catálogo de ubicaciones)
│   ├── LocalController.php (tbpropietariolocal — gestión de negocios/locales)
│   ├── ServicioController.php    (tblocalservicio — catálogo de servicios por local)
│   ├── ReservaController.php  (tbclientesreserva — crear/ver reservas)
│   ├── DetalleController.php   (tbreservadetalle — el "carrito" de la reserva)
│   ├── MetodoPagoController.php       (tbmetodopago — catálogo de métodos de pago)
│   ├── FacturaController.php   (tbreservafactura — generar/consultar facturas)
│   └── NotificacionController.php     (tbnotificacion — enviar/marcar notificaciones)
│
└── View/
     ── auth/
    │   ├── login.php
    │   └── registro.php
    ├── cliente/                     ← nuevo
    │   ├── dashboard.php
    │   ├── explorarLocales.php
    │   └── verLocal.php
    ├── admin/dashboard.php          ← nuevo
    ├── propietario/dashboard.php    ← nuevo
    ├── rol/
    │   ├── login.php
    │   └── registro.php
    ├── Admin/
    │   ├── listar.php
    │   └── formulario.php
    ├── Cliente/
    │   ├── perfil.php
    │   └── formulario.php
    ├── propietario/
    │   ├── listar.php
    │   └── formulario.php
    ├── ubicacion/
    │   ├── listar.php
    │   └── formulario.php
    ├── Local/
    │   ├── listar.php
    │   ├── formulario.php
    │   └── detalle.php
    ├── Servicio/
    │   ├── listar.php
    │   └── formulario.php
    ├── Reserva/
    │   ├── listar.php
    │   ├── crear.php
    │   └── detalle.php
    ├── Detalle/
    │   └── carrito.php
    ├── metodoPago/
    │   ├── listar.php
    │   └── formulario.php
    ├── Factura/
    │   ├── listar.php
    │   └── detalle.php
    ├── notificacion/
    │   └── listar.php
    └── layout/
        ├── header.php
        └── footer.php




# SERVICE
Van los servicios de cada uno para manejar por separado las reglas de negocio
**/



/*

# Reglas de negocio por clase — PARADIGMAS PROYECTO

Estas reglas van en la capa **Service** (no en el Repositorio, que solo hace
queries, ni en el Controller, que solo orquesta la petición). Cada bloque
corresponde a una clase de `App/Model/`.

---

## Rol (tbrol)
- El correo debe ser único en todo el sistema — no se permiten dos cuentas
  con el mismo correo (sin importar si una es Admin, Cliente o Propietario).
- La contraseña debe cumplir un mínimo de seguridad antes de encriptarse
  (ej. 8 caracteres mínimo, al menos un número).
- Un `Rol` con `activo = false` no puede iniciar sesión, sin importar su
  subtipo.
- Si se proporciona teléfono, debe tener un formato válido (8 dígitos en
  Costa Rica).

## RolAdmin (tbroladmin)
- Solo puede existir un `RolAdmin` por cada `Rol` (relación 1:1 — ya se
  garantiza a nivel de BD con `UNIQUE` en la FK, pero conviene validarlo
  también antes del INSERT para dar un mensaje de error claro).
- Un admin no puede desactivarse a sí mismo si es el único admin activo del
  sistema (evita quedarse sin nadie que administre).
- Solo un `RolAdmin` puede aprobar o rechazar un `LocalServicio`
  (cambiar `tblocalservicioestado`).
- Solo un `RolAdmin` puede acceder al módulo de estadísticas.

## RolCliente (tbrolcliente)
- Hereda la regla de correo único de `Rol`.
- Un cliente con `activo = false` no puede crear nuevas reservas.
- Un cliente solo puede ver, editar o cancelar **sus propias** reservas —
  nunca las de otro cliente, aunque conozca el id.

## Propietario (tbpropietario)
- La identificación (cédula/RUT) debe ser única entre propietarios.
- Un propietario debe tener al menos un `PropietarioLocal` activo para
  poder recibir reservas.
- Un propietario no puede eliminar su cuenta si tiene reservas *pendientes*
  asociadas a alguno de sus locales.

## Ubicacion
- No se debe permitir una ubicación duplicada exacta (misma provincia +
  cantón + distrito + detalle) — evita registros redundantes.
- Provincia, cantón y distrito son obligatorios; el detalle es opcional.

## PropietarioLocal (tbpropietariolocal)
- La capacidad del local debe ser mayor a 0.
- Un local con `localactivo = false` no puede recibir nuevas reservas ni
  aparecer en el catálogo público de servicios.
- Un local debe tener una `Ubicacion` asignada antes de poder publicarse.
- Solo el propietario dueño del local (o un Admin) puede editarlo o
  eliminarlo — nunca otro propietario.

## LocalServicio (tblocalservicio)
- El precio debe ser mayor a 0.
- Un servicio con `tblocalservicioestado = 'rechazado'` no puede agregarse
  a ningún carrito/detalle de reserva.
- Solo los servicios con estado `'aprobado'` y `activo = true` se muestran
  en el catálogo que ve el cliente.
- Un servicio no se elimina físicamente si ya fue usado en algún
  `ReservaDetalle` — se desactiva (`activo = false`) en su lugar, para no
  perder el historial de reservas pasadas (por eso el esquema usa
  `ON DELETE RESTRICT` en esa FK).

## ClientesReserva (tbclientesreserva)
- La fecha de la reserva no puede ser anterior a la fecha actual.
- No se puede crear una reserva sobre un local con `localactivo = false`.
- Una reserva debe tener al menos una línea en `ReservaDetalle` antes de
  poder pasar a estado `'confirmada'`.
- Solo se puede cancelar una reserva si su estado actual es `'pendiente'`
  (una reserva ya `'confirmada'` y facturada requiere un proceso distinto,
  ej. reembolso).

## ReservaDetalle (tbreservadetalle)
- La cantidad debe ser mayor a 0.
- El `precioUnitario` se copia del precio del servicio **al momento de
  agregarlo al carrito** — si el negocio sube el precio después, no debe
  afectar reservas ya creadas.
- El descuento de una línea no puede ser mayor al subtotal de esa misma
  línea (`cantidad × precioUnitario`).
- No se puede agregar un servicio que pertenezca a un local distinto al
  de la reserva actual.

## MetodoPago (tbmetodopago)
- El `tipo` debe ser único (no debería existir "Efectivo" duplicado).
- Un método de pago con `activo = false` no debe poder seleccionarse al
  generar una factura nueva.

## ReservaFactura (tbreservafactura)
- Una reserva solo puede tener una factura asociada (relación 1:1 — ya
  reforzada con `UNIQUE` en el esquema).
- El `total` de la factura debe coincidir con la suma de todas las líneas
  de `ReservaDetalle` de esa reserva (cantidad × precio − descuento).
- No se puede generar una factura de una reserva que no esté en estado
  `'confirmada'`.
- Una factura con estado `'pagada'` no se anula directamente — requeriría
  un flujo de reembolso aparte (fuera del alcance de un simple `UPDATE`).

## Notificacion (tbnotificacion)
- Se genera automáticamente cuando cambia el estado de una reserva o de un
  servicio (ej. "tu reserva fue confirmada", "tu servicio fue aprobado").
- Solo el destinatario (`tbrolfk`) puede marcar su propia notificación
  como leída — no otro usuario.
- Las notificaciones con `activo = false` no se muestran en el listado del
  usuario (borrado lógico, no físico).

---

## Dónde codificar esto

Cada bloque de reglas relacionado con **una sola entidad** (ej. validar el
formato del correo) puede vivir directo en el Repositorio o en un método
de validación simple del Controller. Las reglas que **combinan varias
tablas** (ej. calcular el total de la factura sumando el detalle, o
validar que el local esté activo antes de crear una reserva) son las que
justifican un `Service` dedicado — por ejemplo `ReservaService.php`,
`AuthService.php`.
**/


/*
# REGLA JS
La regla general: JS es para experiencia de usuario en el navegador, nunca para lógica de negocio real — todo lo que ya puedes validar en PHP (Service/Controller) se debe volver a validar ahí, porque cualquiera puede desactivar JS o manipular el HTML.
**/

---
# HISTORIAL DE TRABAJO RECIENTE 13 de septiembre

> Sección informativa para el siguiente desarrollador: qué cambió, dónde está
> cada cosa y cómo verificar que todo sigue funcionando.

## 1) Ordenamiento lexicográfico en listas

Se estandarizó el orden de las listas por **nombres/ubicaciones** para que se
vean ordenadas de forma natural (sin importar mayúsculas, tildes ni números).

### Dónde se implementó
- `App/Service/OrderingService.php` — nuevo servicio con tres comparadores:
  - `OrderingService::strings($a, $b)` → nombres con acentos/case-insensitive.
  - `OrderingService::sequences($a, $b)` → orden natural de secuencias (ej. "Local 2" antes de "Local 10").
  - `OrderingService::locations($a, $b)` → ubicaciones por provincia → cantón → distrito.
- Catálogo de locales (`VenueCatalogController::sortCatalogVenues`): orden
  cercanía al cliente → rating → ubicación (lexicográfico) → nombre (natural).
- `HistoryService::recommendVenuesByLocation()` ordena por ubicación.
- `ORDER BY` agregados en repositorios: `VenueRepository::findByOwner`,
  `ServiceRepository` (findAvailableByLocal / findByLocal / findPending),
  `ClientRepository::findAll`, `OwnerRepository::findAll`,
  `AdminRepository::findAll`, `LocationRepository::findAll`.

### Cómo verificar
- `/tmp/opencode/test_sort_catalog.php` (test de integración del catálogo):
  espera el orden `[2, 3, 6, 5, 4, 1]`. Resultado actual: **OK**.

## 2) Refactorización de controladores (controllers más pequeños)

Se dividieron los 6 controladores grandes en **17 controladores** por rol/función.
**Las URLs no cambian** (sigue siendo `Public/index.php?controller=X&action=Y`).

### Controllers creados

| Antes (eliminado) | Después (nuevo) |
|---|---|
| `ServiceController` | `OwnerServiceController`, `AdminServiceController` |
| `ClientController` | `ClientDashboardController`, `ClientProfileController` |
| `OwnerController` | `OwnerDashboardController`, `OwnerProfileController`, `OwnerPaymentController` |
| `VenueController` | `VenueCatalogController`, `OwnerVenueController` |
| `BookingController` | `ClientBookingController`, `OwnerBookingController`, `BookingDetailController` |
| `AdminController` | `AdminDashboardController`, `AdminProfileController`, `AdminUserController`, `AdminBookingController`, `AdminFinanceController` |

### Servicios nuevos (reglas de negocio que vivían en los controllers)
- `App/Service/BookingActionService.php` — crear/cancelar/pagar/subir comprobante/aprobar-rechazar comprobante de reservas.
- `App/Service/BookingDetailService.php` — arma el detalle de una reserva (cliente/owner) para la vista.
- `App/Service/OwnerDashboardService.php` y `App/Service/AdminDashboardService.php` — métricas de los dashboards.
- `App/Service/ImageStorageService.php` — subida/borrado de archivos (fotos de perfil, locales, comprobantes). Usa `resource/{subdir}/` bajo `Public/`.
- `App/Service/ProfileService.php` — validaciones de perfil, cambio de contraseña y auditoría de credenciales.
- `App/Service/LocationService::findOrCreateByParts()` — reutiliza o crea una ubicación en un solo paso.

### Helpers globales (`App/View/_helpers.php`)
`input()`, `require_login()`, `require_role()`, `redirect_to()`, `respond_or_redirect()`,
`parse_year_month()`, `venue_comments_html()`, `service_comments_html()`.
Ya existían: `base_url`, `is_ajax`, `respond_json`, `render_partial`, `csrf_*`, `e`, `image_url`, `css_url`, `js_url`, `format_venue_location`, `current_user_type`.

> Regla para el siguiente desarrollador: **no volver a crear controllers de cientos de líneas**.
> Guardias de rol → `require_role('client'|'owner'|'admin')`. Redirects → `redirect_to()`. Respuestas AJAX/POST → `respond_or_redirect()`.
> Lógica de negocio → en `App/Service/`, nunca en el controller.

### Routing (`Public/index.php`)
La lista plana `$controllers`/`$allowedActions` se reemplazó por una **tabla de rutas por acción**:
`$routeMap['controller.action'] = ['Clase', 'método']`. Los controllers no divididos quedan en `$fallbackControllers`.
Para agregar una ruta nueva basta añadir una entrada al mapa **y** el nombre en `$allowedActions`.

### Bugs corregidos en el proceso
- `uploadTicket` ahora valida la extensión y el tamaño **antes** de mover el archivo.
- `activateUser` y `deactivateUser` tienen try/catch y mensajes de error.
- `cleanTestData` se ejecuta dentro de una transacción (rollback si algo falla).
- `approveTicket` (owner) redirige bien a `booking/detail`.
- `ServiceController::approve/reject` ahora inyectan `HistoryService` y en `reject` también se registra `logAction` (simetría con `approve`).
- Se eliminaron `session_start()` redundantes (el front controller ya inicia sesión).
- Los catch de formularios recargan sus datos antes de re-renderizar la vista.

## Cómo verificar el proyecto tras estos cambios

1. Sintaxis de todos los archivos:
   ```
   find App Public -name '*.php' | xargs -n1 php -l
   ```
   Resultado esperado: ningún error en los 163 archivos.
2. Levantar el servidor local:
   ```
   php -S 127.0.0.1:8899 -t Public
   ```
3. Smoke test por rol (login con los usuarios demo de `DataBase/ScriptsSQL/seed_test_data.sql`):
   - Admin: `admin/dashboard`, `admin/users`, `admin/bookings`, `admin/bookingDetail&id=1`, `admin/commissionConfig`, `service/pending`.
   - Owner: `owner/dashboard`, `owner/profile`, `owner/paymentData`, `venue/list`, `booking/venueBookings&venueId=1`, `service/list&venueId=1`.
   - Cliente: `client/dashboard`, `client/profile`, `booking/myBookings`, `booking/showForm&venueId=1`, `booking/detail&id=1`.
   - Público: `venue/catalog`, `venue/detail&id=1` → 200. Acciones de rol sin sesión → 302.
   - POST sin `csrf_token` → 403. Acción inexistente → 404.
4. Orden del catálogo: `php /tmp/opencode/test_sort_catalog.php` → debe devolver `[2, 3, 6, 5, 4, 1]`.

> NOTA: los controladores antiguos (`ServiceController`, `VenueController`, `BookingController`,
> `AdminController`, `ClientController`, `OwnerController`) **fueron eliminados**. Si el front
> controller deja de resolver algo, revisar `$routeMap` en `Public/index.php`, no restaurar los viejos.

---

## Recomendaciones y posicionamiento de locales (híbrido por reglas)

Objetivo original: ordenar el catálogo por **mejor calificación y cercanía al cliente**
(primero por distrito, luego por distancia real con la **fórmula de Haversine**). Sobre esa base
se agregó registro de interacciones de comportamiento y un **motor de recomendación híbrido por
reglas + distancia geográfica**, con la arquitectura lista para incorporar ML en el futuro.

### Coordenadas (DB y modelos)

- `DataBase/ScriptsSQL/migrate_recommendations.sql` → `ALTER TABLE tblocation` agrega
  `tblocationlatitude` y `tblocationlongitude` (`DECIMAL(10,7) NULL`).
- `DataBase/ScriptsSQL/dbeventhall.sql` ahora incluye ambas columnas en `tblocation`.
- `App/Model/Location.php` → propiedades `latitudeLocation` / `longitudeLocation` con getters/setters.
- `App/Repository/LocationRepository.php` → `save`, `findById`, `findAll`, `mapRow` manejan lat/lng;
  nuevo `updateCoordinates($idLocation, $latitude, $longitude)`.
- `App/Service/LocationService.php` → `validateAndCreate` / `findOrCreateByParts` aceptan
  lat/lng opcionales, validan rangos (−90..90 / −180..180) y exigen ambas juntas
  (`assertValidCoordinates`); al reutilizar una ubicación existente actualiza las coordenadas.
- `App/Service/VenueService.php::validateAndCreate` y `App/Controller/OwnerVenueController.php`
  (create/update) persisten lat/lng del local; `App/View/Venue/Form.php` incluye inputs opcionales.
- `App/Controller/ClientProfileController.php` + `App/View/Client/Profile.php` + 
  `Public/js/client/dashboard.js` guardan lat/lng del cliente (manual o geolocalización).
- Decisión: las coordenadas son **opcionales** y se piden/guardan en segundo plano; en `DataBase/Backup/*.sql`
  **no** se tocó nada.

### GeoService (distancia)

`App/Service/GeoService.php`:
- `haversineKm(...)` con radio terrestre 6371 km.
- `distanceKm(?Location, ?Location)` → `null` si faltan coordenadas.
- `proximityScore(...)` → `1/(1+d)` con coordenadas; sin ellas, fallback por niveles:
  mismo distrito = 1.0, mismo cantón = 0.7, misma provincia = 0.4, otra provincia = 0.1;
  sin ubicación de cliente o local = 0.5 neutro.
- `distanceLabel(...)` → `"a X km"` (1 decimal) o `"a X m"` (> ignorar razón de preferencia).

### Registro de interacciones (tbuserhistory)

- `HistoryAction` suma `CANCEL` y `RATING` (ya existían `VIEW`, `SEARCH`, `FAVORITE`, `BOOKING`, `PURCHASE`).
- `HistoryService` agrega `logVenueCancel`, `logVenueRating`, `logVenueFavorite`, `logVenueUnfavorite`, `isFavorite`, `favoriteVenueIdsByRole`.
- Cancelaciones del cliente (`BookingActionService::cancel`) y del admin
  (`AdminBookingController::cancelBooking` y `refundBooking`) registran `CANCEL`.
- Calificaciones del local (`VenueCatalogController::rate` / `updateComment`) registran `RATING`.
- `Admin/UserHistory.php` muestra las etiquetas "Canceló" y "Calificó".

### Favoritos (feature completo)

- Toggle por AJAX: `VenueCatalogController::favorite()` + ruta `venue.favorite` en `Public/index.php`.
- Botón ❤️ en `App/View/Venue/Detail.php` con `Public/js/venue/favorite.js` y estilos en `Public/css/venue/detail.css`.
- Página "Mis favoritos": ruta `client.favorites` → `ClientDashboardController::favorites()`
  + `App/View/Client/Favorites.php`, enlazada en `App/View/_header.php`.
- Al marcar FAVORITE se agrega `tbuserhistory`; al desmarcar se borra (`deleteFavorite`).

### Motor de recomendación híbrido (App/Service/Recommendation/)

Por reglas y distancia geográfica (sin ML), preparado para ML futuro:

- `RecommendationConfig` → pesos por modo e `INTERACTION_WEIGHTS`
  (SEARCH=1, VIEW=1, FAVORITE=4, BOOKING=7, PURCHASE=10, RATING=6, CANCEL=−4).
- `RecommendationContext` → candidatos + ubicaciones + ratings + colab + historial del rol.
- `RecommendationStrategy` (interfaz) + estrategias:
  - `LocationStrategy` (geo), `RatingStrategy`, `ContentStrategy` (afinidad por tipo de local),
    `CollaborativeStrategy` (popularidad global ponderada).
- `HybridEngine::rankVenues()` → normaliza cada señal a [0,1] y las combina con el peso del modo:
  catálogo `geo=0.40 rating=0.25 content=0.20 colab=0.15`; cerca de ti `geo=0.60 rating=0.20 content=0.05 colab=0.15`.
  Las señales sin datos quedan en 0 (no deforman el ranking). Empate → rating, luego nombre.
- `HistoryService` delega en el motor: `recommendForUser`, `recommendNear`, `rankVenuesForCatalog`
  (reemplazan a `recommendVenues`, `recommendVenuesByLocation` y a `sortCatalogVenues`/`nearTier`).
- `App/Repository/HistoryRepository.php` → `interactionWeightedScores` para el puntaje colaborativo
  y helpers de favoritos (`hasFavorite`, `deleteFavorite`, `favoriteVenueIdsByRole`).
- TODO(ML): agregar una estrategia "ml" en `HybridEngine` y su peso en `RecommendationConfig`.

### Cómo se ve en la UI

- `App/View/Venue/Catalog.php` y `App/View/Client/Dashboard.php` muestran la etiqueta
  **"a X km"** en las tarjetas cuando cliente y local tienen coordenadas
  (`$distanceLabelByVenue` calculado con `GeoService::distanceLabel`).

### Verificación

1. Sintaxis de todos los archivos PHP (`find App Public -name '*.php' | xargs -n1 php -l`).
2. Levantar `php -S 127.0.0.1:8899 -t Public`.
3. Probar: catálogo ordenado híbrido, badges "a X km", favoritos (marcar/desmarcar), panel
   "Mis favoritos", historial con CANCEL/RATING, y "Recomendados para ti"/"Locales cerca de ti"
   en `client/dashboard`.

---

# HISTORIAL DE TRABAJO RECIENTE 13 de septiembre (2.ª tanda)

> Continuación de la sección anterior. Qué cambió en esta tanda y cómo verificar.

## 1) Combos de ubicación vacíos (provincia/cantón/distrito) — corregido

El formulario de perfil del cliente y del local mostraban los `<select>` de
provincia/cantón/distrito vacíos o se borraban al editar. Causas y solución:

- `App/View/Client/Profile.php` — el pie de página no cargaba
  `Public/js/venue/location.js` (`$pageJs`), por eso nunca se poblaban los combos.
  Ahora `$pageJs = ['client/profile', 'venue/location']`.
- `Public/js/venue/location.js::applyPreselection()` — no hacía
  `provinceCombo.setValue(province)` y `syncNative()` dejaba el `<select>`
  nativo en blanco, rompiendo los `required` (impedía guardar aunque solo se
  cambiara la foto). Ahora se preselecciona con `setValue`.

## 2) Latitud/longitud no editables (auto-detección)

Decisión de diseño: **el usuario no teclea lat/lng a mano**; se obtienen de la
ubicación del navegador (o por IP como respaldo).

- `Public/js/geo.js` — nuevo: `navigator.geolocation` con respaldo
  `api/geolocate` (IP vía `ip-api.com`). Rellena los hidden `#latitude/#longitude`.
- `App/View/Client/Profile.php` y `App/View/Venue/Form.php` dejaron de mostrar
  inputs numéricos; solo hay hidden + texto de solo lectura + botón
  "Detectar ubicación" (`data-geo-auto="1"` solo si aún no hay coordenadas).
- `App/Controller/ApiController.php::geolocate` devuelve `{ok, lat, lng}`
  (y provincia/cantón/distrito por IP).

## 3) Dashboard e navegación del cliente

- Etiqueta del nav unificada: **"Inicio"** en dashboards de cliente, propietario y admin.
- Se eliminó **"Mis reservas"** del nav del cliente (las reservas se muestran
  en el panel). Se agregó el link **"Recomendaciones"** → `client/recommendations`.
- `App/View/Client/Dashboard.php` reescrito:
  - Div 1 "Locales alquilados": últimas 5 reservas (nombre, fechas, estado) + "Ver todas →".
  - Div 2 "Locales más frecuentes": los locales que el cliente **más ha visto**
    (historial `tbuserhistory` con `HistoryAction::VIEW`), no por reservas.
    Badge "X visitas" + "Ver local".
  - Módulo geo ligero: detecta y guarda la posición del cliente desde el panel.
- `App/View/Client/Recommendations.php` — nuevo: concentra "Recomendados para ti",
  "Locales cerca de ti" y el módulo geo completo.
- `App/Repository/HistoryRepository.php::mostViewedVenueIdsByRole()`
  → `[idVenue => nº de vistas]` ordenado por visitas (solo `VIEW` de `Venue`).
- `Public/index.php` → ruta y allowlist `client.recommendations`.

## 4) Ubicación del local con mapa (Leaflet + OpenStreetMap)

El formulario del local (crear/editar) **ya no usa la auto-detección**: el
propietario marca la ubicación en un mapa tipo "Google Maps".

- `Public/vendor/leaflet/` — Leaflet 1.9.4 descargado localmente
  (`leaflet.css`, `leaflet.js`, `images/`). Las teselas usan OpenStreetMap
  y la búsqueda usa Nominatim (requieren internet en el navegador).
- `Public/js/venue/location-map.js` — nuevo:
  - Clic/arrastre del marcador → hidden `#latitude/#longitude` (6 decimales).
  - Búsqueda de dirección/cantón (Nominatim, `countrycodes=cr`).
  - Re-centrado automático al elegir provincia/cantón (si aún no hay punto marcado).
  - Validación cliente: bloquea el envío sin marcar el mapa.
- `Public/js/venue/location.js` — `syncNative()` dispara el evento `change`
  en los `<select>` nativos (setear `.value` por JS no lo emite), necesario
  para el re-centrado del mapa por cantón.
- `App/View/Venue/Form.php` — carga Leaflet, `$pageCss = 'venue/form'`,
  `$pageJs = ['venue/form', 'venue/location', 'venue/location-map']` (sin `geo`),
  y el bloque del mapa reemplaza la auto-detección.
- `Public/css/venue/form.css` — estilos del mapa y del dropdown de búsqueda.
- `App/Controller/OwnerVenueController.php` — `requireCoordinates()` en
  `create()` y `update()`: las coordenadas ahora son **obligatorias**
  (error: "Debes marcar la ubicación exacta del local en el mapa.").

El perfil del cliente conserva la auto-detección de `geo.js`; el mapa es
exclusivo del formulario del local.

## Cómo verificar esta tanda

1. `find App Public -name '*.php' | xargs -n1 php -l` → sin errores.
2. `php -S 127.0.0.1:8899 -t Public` y login con los usuarios demo
   (`owner@eventhall.com`, `cliente@eventhall.com`, `admin@eventhall.com` / `Clave123`).
3. Owner → `venue/showForm`: el formulario muestra el mapa, el buscador y
   `client/vendor/leaflet/leaflet.js` responde 200. Guardar sin marcar el mapa
   responde 422 con el mensaje; marcarlo permite guardar.
4. Cliente → panel con "Locales alquilados" + "Locales más frecuentes" (X visitas),
   página "Recomendaciones", nav con "Inicio"; perfil con combos de ubicación
   poblados y auto-detección funcionando.