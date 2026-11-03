# Identidades y Reglas de Negocio de Seguridad

Proyecto EventHall — gestión de reservas de locales para eventos.
Este documento define formalmente las **identidades** del sistema y las
**reglas de negocio de seguridad** que protegen las credenciales de acceso.

---

## 1. Identidades del sistema

Una **identidad** es la representación lógica de un actor (persona o sistema)
con credenciales de acceso. En EventHall toda identidad se materializa a
través de la tabla base `tbrole`, más un perfil de subtipo y su tabla de
relación.

| Identidad | Tabla base | Perfil | Relación (junction) | PK | Descripción |
|---|---|---|---|---|---|
| Rol | `tbrole` | — | — | `tbroleid` | Identidad raíz: credenciales comunes (nombre, correo, contraseña, teléfono, activo). |
| Administrador | `tbrole` | `tbadmin` | `tbroleadmin` | `tbadminid` | Gestiona usuarios, aprueba servicios, ve estadísticas. |
| Cliente | `tbrole` | `tbclient` | `tbroleclient` | `tbclientid` | Reserva locales y servicios. |
| Propietario | `tbrole` | `tbowner` | `tbroleowner` | `tbownerid` | Ofrece locales y recibe ganancias. |

### Convención de identidad (FK)
**Toda columna que referencia la PK de otra tabla se llama exactamente igual
que esa PK.** Ejemplo: en `tbclient` la columna `tblocationid` apunta a
`tblocation.tblocationid`; en `tbbooking` la columna `tbclientid` apunta a
`tbclient.tbclientid`; en las junction `tbroleadmin.tbroleid` →
`tbrole.tbroleid` y `tbroleadmin.tbadminid` → `tbadmin.tbadminid`.

No se definen restricciones `FOREIGN KEY` a nivel de base de datos; la
integridad referencial y las reglas se validan en la capa de código
(Services/Repositories).

---

## 2. Mini-tablas históricas (auditoría de credenciales)

Para perpetuar los movimientos de cada identidad **sin sobrecargar `tbrole`**
ni ocupar el CPU con consultas pesadas sobre la tabla operativa, se usan
mini-tablas **append-only** (solo registro, sin columna `active`): nunca se
actualizan ni se borran; cada cambio genera una fila nueva con la fecha.

| Mini-tabla | Perpetúa | Sus columnas |
|---|---|---|
| `tbrolpasswordhistorical` | contraseña | `tbroleid`, `actualpassword`, `newpassword`, `date` |
| `tbrolphonehistorical` | teléfono | `tbroleid`, `actualphone`, `newphone`, `date` |
| `tbrolemailhistorical` | correo | `tbroleid`, `actualemail`, `newemail`, `date` |

La contraseña **siempre se guarda haseada** (bcrypt), tanto en `tbrole` como
en el histórico. Otras tablas históricas que ya existen en el esquema:
`tbservicehistory` (precios de servicios), `tbbookinghistory` (movimientos de
reservas), `tbownerhistory` (acciones del propietario) y `tbuserhistory`
(accesos y acciones por rol).

---

## 3. Reglas de negocio de seguridad (capa Service)

Todas se implementan en **RoleSecurityService** y se disparan desde los
controladores al editar el perfil o resetear la contraseña.

### 3.1 Contraseña (`changePassword`, `adminResetPassword`)
- Mínimo 8 caracteres y al menos un número (regla existente).
- **No reutilización**: no se permite usar ninguna de las últimas **5**
  contraseñas (se valida contra `tbrolpasswordhistorical`).
- **Frecuencia**: máximo **1 cambio cada 24 h** y máximo **3 en 30 días**.
  De excederse, el cambio se **bloquea** y se genera una alerta de actividad
  sospechosa (notificación al usuario y a todos los admins).
- El reset por un admin **nunca se bloquea** por frecuencia (para no dejar
  la cuenta sin recuperación), pero queda registrado y se notifica al dueño.

### 3.2 Teléfono (`recordPhoneChange`) — la "advertencia por cambio excesivo"
- Formato costarricense de **8 dígitos** (regla existente).
- **Umbrales**: máximo **1 cambio cada 15 días** y máximo **3 en 6 meses**.
- De excederse, el cambio **sí se aplica** y se perpetúa en el histórico, pero
  se emite una **alerta** al usuario y a los admins: "cambio de teléfono
  excesivo" (posible toma de cuenta).

### 3.3 Correo (`recordEmailChange`)
- El correo debe ser **único** en el sistema (regla existente).
- **Umbral**: máximo **1 cambio cada 30 días**. De excederse, se emite
  alerta de actividad sospechosa.

### 3.4 Notificación de actividad sospechosa
Cada alerta genera **dos** registros en `tbnotification`:
1. Al **rol afectado** (`tbroleid = el usuario`).
2. A **cada rol administrador activo** (`tbroleadmin`), con un mensaje de
   "ALERTA de seguridad".

Las notificaciones se crean a través de `NotificationRepository::save` y se
muestran en la bandeja del usuario (lógica de lecturas existente).

---

## 4. Decisiones de diseño (justificación)

- **Mini-tablas sin `active`**: al ser de solo registro, preservan la verdad
  histórica de todos los estados. Una columna `active` implicaría borrado
  lógico y perdería trazabilidad.
- **Separación tabla operativa / histórica**: `tbrole` se mantiene liviana
  para consultas frecuentes (login, perfil); los históricos se guardan y
  consultan solo en operaciones de auditoría, reduciendo la carga del CPU.
- **Registrar movimientos de cada identidad**: password, teléfono y correo
  son las tres vías por las que un atacante puede tomar el control de una
  cuenta; por eso cada una tiene su mini-tabla y sus reglas de frecuencia.