# Fase 8 — Auditoría funcional contra Rúbrica: validaciones, formatos, CRUDs y mensajes

## Decisiones acordadas
1. **Fechas**: permitir `fin == inicio` (reserva de 1 día); rechazar `fin < inicio`. Mensaje: "La fecha de finalización debe ser igual o posterior a la fecha de inicio."
2. **Máscaras**: auto-formato **con guiones** en el cliente (`8888-8888`, `1-2345-0678`) + **normalización a dígitos en el servidor** antes de validar/guardar.
3. **Crédito**: elegibilidad por **cantidad de reservas** (`frequentMin`); no se agrega lista blanca.
4. **Moneda**: `format_colones()` → `₡1,234.56` (miles con coma, 2 decimales con punto).

---

## Hallazgos de la auditoría (estado actual)
- `BookingService::createBooking` **ya** valida `end < start` y tope de **+1 año** (rechaza 2050). `BookingAdminService::reschedule` también. → Item 1 casi completo (solo afinar mensajes/min-max en vistas).
- `AuthService::validatePhoneFormat` solo acepta 8 dígitos sin guiones; `validateIdentificationFormat` acepta física/jurídica/9‑12 dígitos, pero **NO se invoca en `registerOwner`** (sí en `AdminUserController` y `OwnerProfileController`/`ProfileService`).
- `format_colones` **no existe**; ~50 vistas usan `&#8353; number_format(...)`.
- `data-mask="phone|identification"` está en las vistas pero **no hay implementación en `app.js`**; la validación JS de teléfono rechaza guiones.
- Crédito: `OwnerConfig` ya permite plazo (1–365) y `frequentMin`; Owner muestra "Cuentas por cobrar" (correcto); **Cliente muestra "Mis créditos"** (la rúbrica pide "Cuentas por pagar"). Comentarios del `CreditController` están invertidos.
- CRUD UPDATE: perfiles de Client/Owner/Admin **ya refrescan `$_SESSION['user']`**; `AdminUserController::updateUser` rehidrata si edita su propia cuenta; venue/service redirigen a listado fresco. **Sin staleness**.

---

## Cambios a implementar

### 1) `App/View/_helpers.php` (nuevos formateadores)
- `format_colones($amount): string` → `'₡' . number_format((float)$amount, 2, '.', ',')`.
- `format_phone(?string): string` → solo dígitos; 8 → `XXXX-XXXX`; si no, devuelve el valor tal cual.
- `format_cedula(?string): string` → solo dígitos; 9→`d-dddd-dddd`, 10→`d-ddd-dddddd`, 11→`d-dddd-ddddd`, 12→`d-dddd-dddddd`; si no encaja, devuelve original.
- Todas envueltas en `if (!function_exists(...))` (convención del archivo).

### 2) `App/Service/AuthService.php`
- `validatePhoneFormat`: normaliza `preg_replace('/\D/','',...)`; exige 8 dígitos; mensaje granular "El teléfono debe contener 8 dígitos (ej. 8888-8888)."
- Nuevo `normalizePhone(?string): ?string` → dígitos o `null` si vacío.
- `validateIdentificationFormat`: ampliar a física `d-dddd-dddd`, jurídica `d-ddd-dddddd`, DIMEX `d-dddd-dddddd(5-6)`, y dígitos `9–12`; mensaje granular explicando los formatos válidos.
- Nuevo `normalizeIdentification(?string): string` → solo dígitos.
- `registerOwner`: **normalizar** identificación y **llamar** `validateIdentificationFormat` cuando venga; normalizar teléfono.
- `registerClient` / `registerAdmin`: normalizar teléfono antes de crear.

### 3) `App/Service/BookingService.php`
- Mantener `end == start` permitido; ajustar mensaje de `$endDate < $date` a "La fecha de finalización debe ser igual o posterior a la fecha de inicio."
- Cambiar los dos mensajes de tope a "No se pueden realizar reservas con más de 1 año de anticipación." (inicio y fin).
- Sin cambios estructurales.

### 4) `App/Service/CreditService.php`
- Pulir mensaje de `frequentMin`: "El mínimo de reservas para cliente frecuente debe estar entre 0 y 1000."
- Mantener plazo 1–365 y la lógica `enabled`+`frequentMin`. (Verificación del item 4; sin cambio funcional de fondo.)

### 5) `Public/js/app.js` (necesario para máscaras)
- Nuevo `App.initMasks()`:
  - `data-mask="phone"`: solo dígitos, máx 8; al `blur` formatea `XXXX-XXXX`.
  - `data-mask="identification"`: solo dígitos, máx 12; auto-guiones en vivo por longitud.
- En `validateField`, la regla `phone` usa `value.replace(/\D/g,'')` para el test de 8 dígitos.
- Registrar `App.initMasks()` dentro de `App.init()`.

### 6) Vistas
- **Moneda**: reemplazar `&#8353; <?= number_format($x, 2) ?>` por `<?= format_colones($x) ?>` en: `Venue/Detail.php`, `Venue/Catalog.php`, `Booking/Form.php`, `Booking/Detail.php`, `Invoice/Detail.php`, `Invoice/Form.php`, `Credit/Detail.php`, `Credit/OwnerList.php`, `Credit/ClientList.php`, `Credit/AdminList.php`, `Service/List.php`, `Service/AdminDetail.php`, `Admin/Dashboard.php`, `Admin/PendingServices.php`, `Admin/BookingDetail.php`, `Owner/Dashboard.php` (reemplazar closure local `$m`).
- **Fechas de reserva**: en `Booking/Form.php` añadir `min=hoy` y `max=hoy+1 año` a los inputs de fecha (con `datetime`/`date`).
- **Crédito Cliente**: retitular `Credit/ClientList.php` a "Cuentas por pagar" (subtítulo aclaratorio).
- **Máscaras**: asegurar `data-mask` + `maxlength` en `Owner/Form.php` (ownerIdentification, phoneNumber), `Admin/Profile.php`, `Admin/OwnerForm.php`, `Admin/OwnerEdit.php`, `Admin/ClientForm.php`, `Admin/ClientEdit.php`, `Admin/AdminForm.php`, `Admin/AdminEdit.php`. Limpiar atributos `data-mask` duplicados en `Client/Form.php` y `Auth/Setup.php`.
- Mensajes explicativos: revisar `field-error`/`form-hint` ya existentes; añadir hint de formato de cédula/teléfono donde falte.

### 7) Comprobaciones de UPDATE (item 3)
- Documentar en el resumen final que: perfiles refrescan sesión (`UserSessionDTO::fromObject`), `AdminUserController::updateUser` rehidrata si es la propia cuenta, y venue/service redirigen a listado fresco. Sin cambios de código salvo que la verificación detecte un caso real.

---

## Verificación
1. `php -l` sobre todos los PHP modificados.
2. Pruebas de fechas (HTTP con sesión cliente o vía Service):
   - `date=2050-01-01` → `BusinessRuleException` "No se pueden realizar reservas con más de 1 año de anticipación."
   - `end < start` → mensaje de fin anterior.
   - `end == start` → acepta (1 día).
3. Registro de propietario con cédula inválida (`abc`, `12-34`) → mensaje granular; con `1-2345-0678`/`1123456789` → acepta y guarda normalizado.
4. Teléfono `8888-8888` y `88888888` → ambos válidos; `123` → mensaje de 8 dígitos.
5. Render de vistas: confirmar `₡1,234.56`; `format_phone`/`format_cedula` correctos.
6. Client credit list muestra "Cuentas por pagar"; owner "Cuentas por cobrar".
7. Smoke HTTP (login, perfil, reserva, crédito, factura) con `error_log` limpio.

## Notas / riesgos
- `Public/js/app.js` no estaba en la lista de archivos del enunciado, pero es imprescindible para las máscaras; se incluye.
- Reemplazo masivo de moneda es mecánico; se validará con `php -l` y smoke.
- `data-mask` con guiones requiere normalización servidor (incluida) para no romper `^\d{8}$` ni unicidad.
