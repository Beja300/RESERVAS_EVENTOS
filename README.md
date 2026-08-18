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