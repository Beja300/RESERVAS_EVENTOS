<?php

/**
 * FRONT CONTROLLER — Punto de entrada único de la aplicación.
 *
 * Despacha TODAS las rutas del sistema mediante:
 *
 *   /Public/index.php?controller=X&action=Y
 *
 * donde:
 *   controller -> Clave del controlador ("venue", "auth", ...).
 *   action     -> Método de ese controlador ("catalog", "login", ...).
 *
 * ---------------------------------------------------------------------
 * ÍNDICE COMPLETO DE RUTAS (las 30 vistas)
 * ---------------------------------------------------------------------
 *  #  Vista (App/View)                  Ruta (controller/action)
 * -- ---------------------------------  ---------------------------------------
 *  1  Auth/Login.php                    auth/showLogin
 *  2  Auth/Register.php                 auth/showRegister
 *  3  Venue/Catalog.php                 venue/catalog
 *  4  Venue/Detail.php                  venue/detail&id=
 *  5  Venue/List.php                    venue/list
 *  6  Venue/Form.php                    venue/showForm        (crear: vacío / editar: &id=)
 *  7  Service/List.php                  service/list&venueId=
 *  8  Service/Form.php                  service/showForm      (crear: &venueId= / editar: &id=&venueId=)
 *  9  Admin/PendingServices.php         service/pending
 * 10  Booking/Form.php                  booking/showForm&venueId=
 * 11  Booking/List.php                  booking/myBookings    (cliente) | booking/venueBookings&venueId= (owner-venuero)
 * 12  Booking/Detail.php                booking/detail&id=
 * 13  Client/Dashboard.php              client/dashboard
 * 14  Client/Profile.php                client/profile
 * 15  Client/Form.php                   client/updateProfile  (POST)
 * 16  Owner/Dashboard.php               owner/dashboard
 * 17  Owner/Form.php                    owner/profile
 * 18  Owner/List.php                    owner/dashboard       (perfil: owner/profile)
 * 19  Admin/Dashboard.php               admin/dashboard
 * 20  Admin/List.php                    admin/users           (usuarios) | admin/bookings (reservas)
 * 21  Admin/Form.php                    admin/dashboard       (panel de acciones)
 * 22  Invoice/Form.php                  invoice/showForm&bookingId=
 * 23  Invoice/Detail.php                invoice/detail&bookingId=
 * 24  Invoice/List.php                  invoice/list
 * 25  PaymentMethod/Form.php            paymentMethod/showForm
 * 26  PaymentMethod/List.php            paymentMethod/list
 * 27  Location/Form.php                 location/showForm
 * 28  Location/List.php                 location/list
 * 29  Notification/List.php             notification/list
 * --  (Front controller)                Public/index.php?controller=X&action=Y
 *
 * Las acciones que NO renderizan una vista (create/update/login/logout/confirm/
 * approve/reject/generate/markAsRead/...) realizan su lógica y redirigen.
 * ---------------------------------------------------------------------
 */

// =========================================================
// MANEJO DE ERRORES
// Nunca mostrar detalles de PHP/SQL al usuario final: se registran
// en el log del servidor y se responde con un mensaje claro y genérico.
// =========================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Autoloader: resuelve controladores, servicios, repositorios y modelos
// automáticamente (Deuda Técnica 4.2). Debe registrarse ANTES de
// session_start() para que la deserialización de sesión también lo use.
require_once __DIR__ . '/../Configuration/Autoloader.php';
Autoloader::register();

require_once __DIR__ . '/../Configuration/DataBase.php';
require_once __DIR__ . '/../App/Service/BusinessRuleException.php';

// $_SESSION['user'] guarda un DTO liviano (UserSessionDTO), no entidades de
// dominio. El autoloader, ya registrado arriba, resuelve su clase durante la
// deserialización de la sesión en session_start(), por lo que no hace falta
// precargar ningún modelo aquí.

// Helpers globales (incluyen is_ajax()/respond_json()/csrf_*), disponibles
// para todos los controladores y vistas.
require_once __DIR__ . '/../App/View/_helpers.php';

// =========================================================
// SESIÓN + TOKEN CSRF
// =========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Migración transparente: sesiones creadas antes de la introducción del DTO
// todavía contienen la entidad de dominio completa. Se convierte a DTO para
// no arrastrar objetos pesados (ni la contraseña) en la sesión.
if (isset($_SESSION['user']) && !($_SESSION['user'] instanceof UserSessionDTO)) {
    $legacyUser = $_SESSION['user'];

    if ($legacyUser instanceof Client || $legacyUser instanceof Owner || $legacyUser instanceof Admin) {
        $_SESSION['user'] = UserSessionDTO::fromObject($legacyUser);
    } elseif (is_array($legacyUser)) {
        $_SESSION['user'] = UserSessionDTO::fromArray($legacyUser);
    } else {
        unset($_SESSION['user']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        exit('Sesión inválida (token de seguridad). Por favor vuelve a intentarlo.');
    }
}

// =========================================================
// TABLA DE RUTAS (controller/action -> Clase y método)
// -------------------------------------------------------
// Cada acción conserva su URL histórica. Los controladores se dividieron
// por rol/función (p. ej. "venue/catalog" vive en VenueCatalogController y
// "venue/list" en OwnerVenueController) manteniendo los mismos parámetros.
// =========================================================
$routeMap = [
  // ---------- service ----------
  'service.list'       => ['OwnerServiceController', 'list'],
  'service.showForm'   => ['OwnerServiceController', 'showForm'],
  'service.create'     => ['OwnerServiceController', 'create'],
  'service.update'     => ['OwnerServiceController', 'update'],
  'service.pending'    => ['AdminServiceController', 'pending'],
  'service.approve'    => ['AdminServiceController', 'approve'],
  'service.reject'     => ['AdminServiceController', 'reject'],
  'service.detail'     => ['AdminServiceController', 'detail'],

  // ---------- venue ----------
  'venue.catalog'      => ['VenueCatalogController', 'catalog'],
  'venue.detail'       => ['VenueCatalogController', 'detail'],
  'venue.showOwner'    => ['VenueCatalogController', 'showOwner'],
  'venue.rate'         => ['VenueCatalogController', 'rate'],
  'venue.rateService'  => ['VenueCatalogController', 'rateService'],
  'venue.updateComment'=> ['VenueCatalogController', 'updateComment'],
  'venue.favorite'     => ['VenueCatalogController', 'favorite'],
  'venue.list'         => ['OwnerVenueController', 'list'],
  'venue.showForm'     => ['OwnerVenueController', 'showForm'],
  'venue.create'       => ['OwnerVenueController', 'create'],
  'venue.update'       => ['OwnerVenueController', 'update'],

  // ---------- booking ----------
  'booking.create'        => ['ClientBookingController', 'create'],
  'booking.showForm'      => ['ClientBookingController', 'showForm'],
  'booking.myBookings'    => ['ClientBookingController', 'myBookings'],
  'booking.addLine'       => ['ClientBookingController', 'addLine'],
  'booking.cancel'        => ['ClientBookingController', 'cancel'],
  'booking.requestRefund' => ['ClientBookingController', 'requestRefund'],
  'booking.pay'           => ['ClientBookingController', 'pay'],
  'booking.uploadTicket'  => ['ClientBookingController', 'uploadTicket'],
  'booking.detail'        => ['BookingDetailController', 'detail'],
  'booking.venueBookings' => ['OwnerBookingController', 'venueBookings'],
  'booking.pendingBookings' => ['OwnerBookingController', 'pendingBookings'],
  'booking.approveTicket' => ['OwnerBookingController', 'approveTicket'],
  'booking.rejectTicket'  => ['OwnerBookingController', 'rejectTicket'],

  // ---------- client ----------
  'client.dashboard'       => ['ClientDashboardController', 'dashboard'],
  'client.recommendations' => ['ClientDashboardController', 'recommendations'],
  'client.favorites'       => ['ClientDashboardController', 'favorites'],
  'client.profile'         => ['ClientProfileController', 'profile'],
  'client.updateProfile'   => ['ClientProfileController', 'updateProfile'],
  'client.updateLocation'  => ['ClientProfileController', 'updateLocation'],
  'client.removePhoto'     => ['ClientProfileController', 'removePhoto'],
  'client.deactivateAccount' => ['ClientProfileController', 'deactivateAccount'],

  // ---------- owner ----------
  'owner.dashboard'       => ['OwnerDashboardController', 'dashboard'],
  'owner.profile'         => ['OwnerProfileController', 'profile'],
  'owner.updateProfile'   => ['OwnerProfileController', 'updateProfile'],
  'owner.removePhoto'     => ['OwnerProfileController', 'removePhoto'],
  'owner.deactivateAccount' => ['OwnerProfileController', 'deactivateAccount'],
  'owner.paymentData'     => ['OwnerPaymentController', 'paymentData'],
  'owner.savePayment'     => ['OwnerPaymentController', 'savePayment'],
  'owner.removePayment'   => ['OwnerPaymentController', 'removePayment'],

  // ---------- admin ----------
  'admin.dashboard'             => ['AdminDashboardController', 'dashboard'],
  'admin.users'                 => ['AdminUserController', 'users'],
  'admin.activateUser'          => ['AdminUserController', 'activateUser'],
  'admin.deactivateUser'        => ['AdminUserController', 'deactivateUser'],
  'admin.showAdminForm'         => ['AdminUserController', 'showAdminForm'],
  'admin.createAdmin'           => ['AdminUserController', 'createAdmin'],
  'admin.showClientForm'        => ['AdminUserController', 'showClientForm'],
  'admin.createClient'          => ['AdminUserController', 'createClient'],
  'admin.showOwnerForm'         => ['AdminUserController', 'showOwnerForm'],
  'admin.createOwner'           => ['AdminUserController', 'createOwner'],
  'admin.showEditForm'          => ['AdminUserController', 'showEditForm'],
  'admin.updateUser'            => ['AdminUserController', 'updateUser'],
  'admin.bookings'              => ['AdminBookingController', 'bookings'],
  'admin.userHistory'           => ['AdminBookingController', 'userHistory'],
  'admin.bookingDetail'         => ['AdminBookingController', 'bookingDetail'],
  'admin.approvePayment'        => ['AdminBookingController', 'approvePayment'],
  'admin.rejectPayment'         => ['AdminBookingController', 'rejectPayment'],
  'admin.cancelBooking'         => ['AdminBookingController', 'cancelBooking'],
  'admin.rescheduleBooking'     => ['AdminBookingController', 'rescheduleBooking'],
  'admin.changeBookingVenue'    => ['AdminBookingController', 'changeBookingVenue'],
  'admin.refundBooking'         => ['AdminBookingController', 'refundBooking'],
  'admin.rejectRefundBooking'   => ['AdminBookingController', 'rejectRefundBooking'],
  'admin.profile'               => ['AdminProfileController', 'profile'],
  'admin.updateProfile'         => ['AdminProfileController', 'updateProfile'],
  'admin.removePhoto'           => ['AdminProfileController', 'removePhoto'],
  'admin.deactivateAccount'     => ['AdminProfileController', 'deactivateAccount'],
  'admin.commissionConfig'      => ['AdminFinanceController', 'commissionConfig'],
  'admin.saveCommissionConfig'  => ['AdminFinanceController', 'saveCommissionConfig'],
  'admin.cleanTestData'         => ['AdminFinanceController', 'cleanTestData'],
];

// Controladores no divididos (misma clase para todas sus acciones).
$fallbackControllers = [
  'auth'          => 'AuthController',
  'invoice'       => 'InvoiceController',
  'paymentmethod' => 'PaymentMethodController',
  'location'      => 'LocationController',
  'notification'  => 'NotificationController',
  'promotion'     => 'PromotionController',
  'api'           => 'ApiController',
  'credit'        => 'CreditController',
];

// =========================================================
// LISTA BLANCA DE ACCIONES PERMITIDAS (por controlador)
// Solo se puede ejecutar una acción listada aquí.
// =========================================================
$allowedActions = [
  'auth'          => ['showLogin', 'login', 'showRegister', 'registerClient', 'registerOwner', 'logout', 'cleanDemo', 'showSetup', 'createFirstAdmin'],
  'service'       => ['list', 'showForm', 'create', 'update', 'pending', 'approve', 'reject', 'detail'],
  'venue'         => ['catalog', 'detail', 'showOwner', 'list', 'showForm', 'create', 'update', 'rate', 'rateService', 'updateComment', 'favorite'],
  'booking'       => ['create', 'showForm', 'myBookings', 'detail', 'addLine', 'cancel', 'pay', 'venueBookings', 'pendingBookings', 'uploadTicket', 'approveTicket', 'rejectTicket', 'requestRefund'],
  'admin'         => ['dashboard', 'users', 'activateUser', 'deactivateUser', 'bookings', 'userHistory', 'profile', 'updateProfile', 'removePhoto', 'deactivateAccount', 'approvePayment', 'rejectPayment', 'cleanTestData', 'showAdminForm', 'createAdmin', 'showClientForm', 'createClient', 'showOwnerForm', 'createOwner', 'showEditForm', 'updateUser', 'bookingDetail', 'cancelBooking', 'rescheduleBooking', 'changeBookingVenue', 'refundBooking', 'rejectRefundBooking', 'commissionConfig', 'saveCommissionConfig'],
  'client'        => ['dashboard', 'recommendations', 'favorites', 'profile', 'updateProfile', 'updateLocation', 'removePhoto', 'deactivateAccount'],
  'owner'         => ['dashboard', 'profile', 'updateProfile', 'removePhoto', 'deactivateAccount', 'paymentData', 'savePayment', 'removePayment'],
  'invoice'       => ['showForm', 'generate', 'detail', 'list'],
  'paymentmethod' => ['list', 'showForm', 'create', 'edit', 'update', 'delete'],
  'location'      => ['list', 'showForm', 'create', 'edit', 'update'],
  'notification'  => ['list', 'markAsRead', 'markAllAsRead', 'open'],
  'promotion'     => ['list', 'showForm', 'create', 'edit', 'update', 'addService'],
  'api'           => ['locations', 'geolocate', 'venueComments', 'serviceComments'],
  'credit'        => ['ownerConfig', 'saveConfig', 'ownerList', 'clientList', 'request', 'pay', 'detail', 'adminList'],
];

// Ruta por defecto (acceso a /Public/index.php sin parámetros)
$defaultController = 'venue';
$defaultAction     = 'catalog';

// HTML ligero para las páginas de error (no depende de BD ni vistas).
function renderError(int $code, string $message): void
{
    http_response_code($code);
    echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\">"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">"
        . "<title>{$code}</title></head>"
        . "<body style=\"font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a;"
        . "display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;\">"
        . "<div style=\"text-align:center;background:#fff;padding:40px 52px;border-radius:14px;"
        . "box-shadow:0 10px 30px rgba(15,23,42,.12);\">"
        . "<div style=\"font-size:3rem;font-weight:800;color:#4f46e5;\">{$code}</div>"
        . "<p style=\"color:#64748b;margin-top:8px;\">{$message}</p>"
        . "<a href=\"" . htmlspecialchars(rtrim(dirname($_SERVER['SCRIPT_NAME'], 1), '/') . '/index.php') . "\" style=\"display:inline-block;margin-top:18px;color:#4f46e5;"
        . "text-decoration:none;font-weight:600;\">&larr; Ir al inicio</a>"
        . "</div></body></html>";
    exit;
}

// =========================================================
// HANDLERS GLOBALES
// -------------------------------------------------------
// - set_error_handler: convierte warnings/notices en excepciones para
//   que nunca se impriman crudos en la respuesta.
// - set_exception_handler: categoriza el error (validación vs servidor),
//   lo registra en el log y responde de forma amigable (HTML o JSON).
// =========================================================
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    // Avisos menores (notices/deprecations) se registran pero NO se convierten
    // en excepción: son benignos y no deben tumbar la petición.
    if (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED], true)) {
        error_log(sprintf('[EventHall] Aviso: %s en %s:%d', $message, $file, $line));
        return true;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (\Throwable $e): void {
    // Red de seguridad: si una excepción escapó sin que el servicio pudiera
    // revertir, aquí se revierte cualquier transacción PDO que siga abierta
    // antes de responder, para no dejar escrituras a medio confirmar.
    try {
        DataBase::rollBackActive();
    } catch (\Throwable $ignored) {
        // Nunca enmascarar la excepción original.
    }

    $isValidation = $e instanceof BusinessRuleException;
    $status = $isValidation ? 422 : 500;

    error_log(sprintf(
        '[EventHall] %s: %s en %s:%d',
        $isValidation ? 'Validación' : 'Error',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    $isAjaxRequest = function_exists('is_ajax') && is_ajax();

    if ($isValidation) {
        $message = $e->getMessage();
    } else {
        $message = 'Ocurrió un error inesperado. Intenta de nuevo o contacta al administrador.';
    }

    if ($isAjaxRequest && function_exists('respond_json') && !headers_sent()) {
        respond_json(['ok' => false, 'error' => $message], $status);
    }

    if (php_sapi_name() === 'cli') {
        http_response_code($status);
        exit($message);
    }

    // Si ya se envió salida no podemos cambiar el código HTTP ni las
    // cabeceras; solo registramos y cortamos la respuesta.
    if (headers_sent()) {
        exit($message);
    }

    renderError($status, $message);
});

// =========================================================
// LEER PARÁMETROS DE RUTA
// =========================================================
$controllerKey = strtolower(trim($_GET['controller'] ?? $defaultController));
$action        = ($_GET['action'] ?? $defaultAction);

// Validar que el controlador exista en el mapa.
if (!isset($fallbackControllers[$controllerKey]) && !isset($allowedActions[$controllerKey])) {
    renderError(404, 'Controlador no encontrado.');
}

// Validar que la acción esté en la lista blanca del controlador.
if (!isset($allowedActions[$controllerKey]) || !in_array($action, $allowedActions[$controllerKey], true)) {
    renderError(404, 'Acción no encontrada.');
}

$mapped = $fallbackControllers[$controllerKey] ?? null;

if ($mapped !== null) {
    $controllerClass = $mapped;
    $method = $action;
} elseif (isset($routeMap[$controllerKey . '.' . $action])) {
    [$controllerClass, $method] = $routeMap[$controllerKey . '.' . $action];
} else {
    renderError(404, 'Acción no encontrada.');
}

// class_exists() dispara el autoloader: resuelve la clase desde
// App/Controller/ y verifica que esté definida antes de instanciarla.
if (!class_exists($controllerClass)) {
    renderError(404, 'Archivo de controlador no encontrado.');
}

// Crear la instancia y ejecutar la acción (el controller valida sesión y roles).
$controller = new $controllerClass();
$controller->$method();