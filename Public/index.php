<?php

/**
 * FRONT CONTROLLER
 *
 * Punto de entrada único de la aplicación.
 *
 * Uso:
 *   /Public/index.php?controller=venue&action=catalog
 *   /Public/index.php?controller=auth&action=login
 *
 * Los parámetros se mapean así:
 *   controller -> Clase (p.ej. "venue"  -> VenueController)
 *   action     -> Método (p.ej. "catalog" -> catalog)
 */

require_once __DIR__ . '/../Configuration/DataBase.php';

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
  'paymentMethod' => 'PaymentMethodController',
  'location'      => 'LocationController',
  'notification'  => 'NotificationController',
];

// =========================================================
// LEER PARÁMETROS DE RUTA
// =========================================================
$controllerKey = strtolower(trim($_GET['controller'] ?? 'venue'));
$action = $_GET['action'] ?? 'catalog';

if (!isset($controllers[$controllerKey])) {
  http_response_code(404);
  exit('Controlador no encontrado.');
}

$controllerClass = $controllers[$controllerKey];
$controllerFile = __DIR__ . '/../App/Controller/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
  http_response_code(404);
  exit('Archivo de controlador no encontrado.');
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
  http_response_code(500);
  exit('Clase de controlador no definida.');
}

$controller = new $controllerClass();

// =========================================================
// VALIDAR QUE LA ACCIÓN SEA UN MÉTODO VÁLIDO
// =========================================================
if (!method_exists($controller, $action)) {
  http_response_code(404);
  exit('Acción no encontrada.');
}

// =========================================================
// EJECUTAR ACCIÓN
// =========================================================
$controller->$action();
