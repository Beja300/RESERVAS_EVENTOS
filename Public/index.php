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

require_once __DIR__ . '/../Configuration/DataBase.php';

// Las sesiones pueden contener objetos serializados ($_SESSION['user'] es
// un objeto Client/Owner/Admin). Para que PHP pueda deserializarlos es
// OBLIGATORIO que su clase esté definida ANTES de session_start(); por eso
// se precargan los perfiles aquí, antes de iniciar la sesión.
require_once __DIR__ . '/../App/Model/Client.php';
require_once __DIR__ . '/../App/Model/Owner.php';
require_once __DIR__ . '/../App/Model/Admin.php';

// Helpers globales (incluyen is_ajax()/respond_json()/csrf_*), disponibles
// para todos los controladores y vistas.
require_once __DIR__ . '/../App/View/_helpers.php';

// =========================================================
// SESIÓN + TOKEN CSRF
// =========================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
// MAPEO DE CONTROLLERS
// =========================================================
$controllers = [
  'auth'          => 'AuthController',
  'service'       => 'ServiceController',
  'venue'         => 'VenueController',
  'booking'       => 'BookingController',
  'admin'         => 'AdminController',
  'client'        => 'ClientController',
  'owner'         => 'OwnerController',
  'invoice'       => 'InvoiceController',
  'paymentmethod' => 'PaymentMethodController',
  'location'      => 'LocationController',
  'notification'  => 'NotificationController',
  'promotion'     => 'PromotionController',
  'api'           => 'ApiController',
];

// =========================================================
// LISTA BLANCA DE ACCIONES PERMITIDAS (por controlador)
// Solo se puede ejecutar una acción listada aquí.
// =========================================================
$allowedActions = [
  'auth'          => ['showLogin', 'login', 'showRegister', 'registerClient', 'registerOwner', 'logout', 'cleanDemo'],
  'service'       => ['list', 'showForm', 'create', 'update', 'pending', 'approve', 'reject', 'detail'],
  'venue'         => ['catalog', 'detail', 'showOwner', 'list', 'showForm', 'create', 'update', 'rate', 'rateService', 'updateComment'],
  'booking'       => ['create', 'showForm', 'myBookings', 'detail', 'addLine', 'cancel', 'pay', 'venueBookings', 'pendingBookings', 'uploadTicket', 'approveTicket', 'rejectTicket', 'requestRefund'],
  'admin'         => ['dashboard', 'users', 'activateUser', 'deactivateUser', 'bookings', 'approvePayment', 'rejectPayment', 'cleanTestData', 'showAdminForm', 'createAdmin', 'showClientForm', 'createClient', 'showOwnerForm', 'createOwner', 'showEditForm', 'updateUser', 'bookingDetail', 'cancelBooking', 'rescheduleBooking', 'changeBookingVenue', 'refundBooking', 'rejectRefundBooking'],
  'client'        => ['dashboard', 'profile', 'updateProfile', 'deactivateAccount'],
  'owner'         => ['dashboard', 'profile', 'updateProfile', 'removePhoto', 'deactivateAccount', 'paymentData', 'savePayment', 'removePayment'],
  'invoice'       => ['showForm', 'generate', 'detail', 'list'],
  'paymentmethod' => ['list', 'showForm', 'create', 'edit', 'update', 'delete'],
  'location'      => ['list', 'showForm', 'create'],
  'notification'  => ['list', 'markAsRead', 'markAllAsRead', 'open'],
  'promotion'     => ['list', 'showForm', 'create', 'addService'],
  'api'           => ['locations', 'venueComments', 'serviceComments'],
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
        . "<a href=\"/Public/index.php\" style=\"display:inline-block;margin-top:18px;color:#4f46e5;"
        . "text-decoration:none;font-weight:600;\">&larr; Ir al inicio</a>"
        . "</div></body></html>";
    exit;
}

// =========================================================
// LEER PARÁMETROS DE RUTA
// =========================================================
$controllerKey = strtolower(trim($_GET['controller'] ?? $defaultController));
$action        = ($_GET['action'] ?? $defaultAction);

// Validar que el controlador exista en el mapa.
if (!isset($controllers[$controllerKey])) {
    renderError(404, 'Controlador no encontrado.');
}

// Validar que la acción esté en la lista blanca del controlador.
if (!isset($allowedActions[$controllerKey]) || !in_array($action, $allowedActions[$controllerKey], true)) {
    renderError(404, 'Acción no encontrada.');
}

$controllerClass = $controllers[$controllerKey];
$controllerFile  = __DIR__ . '/../App/Controller/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    renderError(404, 'Archivo de controlador no encontrado.');
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    renderError(500, 'Clase de controlador no definida.');
}

// Crear la instancia y ejecutar la acción (el controller valida sesión y roles).
$controller = new $controllerClass();
$controller->$action();
